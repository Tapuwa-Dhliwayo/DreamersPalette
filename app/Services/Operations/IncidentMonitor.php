<?php

namespace App\Services\Operations;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class IncidentMonitor
{
    public function record(Throwable $exception, ?string $feature = null, array $context = []): ?array
    {
        if (! config('incidents.enabled') || $this->isStaleClient($exception) || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500)) {
            return null;
        }
        $feature ??= 'application';
        $incident = $this->change(function (array &$state) use ($exception, $feature, $context): array {
            $incident = $state[$feature] ?? null;
            if (! $incident || $incident['resolved_at']) {
                if ($incident) {
                    $state['_history'][] = $incident;
                    $state['_history'] = array_slice($state['_history'], -200);
                }
                $incident = [
                    'id' => (string) Str::ulid(), 'feature' => $feature,
                    'exception' => $exception::class,
                    'location' => $this->location($exception->getFile()).':'.$exception->getLine(),
                    'trace' => array_map(fn ($frame) => $this->location($frame['file'] ?? '').':'.($frame['line'] ?? 0).' '.($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? ''), array_slice($exception->getTrace(), 0, 12)),
                    'context' => array_intersect_key($context, array_flip(['route', 'action', 'user_id', 'turn_id'])),
                    'first_seen_at' => now()->toIso8601String(), 'count' => 0,
                    'resolved_at' => null, 'notified_at' => null, 'last_notification_attempt_at' => null,
                ];
            }
            $incident['last_seen_at'] = now()->toIso8601String();
            $incident['count']++;
            $state[$feature] = $incident;

            return $incident;
        });
        Log::error('Application incident recorded.', ['incident_id' => $incident['id'], 'feature' => $feature, 'exception_class' => $exception::class]);

