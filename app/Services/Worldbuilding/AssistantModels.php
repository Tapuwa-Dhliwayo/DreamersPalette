<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantConnection;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class AssistantModels
{
    public function catalogue(AssistantConnection $connection, bool $refresh = false): array
    {
        $this->authorize($connection);
        if (! $connection->isLocal() && ! $connection->credential) {
            return ['models' => [], 'status' => 'needs_key', 'message' => 'Add your API key to load this account’s models.'];
        }
        $identity = $connection->credential ?? $this->localIdentity($connection->provider);
        $key = 'studio-models:v1:'.$connection->user_id.':'.$connection->provider.':'.hash('sha256', $identity);
        if (! $refresh && ($cached = Cache::get($key))) {
            return $cached;
        }
        try {
            $models = $connection->isLocal() ? $this->localModels($connection) : $this->apiModels($connection);
            if (! $models) {
                throw new RuntimeException('No conversation models were returned.');
            }
            $catalogue = [
                'models' => $models,
                'status' => 'ready',
                'checked_at' => now()->toIso8601String(),
                'message' => 'Models reported by this connection. Access is checked when you send.',
            ];
            Cache::put($key, $catalogue, now()->addMinutes(10));
        } catch (\Throwable) {
            $catalogue = [
                'models' => [],
                'status' => 'failed',
                'message' => 'Models could not be loaded. Check this connection and try Refresh models. Your saved model has not changed.',
            ];
            Cache::put($key, $catalogue, now()->addSeconds(20));
        }

        return $catalogue;
    }

    public function assertSelection(
        AssistantConnection $connection,
        string $model,
        string $effort = 'default',
        string $field = 'conversationForm.model',
    ): void {
        $catalogue = $this->catalogue($connection);
        $selected = collect($catalogue['models'])->firstWhere('id', $model);
        if (! $selected) {
            // Existing connections remain usable during a catalogue outage, without a silent model change.
            $saved = $connection->exists ? $connection->getRawOriginal('model') : null;
            if ($catalogue['status'] !== 'ready' && $model === $saved && $effort === 'default') {
                return;
            }
            throw ValidationException::withMessages([$field => 'Choose a model from this connection’s list. Refresh models if it is unavailable.']);
        }
        if ($effort !== 'default' && ! in_array($effort, $selected['efforts'], true)) {
            throw ValidationException::withMessages(['conversationForm.effort' => 'Choose a thinking effort supported by the selected model.']);
        }
    }

    private function authorize(AssistantConnection $connection): void
    {
        abort_unless($connection->user_id === auth()->id() && auth()->user()?->can('worlds.update'), 403);
        if ($connection->exists) {
            Gate::authorize('update', $connection);
        }
        abort_unless(array_key_exists($connection->provider, app(AssistantProviders::class)->availableProviders(auth()->id())), 403);
    }

    private function localIdentity(string $provider): string
    {
        $path = match ($provider) {
            'codex' => AssistantProviders::codexAuthPath(),
            'claude' => getenv('HOME').'/.claude/.credentials.json',
            default => throw new RuntimeException('Unsupported local provider.'),
        };

        return is_file($path) ? hash_file('sha256', $path) : 'no-file-login';
    }

    private function apiModels(AssistantConnection $connection): array
    {
        $endpoint = match ($connection->provider) {
            'openai' => 'https://api.openai.com/v1/models',
            'anthropic' => 'https://api.anthropic.com/v1/models',
            'openrouter' => 'https://openrouter.ai/api/v1/models/user',
            default => throw new RuntimeException('Unsupported provider.'),
        };
        $client = Http::acceptJson()->connectTimeout(4)->timeout(10);
        $client = match ($connection->provider) {
            'anthropic' => $client->withHeaders(AssistantProviders::anthropicHeaders($connection->credential)),
            default => $client->withToken($connection->credential),
        };
        $models = [];
        $query = $connection->provider === 'anthropic' ? ['limit' => 100] : [];
        $deadline = microtime(true) + 15;
        for ($page = 0; $page < 10; $page++) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                throw new RuntimeException('Model discovery timed out.');
            }
            $response = $client->timeout($remaining)->get($endpoint, $query);
            if (! $response->successful() || ! is_array($response->json('data'))) {
                throw new RuntimeException('The model list was refused.');
            }
            foreach ($response->json('data') as $model) {
                if (! $this->supportsConversation($connection->provider, $model)) {
                    continue;
                }
                $models[] = [
                    'id' => $model['id'],
                    'name' => $model['display_name'] ?? $model['name'] ?? $model['id'],
                    'efforts' => [],
                ];
            }
            if (! $response->json('has_more')) {
                return collect($models)->unique('id')->sortBy('name')->values()->all();
            }
            $cursor = $response->json('last_id') ?? collect($response->json('data'))->last()['id'] ?? null;
            if (! is_string($cursor) || ($query['after_id'] ?? null) === $cursor) {
                break;
            }
            $query['after_id'] = $cursor;
        }
        throw new RuntimeException('The provider model list could not finish loading.');
    }

    private function supportsConversation(string $provider, array $model): bool
    {
        if (! is_string($model['id'] ?? null) || ! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:\/-]{0,199}$/D', $model['id'])) {
            return false;
        }

        return match ($provider) {
            'openai' => preg_match('/^(gpt-|chatgpt-|o[0-9])/', $model['id']) && ! preg_match('/(embedding|audio|realtime|transcrib|tts|image|search|instruct|codex|deep-research|pro(?:-|$))/', $model['id']),
            'anthropic' => true,
            'openrouter' => in_array('text', $model['architecture']['output_modalities'] ?? ['text'], true),
            default => false,
        };
    }

    private function localModels(AssistantConnection $connection): array
    {
        if (app(AssistantProviders::class)->status($connection) !== 'Local login available') {
            throw new RuntimeException('Sign in to this local connection first.');
        }
        $directory = sys_get_temp_dir().'/dreamers-models-'.Str::uuid();
        File::ensureDirectoryExists($directory, 0700);
        $environment = AssistantProviders::isolatedEnvironment();
        try {
            return match ($connection->provider) {
                'codex' => $this->codexModels($directory, $environment),
                'claude' => $this->claudeModels($directory, $environment),
                default => throw new RuntimeException('Unsupported local provider.'),
            };
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function codexModels(string $directory, array $environment): array
    {
        $auth = AssistantProviders::codexAuthPath();
        if (! is_file($auth)) {
            throw new RuntimeException('Local login unavailable.');
        }
        // Model discovery receives only the local login, without coding plugins, tools or repository context.
        $environment['CODEX_HOME'] = $directory;
        $fingerprint = hash_file('sha256', $auth);
        copy($auth, $directory.'/auth.json');
        chmod($directory.'/auth.json', 0600);
        $command = [
            config('worldbuilding.codex_binary'),
            'app-server',
            '--listen',
            'stdio://',
            '--disable',
            'apps',
            '--disable',
            'plugins',
            '--disable',
            'skill_search',
            '-c',
            'check_for_update_on_startup=false',
        ];
        $models = [];
        try {
            $this->exchange($command, $directory, $environment, [
                'id' => 1,
                'method' => 'initialize',
                'params' => ['clientInfo' => ['name' => 'dreamers_model_discovery', 'version' => '1.0.0']],
            ], function (array $event, Closure $send) use (&$models): bool {
                if (isset($event['error'])) {
                    throw new RuntimeException('Model discovery failed.');
                }
                switch ($event['id'] ?? null) {
                    case 1:
                        $send(['method' => 'initialized']);
                        $send(['id' => 2, 'method' => 'model/list', 'params' => ['limit' => 100, 'includeHidden' => false]]);

                        return false;
                    case 2:
                        foreach ($event['result']['data'] ?? [] as $model) {
                            $models[] = [
                                'id' => $model['model'],
                                'name' => $model['displayName'],
                                'efforts' => array_column($model['supportedReasoningEfforts'] ?? [], 'reasoningEffort'),
                                'is_default' => $model['isDefault'] ?? false,
                            ];
                        }
                        if ($event['result']['nextCursor'] ?? null) {
                            $send([
                                'id' => 2,
                                'method' => 'model/list',
                                'params' => ['limit' => 100, 'includeHidden' => false, 'cursor' => $event['result']['nextCursor']],
                            ]);

                            return false;
                        }

                        return true;
                    default:
                        return false;
                }
            });
        } finally {
            AssistantProviders::writeBackRefreshedLogin($auth, $directory.'/auth.json', $fingerprint);
        }
        $default = collect($models)->firstWhere('is_default', true);
        array_unshift($models, [
            'id' => 'default',
            'name' => 'Provider default'.($default ? ' · '.$default['name'] : ''),
            'efforts' => $default['efforts'] ?? [],
        ]);

        return $models;
    }

    private function claudeModels(string $directory, array $environment): array
    {
        $command = [
            config('worldbuilding.claude_binary'),
            '--print',
            '--input-format',
            'stream-json',
            '--output-format',
            'stream-json',
            '--verbose',
            '--tools',
            '',
            '--strict-mcp-config',
            '--mcp-config',
            '{"mcpServers":{}}',
            '--setting-sources',
            '',
            '--settings',
            '{"disableAllHooks":true}',
            '--disable-slash-commands',
            '--no-session-persistence',
        ];
        $models = [];
        $this->exchange($command, $directory, $environment, [
            'type' => 'control_request',
            'request_id' => 'models',
            'request' => ['subtype' => 'initialize', 'hooks' => (object) []],
        ], function (array $event) use (&$models): bool {
            if (($event['type'] ?? '') !== 'control_response' || ($event['response']['request_id'] ?? null) !== 'models') {
                return false;
            }
            if (($event['response']['subtype'] ?? '') !== 'success') {
                throw new RuntimeException('Model discovery failed.');
            }
            foreach ($event['response']['response']['models'] ?? [] as $model) {
                $models[] = [
                    'id' => $model['value'],
                    'name' => $model['displayName'],
                    'efforts' => $model['supportedEffortLevels'] ?? [],
                    'resolved_model' => $model['resolvedModel'] ?? null,
                ];
            }

            return true;
        });

        return $models;
    }

    private function exchange(array $command, string $directory, array $environment, array $initial, Closure $receive): void
    {
        $input = new InputStream;
        $send = fn (array $message) => $input->write(json_encode($message, JSON_THROW_ON_ERROR)."\n");
        $process = new Process($command, $directory, $environment, $input, 18);
        $buffer = '';
        $complete = false;
        try {
            $process->start(function (string $type, string $chunk) use (&$buffer, &$complete, $receive, $send): void {
                if ($type !== Process::OUT) {
                    return;
                }
                $buffer .= $chunk;
                if (strlen($buffer) > 2000000) {
                    throw new RuntimeException('Model catalogue response was too large.');
                }
                while (($end = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $end);
                    $buffer = substr($buffer, $end + 1);
                    $event = json_decode($line, true);
                    if (is_array($event) && $receive($event, $send)) {
                        $complete = true;
                    }
                }
            });
            $send($initial);
            while ($process->isRunning() && ! $complete) {
                $process->checkTimeout();
                usleep(20000);
            }
            if (! $complete) {
                throw new RuntimeException('Model catalogue unavailable.');
            }
        } finally {
            $input->close();
            $process->stop(0.2);
        }
    }
}
