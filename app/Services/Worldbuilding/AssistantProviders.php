<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantConnection;
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
        if (! config('worldbuilding.local_connections') || $connection->user_id !== (int) config('worldbuilding.local_user_id')) {
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

    public function stream(AssistantConnection $connection, string $prompt, Closure $append, Closure $cancelled): void
    {
        if (! $connection->enabled) {
            throw new RuntimeException('This connection is disconnected. Reconnect it before sending.');
        }
        if ($connection->isLocal()) {
            $this->local($connection, $prompt, $append, $cancelled);

            return;
        }
        $this->api($connection, $prompt, $append, $cancelled);
    }

    private function binary(string $provider): string
    {
        return config('worldbuilding.'.$provider.'_binary');
    }

    private function local(AssistantConnection $connection, string $prompt, Closure $append, Closure $cancelled): void
    {
        if (! config('worldbuilding.local_connections') || $connection->user_id !== (int) config('worldbuilding.local_user_id')) {
            throw new RuntimeException('Local subscription access is only enabled for the configured local author.');
        }
        $directory = storage_path('app/private/assistant-runtime/'.Str::uuid());
        File::ensureDirectoryExists($directory, 0700);
        $environment = ['CLAUDECODE' => false, 'ANTHROPIC_API_KEY' => false, 'ANTHROPIC_AUTH_TOKEN' => false, 'ANTHROPIC_BASE_URL' => false, 'OPENAI_API_KEY' => false];
        if ($connection->provider === 'claude') {
            $command = [$this->binary('claude'), '--print', '--output-format', 'stream-json', '--verbose', '--include-partial-messages', '--tools', '', '--strict-mcp-config', '--mcp-config', '{"mcpServers":{}}', '--setting-sources', '', '--settings', '{"disableAllHooks":true}', '--disable-slash-commands', '--permission-mode', 'dontAsk', '--no-session-persistence', '--system-prompt', 'You are a worldbuilding conversation partner. No tools or filesystem access. Follow the supplied exercise instructions.', '--model', $connection->model];
        } else {
            // Isolate configuration/plugins/MCP from the user's coding setup; retain only their existing authentication.
            $home = $directory.'/codex';
            File::ensureDirectoryExists($home, 0700);
            $auth = (getenv('CODEX_HOME') ?: (getenv('HOME').'/.codex')).'/auth.json';
            if (! is_file($auth)) {
                throw new RuntimeException('Codex file-based login was not found. Sign in with Codex locally first.');
            }
            $authFingerprint = hash_file('sha256', $auth);
            copy($auth, $home.'/auth.json');
            chmod($home.'/auth.json', 0600);
            $environment['CODEX_HOME'] = $home;
            $command = [$this->binary('codex'), 'exec', '--json', '--ephemeral', '--skip-git-repo-check', '--ignore-rules', '--sandbox', 'read-only', '--disable', 'shell_tool', '--disable', 'apps', '--disable', 'plugins', '--disable', 'view_image', '--disable', 'image_generation', '--disable', 'multi_agent', '--disable', 'in_app_browser', '-c', 'web_search="disabled"', '-c', 'check_for_update_on_startup=false', '-c', 'notify=[]', '--model', $connection->model, '-'];
        }
        if ($connection->model === 'default') {
            $modelIndex = array_search('--model', $command, true);
            array_splice($command, $modelIndex, 2);
        }
        $process = new Process($command, $directory, $environment, $prompt, config('worldbuilding.timeout'));
        $buffer = '';
        $received = false;
        $providerError = false;
        try {
            $process->start(function (string $type, string $chunk) use (&$buffer, &$received, &$providerError, $connection, $append): void {
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
            if (isset($auth, $authFingerprint, $home) && is_file($home.'/auth.json') && is_file($auth) && hash_file('sha256', $auth) === $authFingerprint) {
                // Preserve a provider-issued token refresh only if the source login was not changed elsewhere.
                $refreshed = file_get_contents($home.'/auth.json');
                $decoded = json_decode($refreshed, true);
                if (is_array($decoded) && hash('sha256', $refreshed) !== $authFingerprint) {
                    $handle = fopen($auth, 'r+');
                    if ($handle && flock($handle, LOCK_EX)) {
                        $current = stream_get_contents($handle);
                        if (hash('sha256', $current) === $authFingerprint) {
                            rewind($handle);
                            fwrite($handle, $refreshed);
                            ftruncate($handle, strlen($refreshed));
                            fflush($handle);
                        }
                        flock($handle, LOCK_UN);
                    }
                    if ($handle) {
                        fclose($handle);
                    }
                }
            }
            File::deleteDirectory($directory);
        }
    }

    private function api(AssistantConnection $connection, string $prompt, Closure $append, Closure $cancelled): void
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
        $request = $anthropic ? $request->withHeaders(['x-api-key' => $connection->credential, 'anthropic-version' => '2023-06-01']) : $request->withToken($connection->credential);
        $payload = ['model' => $connection->model, 'stream' => true, 'messages' => [['role' => 'user', 'content' => $prompt]]];
        if ($anthropic) {
            $payload['max_tokens'] = 8192;
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