        return $incident;
    }

    public function reportRequest(Throwable $exception, Request $request): void
    {
        if ($request->attributes->get('reported_incident_exception') === $exception) {
            return;
        }
        $request->attributes->set('reported_incident_exception', $exception);
        try {
            $incident = $this->record($exception, $request->attributes->get('incident_feature'), [
                'route' => $request->route()?->getName(), 'action' => $request->attributes->get('incident_action'),
                'user_id' => Auth::guard()->hasUser() ? Auth::id() : null,
            ]);
            if ($incident) {
                $request->attributes->set('application_incident', $incident);
            }
        } catch (Throwable $failure) {
            // Monitoring must never replace or recursively report the original failure.
            error_log('DreamersPalette incident recording failed: '.$failure::class);
        }
    }

    public function active(?string $feature): ?array
    {
        if (! $feature || ! config('incidents.enabled')) {
            return null;
        }

        try {
            return $this->change(fn (array &$state) => isset($state[$feature]) && ! $state[$feature]['resolved_at'] ? $state[$feature] : null);
        } catch (Throwable $failure) {
            // An unavailable monitoring store must not take healthy features down.
            error_log('DreamersPalette incident status lookup failed: '.$failure::class);

            return null;
        }
    }

    public function all(): array
    {
        return $this->change(function (array &$state): array {
            $history = $state['_history'] ?? [];

            return array_merge($history, array_values(array_diff_key($state, ['_history' => true])));
        });
    }

    public function reportBackground(Throwable $exception, string $feature, int $turnId): void
    {
        request()->attributes->set('reported_incident_exception', $exception);
        if ($exception instanceof ValidationException
            || $exception instanceof ModelNotFoundException
            || $exception instanceof AuthorizationException
            || $exception::class === RuntimeException::class
            || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500)) {
            return;
        }
        try {
            $incident = $this->record($exception, $feature, ['turn_id' => $turnId]);
            if ($incident) {
                request()->attributes->set('application_incident', $incident);
            }
        } catch (Throwable $failure) {
            error_log('DreamersPalette background incident recording failed: '.$failure::class);
        }
    }

    public function resolve(string $id): bool
    {
        return $this->change(function (array &$state) use ($id): bool {
            foreach ($state as &$incident) {
                if (! isset($incident['id'])) {
                    continue;
                }
                if ($incident['id'] === $id && ! $incident['resolved_at']) {
                    $incident['resolved_at'] = now()->toIso8601String();
                    Log::notice('Application incident resolved.', ['incident_id' => $id, 'feature' => $incident['feature']]);

                    return true;
                }
            }

            return false;
        });
    }

    public function notify(): int
    {
        // Scheduler and manual delivery must not send the same pending alert together.
        $this->all();
        $lock = fopen(config('incidents.path').'.notify.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) {
                fclose($lock);
            }

            return 0;
        }
        try {
            return $this->deliverPending();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function deliverPending(): int
    {
        $recipient = config('incidents.email');
        $mailer = config('incidents.mailer');
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || ! in_array(config('mail.mailers.'.$mailer.'.transport'), ['smtp', 'sendmail', 'ses', 'postmark', 'resend', 'mailgun'], true)) {
            return 0;
        }
        $sent = 0;
        foreach ($this->all() as $incident) {
            if ($incident['resolved_at'] || $incident['notified_at'] || ($incident['last_notification_attempt_at'] && now()->diffInSeconds($incident['last_notification_attempt_at'], true) < 300)) {
                continue;
            }
            $this->change(function (array &$state) use ($incident): void {
                if ($state[$incident['feature']]['id'] === $incident['id']) {
                    $state[$incident['feature']]['last_notification_attempt_at'] = now()->toIso8601String();
                }
            });
            try {
                $body = implode("\n", [
                    'Incident: '.$incident['id'], 'Feature: '.$incident['feature'],
                    'Environment: '.app()->environment(), 'Application: '.config('app.url'),
                    'Exception: '.$incident['exception'], 'Location: '.$incident['location'],
                    'First seen: '.CarbonImmutable::parse($incident['first_seen_at'])->asDateTime(),
                    'Occurrences: '.$incident['count'], 'Context: '.json_encode($incident['context']),
                    'Trace (arguments and exception messages omitted):', ...$incident['trace'],
                    '', 'Investigate and deploy a fix, then run: php artisan dreamers:incidents resolve '.$incident['id'],
                ]);
                Mail::mailer($mailer)->raw($body, fn ($message) => $message->to($recipient)->subject('DreamersPalette error: '.$incident['feature'].' ['.$incident['id'].']'));
                $this->change(function (array &$state) use ($incident): void {
                    if ($state[$incident['feature']]['id'] === $incident['id']) {
                        $state[$incident['feature']]['notified_at'] = now()->toIso8601String();
                    }
                });
                $sent++;
            } catch (Throwable $failure) {
                Log::error('Incident email delivery failed; scheduler will retry.', ['incident_id' => $incident['id'], 'exception_class' => $failure::class]);
            }
        }

        return $sent;
    }

    public function publicNotice(array $incident): array
    {
        return [
            'id' => $incident['id'], 'feature' => $incident['feature'],
            'message' => 'This feature has encountered a problem and is paused until a fix is released. Stop working in this feature. Keep this page open or copy any unsaved writing before leaving. Other features remain available.',
        ];
    }

    public function isStaleClient(Throwable $exception): bool
    {
        return $exception instanceof CorruptComponentPayloadException
            || $exception instanceof MethodNotFoundException
            || $exception instanceof PublicPropertyNotFoundException
            || $exception instanceof CannotUpdateLockedPropertyException;
    }

    private function location(string $path): string
    {
        return str_starts_with($path, base_path().'/') ? substr($path, strlen(base_path()) + 1) : basename($path);
    }

    private function change(Closure $callback): mixed
    {
        $path = config('incidents.path');
        $directory = dirname($path);
        if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Cannot create incident storage.');
        }
        $lock = fopen($path.'.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('Cannot lock incident storage.');
        }
        try {
            $state = is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
            $before = $state;
            $answer = $callback($state);
            if ($state !== $before) {
                $temporary = tempnam($directory, 'incident-');
                try {
                    if (file_put_contents($temporary, json_encode($state, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)) === false || ! rename($temporary, $path)) {
                        throw new RuntimeException('Cannot save incident storage.');
                    }
                } finally {
                    if (is_file($temporary)) {
                        unlink($temporary);
                    }
                }
            }

            return $answer;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
