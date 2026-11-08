<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantConnection;
use App\Models\AssistantRequest;
use App\Models\AssistantTurn;
use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class AssistantProviders
{
    public const PROVIDERS = ['codex' => 'ChatGPT · local Codex login', 'claude' => 'Claude · local Claude login', 'openrouter' => 'OpenRouter · API billing', 'openai' => 'OpenAI · API billing', 'anthropic' => 'Anthropic · API billing'];

    public function status(AssistantConnection $connection): string
    {
        if (! $connection->enabled) {
            return 'Disconnected';
        }
        if (! $connection->isLocal()) {
            return $connection->credential ? 'API key saved · connection checked when sending' : 'API key required';
        }
        if (! self::allowsLocal($connection->user_id)) {
            return 'Local subscription connection unavailable for this account';
        }
        $process = new Process([$this->binary($connection->provider), ...($connection->provider === 'codex' ? ['login', 'status'] : ['auth', 'status'])]);
        $process->setTimeout(8);
        try {
            $process->run();

            return $process->isSuccessful() ? 'Local login available' : 'Sign in locally before sending';
        } catch (\Throwable) {
            return 'Local provider executable unavailable';
        }
    }

    public function stream(
        AssistantConnection $connection,
        string $prompt,
        Closure $append,
        Closure $cancelled,
        AssistantTurn $turn,
        string $purpose = 'response',
    ): void {
        if ($cancelled()) {
            throw new RuntimeException('Stopped before starting the next request.');
        }
        $request = app(AssistantUsage::class)->begin($connection, $turn, $purpose);
        try {
            if ($connection->isLocal()) {
                $this->local($connection, $prompt, $append, $cancelled, $request);
            } else {
                $this->api($connection, $prompt, $append, $cancelled, $request);
            }
            $request->finish('completed');
        } catch (\Throwable $exception) {
            $request->finish($cancelled() ? 'cancelled' : 'failed');
            throw $exception;
        }
    }

    // Pipes a whole reply into a string. The heartbeat is a guarded update so a worker that was stopped or superseded cannot revive its turn.
    public function streamText(AssistantConnection $connection, string $prompt, Closure $cancelled, AssistantTurn $turn, string $purpose): string
    {
        $text = '';
        $lastBeat = 0;
        $this->stream($connection, $prompt, function ($delta) use (&$text, &$lastBeat, $turn): void {
            $text .= $delta;
            if (microtime(true) - $lastBeat > 2) {
                AssistantTurn::whereKey($turn->id)->where('status', 'running')->where('run_uuid', $turn->run_uuid)->update(['updated_at' => now()]);
                $lastBeat = microtime(true);
            }
        }, $cancelled, $turn, $purpose);

        return $text;
    }

    // Local subscription logins belong to the one configured author and are never offered to anyone else.
    public static function allowsLocal(int $userId): bool
    {
        return config('worldbuilding.local_connections') && $userId === (int) config('worldbuilding.local_user_id');
    }

    public function availableProviders(int $userId): array
    {
        return collect(self::PROVIDERS)
            ->reject(fn ($label, $provider) => in_array($provider, ['codex', 'claude'], true) && ! self::allowsLocal($userId))
            ->all();
    }

    // Models are often wrapped in a markdown fence; the callers decide what an unreadable reply means.
    public static function decodeJsonReply(string $reply): mixed
    {
        return json_decode(preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($reply)), true, 32, JSON_THROW_ON_ERROR);
    }

    public static function anthropicHeaders(string $key): array
    {
        return ['x-api-key' => $key, 'anthropic-version' => '2023-06-01'];
    }

    // Provider subprocesses must authenticate with the local login only, never with keys inherited from this app's environment.
    public static function isolatedEnvironment(): array
    {
        return [
            'CLAUDECODE' => false,
            'ANTHROPIC_API_KEY' => false,
            'ANTHROPIC_AUTH_TOKEN' => false,
            'ANTHROPIC_BASE_URL' => false,
            'OPENAI_API_KEY' => false,
        ];
    }

    public static function codexAuthPath(): string
    {
        return (getenv('CODEX_HOME') ?: getenv('HOME').'/.codex').'/auth.json';
    }

    // Preserve a provider-issued token refresh only if the source login was not changed elsewhere meanwhile.
    public static function writeBackRefreshedLogin(string $source, string $refreshedFile, string $fingerprint): void
    {
        if (! is_file($source) || ! is_file($refreshedFile) || hash_file('sha256', $source) !== $fingerprint) {
            return;
        }
        $refreshed = file_get_contents($refreshedFile);
        if (! is_array(json_decode($refreshed, true)) || hash('sha256', $refreshed) === $fingerprint) {
            return;
        }
        $handle = fopen($source, 'r+');
        if (! $handle) {
            return;
        }
        if (flock($handle, LOCK_EX)) {
            if (hash('sha256', stream_get_contents($handle)) === $fingerprint) {
                rewind($handle);
                fwrite($handle, $refreshed);
                ftruncate($handle, strlen($refreshed));
                fflush($handle);
            }
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }

    public function refreshAllowance(AssistantConnection $connection): void
    {
        if ($connection->provider !== 'openrouter') {
            $connection->forceFill(['allowance' => ['status' => 'unavailable'], 'allowance_checked_at' => now()])->save();

            return;
        }
        if (! $connection->enabled || ! $connection->credential) {
            throw new RuntimeException('Reconnect this account before checking its allowance.');
        }
        $response = Http::withToken($connection->credential)->connectTimeout(5)->timeout(10)->get('https://openrouter.ai/api/v1/key');
        if (! $response->successful()) {
            $connection->forceFill(['allowance' => ['status' => 'failed'], 'allowance_checked_at' => now()])->save();

            return;
        }
        $remaining = $response->json('data.limit_remaining');
        $connection->forceFill([
            'allowance' => [
                'status' => is_numeric($remaining) ? 'reported' : 'unavailable',
                'remaining_usd' => is_numeric($remaining) ? max(0, (float) $remaining) : null,
                'reset' => $response->json('data.limit_reset'),
            ],
            'allowance_checked_at' => now(),
        ])->save();
    }

    private function binary(string $provider): string
    {
        return config('worldbuilding.'.$provider.'_binary');
    }

    private function local(AssistantConnection $connection, string $prompt, Closure $append, Closure $cancelled, AssistantRequest $usage): void
    {
        if (! self::allowsLocal($connection->user_id)) {
            throw new RuntimeException('Local subscription access is only enabled for the configured local author.');
        }
        $directory = sys_get_temp_dir().'/dreamers-assistant-'.Str::uuid();
        File::ensureDirectoryExists($directory, 0700);
        $environment = self::isolatedEnvironment();
        if ($connection->provider === 'claude') {
            $command = [$this->binary('claude'), '--print', '--output-format', 'stream-json', '--verbose', '--include-partial-messages', '--tools', '', '--strict-mcp-config', '--mcp-config', '{"mcpServers":{}}', '--setting-sources', '', '--settings', '{"disableAllHooks":true}', '--disable-slash-commands', '--permission-mode', 'dontAsk', '--no-session-persistence', '--system-prompt', match ($usage->purpose) {
                'poetry_analysis' => 'You are a writing conversation partner. No tools or filesystem access. Follow the supplied conversation instructions.', default => 'You are a worldbuilding conversation partner. No tools or filesystem access. Follow the supplied exercise instructions.'
            }, '--model', $connection->model];
        } else {
            // Isolate configuration/plugins/MCP from the user's coding setup; retain only their existing authentication.
            $home = $directory.'/codex';
            File::ensureDirectoryExists($home, 0700);
            $auth = self::codexAuthPath();
            if (! is_file($auth)) {
                throw new RuntimeException('Codex file-based login was not found. Sign in with Codex locally first.');
            }
            $authFingerprint = hash_file('sha256', $auth);
            copy($auth, $home.'/auth.json');
            chmod($home.'/auth.json', 0600);
            $environment['CODEX_HOME'] = $home;
            $command = [$this->binary('codex'), 'exec', '--json', '--ephemeral', '--skip-git-repo-check', '--ignore-rules', '--sandbox', 'read-only', '--disable', 'shell_tool', '--disable', 'apps', '--disable', 'plugins', '--disable', 'view_image', '--disable', 'image_generation', '--disable', 'multi_agent', '--disable', 'in_app_browser', '--disable', 'browser_use', '--disable', 'browser_use_external', '--disable', 'computer_use', '--disable', 'unified_exec', '--disable', 'skill_search', '--disable', 'skill_mcp_dependency_install', '--disable', 'tool_suggest', '--enable', 'skip_host_skill_discovery', '-c', 'web_search="disabled"', '-c', 'check_for_update_on_startup=false', '-c', 'notify=[]', '--model', $connection->model, '-'];
        }
        if ($connection->model === 'default') {
            $modelIndex = array_search('--model', $command, true);
            array_splice($command, $modelIndex, 2);
        }
        if (($connection->getAttribute('effort') ?? 'default') !== 'default') {
            $command = array_merge($command, $connection->provider === 'claude' ? ['--effort', $connection->getAttribute('effort')] : ['-c', 'model_reasoning_effort="'.$connection->getAttribute('effort').'"']);
        }
        if ($cancelled()) {
            File::deleteDirectory($directory);
            throw new RuntimeException('Stopped before contacting the provider.');
        }
        $process = new Process($command, $directory, $environment, $prompt, config('worldbuilding.timeout'));
        $buffer = '';
        $received = false;
        $providerError = false;
        try {
            $process->start(function (string $type, string $chunk) use (&$buffer, &$received, &$providerError, $connection, $append, $usage): void {
                if ($type !== Process::OUT) {
                    return;
                }
                $buffer .= $chunk;
                while (($newline = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $newline);
                    $buffer = substr($buffer, $newline + 1);
                    $event = json_decode($line, true);
                    if (! is_array($event)) {
                        continue;
                    }
                    if (($event['type'] ?? '') === 'turn.completed' && isset($event['usage'])) {
                        $usage->recordUsage($event['usage'] + ['cache_read_tokens' => $event['usage']['cached_input_tokens'] ?? null]);
                    }
                    if (($event['type'] ?? '') === 'result' && isset($event['usage'])) {
                        $usage->recordUsage($event['usage'] + [
                            'cache_read_tokens' => $event['usage']['cache_read_input_tokens'] ?? null,
                            'cache_write_tokens' => $event['usage']['cache_creation_input_tokens'] ?? null,
                        ]);
                    }
                    if ($connection->provider === 'claude') {
                        $delta = $event['event']['delta'] ?? [];
                        if (($delta['type'] ?? '') === 'text_delta') {
                            $append($delta['text']);
                            $received = true;
                        }
                        if (($event['type'] ?? '') === 'result' && ($event['is_error'] ?? false)) {
                            $providerError = true;
                        }
                        if (! $received && ($event['type'] ?? '') === 'result' && isset($event['result']) && ! ($event['is_error'] ?? false)) {
                            $append($event['result']);
                            $received = true;
                        }
                    } else {
                        if (in_array($event['type'] ?? '', ['error', 'turn.failed'], true)) {
                            $providerError = true;
                        }
                        if (($event['type'] ?? '') === 'item.completed' && ($event['item']['type'] ?? '') === 'agent_message') {
                            $append(($received ? "\n\n" : '').$event['item']['text']);
                            $received = true;
                        }
                    }
                }
            });
            while ($process->isRunning()) {
                if ($cancelled()) {
                    $process->stop(1);
                    throw new RuntimeException('Stopped. Received writing is retained.');
                }
                $process->checkTimeout();
                usleep(100000);
            }
            if (! $process->isSuccessful() || $providerError) {
                // Raw provider diagnostics can include credentials or private runtime paths.
                throw new RuntimeException('The provider could not complete this turn. Check its local login, model availability and usage allowance. No fallback was charged.');
            }
            if (! $received) {
                throw new RuntimeException('The provider returned no text. Check the selected model and connection.');
            }
        } finally {
            if ($process->isRunning()) {
                $process->stop(1);
            }
            if (isset($auth, $authFingerprint, $home)) {
                self::writeBackRefreshedLogin($auth, $home.'/auth.json', $authFingerprint);
            }
            File::deleteDirectory($directory);
        }
    }

    private function api(AssistantConnection $connection, string $prompt, Closure $append, Closure $cancelled, AssistantRequest $usage): void
    {
        if (! $connection->credential) {
            throw new RuntimeException('Save an API key for this connection first.');
        }
        $anthropic = $connection->provider === 'anthropic';
        $endpoint = match ($connection->provider) {
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'anthropic' => 'https://api.anthropic.com/v1/messages',
            default => throw new RuntimeException('Unsupported provider.'),
        };
        $request = Http::connectTimeout(15)->timeout(config('worldbuilding.timeout'))->withOptions(['stream' => true]);
        $request = $anthropic ? $request->withHeaders(self::anthropicHeaders($connection->credential)) : $request->withToken($connection->credential);
        $payload = ['model' => $connection->model, 'stream' => true, 'messages' => [['role' => 'user', 'content' => $prompt]]];
        $payload[$connection->provider === 'openai' ? 'max_completion_tokens' : 'max_tokens'] = config('worldbuilding.max_output_tokens');
        if (! $anthropic) {
            $payload['stream_options'] = ['include_usage' => true];
        }
        if ($cancelled()) {
            throw new RuntimeException('Stopped before contacting the provider.');
        }
        $response = $request->post($endpoint, $payload);
        if (! $response->successful()) {
            throw new RuntimeException(match ($response->status()) {
                401, 403 => 'The API connection was refused. Check its key and account permissions.',
                402, 429 => 'The provider reported a billing or usage limit. Choose another connection explicitly or retry later.',
                default => 'The API provider could not start this turn. No automatic retry or fallback was made.',
            });
        }
        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $received = false;
        $completed = false;
        try {
            while (! $body->eof()) {
                if ($cancelled()) {
                    throw new RuntimeException('Stopped. Received writing is retained.');
                }
                $buffer .= $body->read(1024);
                while (($newline = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $newline));
                    $buffer = substr($buffer, $newline + 1);
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }
                    $json = trim(substr($line, 5));
                    if ($json === '[DONE]') {
                        $completed = true;

                        continue;
                    }
                    $event = json_decode($json, true);
                    $reported = $event['usage'] ?? $event['message']['usage'] ?? [];
                    if ($reported) {
                        $usage->recordUsage([
                            'input_tokens' => $reported['input_tokens'] ?? $reported['prompt_tokens'] ?? null,
                            'output_tokens' => $reported['output_tokens'] ?? $reported['completion_tokens'] ?? null,
                            'cache_read_tokens' => $reported['cache_read_input_tokens'] ?? $reported['prompt_tokens_details']['cached_tokens'] ?? null,
                            'cache_write_tokens' => $reported['cache_creation_input_tokens'] ?? null,
                            'reported_cost_usd' => $reported['cost'] ?? null,
                        ]);
                    }
                    if (isset($event['error']) || ($event['type'] ?? '') === 'error') {
                        throw new RuntimeException('The provider interrupted the response. Received writing is retained.');
                    }
                    if (($event['type'] ?? '') === 'message_stop') {
                        $completed = true;
                    }
                    $text = $anthropic ? ($event['delta']['text'] ?? '') : ($event['choices'][0]['delta']['content'] ?? '');
                    if (is_string($text) && $text !== '') {
                        $append($text);
                        $received = true;
                    }
                }
            }
        } finally {
            $body->close();
        }
        if (! $received || ! $completed) {
            throw new RuntimeException('The provider response ended before completion. Any received text is retained.');
        }
    }
}
