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
                $incident = $this->openIncident($exception, $feature, $context);
            }
            $incident = $this->withCurrentKeys($incident);
            $incident['last_seen_at'] = now()->toIso8601String();
            $incident['count']++;
            $incident['latest_diagnostics'] = $this->diagnostics($exception, $context);
            $incident['occurrences'] = $this->recentOccurrences($incident, $exception, $context['user_id'] ?? null);
            if (! $incident['paused_at'] && $this->reachesPauseThreshold($incident['occurrences'])) {
                $incident['paused_at'] = now()->toIso8601String();
                $incident['pause_cause'] = $incident['latest_diagnostics'];
            }
            $state[$feature] = $incident;

            return $incident;
        });
        Log::error('Application incident recorded.', ['incident_id' => $incident['id'], 'feature' => $feature, 'exception_class' => $exception::class]);

        return $incident;
    }

    private function openIncident(Throwable $exception, string $feature, array $context): array
    {
        return [
            'id' => (string) Str::ulid(), 'feature' => $feature,
            ...$this->diagnostics($exception, $context),
            'first_seen_at' => now()->toIso8601String(), 'count' => 0,
            'resolved_at' => null, 'notified_at' => null, 'last_notification_attempt_at' => null,
            'paused_at' => null, 'pause_notified_at' => null, 'last_pause_notification_attempt_at' => null,
            'occurrences' => [],
        ];
    }

    private function diagnostics(Throwable $exception, array $context): array
    {
        return [
            'exception' => $exception::class,
            'location' => $this->signature($exception, false),
            'trace' => array_map(fn ($frame) => $this->location($frame['file'] ?? '').':'.($frame['line'] ?? 0).' '.($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? ''), array_slice($exception->getTrace(), 0, 12)),
            'context' => array_intersect_key($context, array_flip(['route', 'action', 'user_id', 'turn_id'])),
        ];
    }

    // Incidents stored before the pause threshold existed were paused from their first error.
    private function withCurrentKeys(array $incident): array
    {
        if (! array_key_exists('paused_at', $incident)) {
            $incident['paused_at'] = $incident['first_seen_at'];
            $incident['pause_notified_at'] = $incident['notified_at'];
        }

        return $incident + ['occurrences' => [], 'pause_notified_at' => null, 'last_pause_notification_attempt_at' => null];
    }

    private function recentOccurrences(array $incident, Throwable $exception, ?int $userId): array
    {
        $occurrences = $incident['occurrences'];
        $occurrences[] = ['at' => now()->toIso8601String(), 'user_id' => $userId, 'signature' => $this->signature($exception)];
        $windowStart = now()->subMinutes(config('incidents.pause_window_minutes'));

        $retained = max(50, config('incidents.pause_after_occurrences'), config('incidents.pause_after_users'));

        return array_slice(array_values(array_filter($occurrences, fn (array $occurrence) => CarbonImmutable::parse($occurrence['at'])->greaterThanOrEqualTo($windowStart))), -$retained);
    }

    private function reachesPauseThreshold(array $recentOccurrences): bool
    {
        if (count($recentOccurrences) >= config('incidents.pause_after_occurrences')) {
            return true;
        }
        $usersBySignature = [];
        foreach ($recentOccurrences as $occurrence) {
            if ($occurrence['user_id']) {
                $usersBySignature[$occurrence['signature']][$occurrence['user_id']] = true;
            }
        }

        return collect($usersBySignature)->contains(fn (array $users) => count($users) >= config('incidents.pause_after_users'));
    }

    private function signature(Throwable $exception, bool $withClass = true): string
    {
        $where = $this->location($exception->getFile()).':'.$exception->getLine();

        return $withClass ? $exception::class.' '.$where : $where;
    }

    public function state(array $incident): string
    {
        return match (true) {
            (bool) $incident['resolved_at'] => 'resolved',
            $this->isPaused($incident) => 'paused',
            default => 'watching',
        };
    }

    private function isPaused(array $incident): bool
    {
        // Incidents stored before the threshold existed have no key and were paused.
        return array_key_exists('paused_at', $incident) ? $incident['paused_at'] !== null : true;
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
            return $this->change(fn (array &$state) => isset($state[$feature]) && $this->state($state[$feature]) === 'paused' ? $state[$feature] : null);
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

    public function paused(): array
    {
        return array_values(array_filter($this->all(), fn (array $incident) => $this->state($incident) === 'paused'));
    }

    public function alertsAreConfigured(): bool
    {
        return filter_var(config('incidents.email'), FILTER_VALIDATE_EMAIL) !== false
            && in_array(config('mail.mailers.'.config('incidents.mailer').'.transport'), ['smtp', 'sendmail', 'ses', 'postmark', 'resend', 'mailgun'], true);
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
            $incident = $this->record($exception, $feature, ['turn_id' => $turnId, 'user_id' => Auth::guard()->hasUser() ? Auth::id() : null]);
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
                if (isset($incident['id']) && $incident['id'] === $id && ! $incident['resolved_at']) {
                    $this->markResolved($incident);

                    return true;
                }
            }

            return false;
        });
    }

    public function pause(string $id): bool
    {
        return $this->change(function (array &$state) use ($id): bool {
            foreach ($state as &$incident) {
                if (isset($incident['id']) && $incident['id'] === $id && $this->state($incident) === 'watching') {
                    $incident = $this->withCurrentKeys($incident);
                    $incident['paused_at'] = now()->toIso8601String();
                    $incident['pause_cause'] = $incident['latest_diagnostics'] ?? array_intersect_key($incident, array_flip(['exception', 'location', 'trace', 'context']));
                    Log::notice('Application incident paused by an operator.', ['incident_id' => $id, 'feature' => $incident['feature']]);

                    return true;
                }
            }

            return false;
        });
    }

    // Used after a deploy: a fix that did not work re-pauses through the threshold.
    public function release(): int
    {
        return $this->change(function (array &$state): int {
            $released = 0;
            foreach ($state as $feature => &$incident) {
                if ($feature !== '_history' && ! $incident['resolved_at']) {
                    $this->markResolved($incident);
                    $released++;
                }
            }

            return $released;
        });
    }

    private function markResolved(array &$incident): void
    {
        $incident['resolved_at'] = now()->toIso8601String();
        Log::notice('Application incident resolved.', ['incident_id' => $incident['id'], 'feature' => $incident['feature']]);
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
        if (! $this->alertsAreConfigured()) {
            return 0;
        }
        $sent = 0;
        foreach ($this->all() as $incident) {
            $alert = $this->pendingAlert($incident);
            if (! $alert) {
                continue;
            }
            [$notifiedKey, $attemptKey] = match ($alert) {
                'opened' => ['notified_at', 'last_notification_attempt_at'],
                'paused' => ['pause_notified_at', 'last_pause_notification_attempt_at'],
            };
            $this->markIncident($incident, [$attemptKey => now()->toIso8601String()]);
            try {
                Mail::mailer($mailer)->raw($this->alertBody($incident), fn ($message) => $message->to($recipient)->subject($this->alertSubject($incident, $alert)));
                $delivered = [$notifiedKey => now()->toIso8601String()];
                // An incident already paused when first alerted is covered by that single email.
                $this->markIncident($incident, $alert === 'opened' && $this->isPaused($incident) ? $delivered + ['pause_notified_at' => now()->toIso8601String()] : $delivered);
                $sent++;
            } catch (Throwable $failure) {
                Log::error('Incident email delivery failed; scheduler will retry.', ['incident_id' => $incident['id'], 'exception_class' => $failure::class]);
            }
        }

        return $sent;
    }

    private function pendingAlert(array $incident): ?string
    {
        if ($incident['resolved_at']) {
            return null;
        }
        $alert = match (true) {
            ! $incident['notified_at'] => 'opened',
            // Legacy paused incidents have no pause_notified_at key and were already alerted.
            $this->isPaused($incident) && array_key_exists('pause_notified_at', $incident) && ! $incident['pause_notified_at'] => 'paused',
            default => null,
        };
        $attemptKey = $alert === 'paused' ? 'last_pause_notification_attempt_at' : 'last_notification_attempt_at';
        $lastAttempt = $incident[$attemptKey] ?? null;

        return $alert && $lastAttempt && now()->diffInSeconds($lastAttempt, true) < 300 ? null : $alert;
    }

    private function markIncident(array $incident, array $values): void
    {
        $this->change(function (array &$state) use ($incident, $values): void {
            if ($state[$incident['feature']]['id'] === $incident['id']) {
                $state[$incident['feature']] = $values + $state[$incident['feature']];
            }
        });
    }

    private function alertSubject(array $incident, string $alert): string
    {
        $prefix = match ($alert) {
            'opened' => $this->isPaused($incident) ? 'DreamersPalette feature paused: ' : 'DreamersPalette error being watched: ',
            'paused' => 'DreamersPalette feature paused: ',
        };

        return $prefix.$incident['feature'].' ['.$incident['id'].']';
    }

    private function alertBody(array $incident): string
    {
        $state = $this->state($incident);
        $diagnostics = $incident['pause_cause'] ?? $incident;
        $next = match ($state) {
            'paused' => 'The feature is paused for all users. Investigate and deploy a fix; a deploy runs dreamers:incidents release, or resume it with: php artisan dreamers:incidents resolve '.$incident['id'],
            'watching' => 'The feature is still available. It pauses if the error repeats. To pause it now: Studio > Incidents, or investigate and run: php artisan dreamers:incidents resolve '.$incident['id'],
        };

        return implode("\n", [
            'State: '.ucfirst($state),
            'Incident: '.$incident['id'], 'Feature: '.$incident['feature'],
            'Environment: '.app()->environment(), 'Application: '.config('app.url'),
            'Exception: '.$diagnostics['exception'], 'Location: '.$diagnostics['location'],
            'First seen: '.CarbonImmutable::parse($incident['first_seen_at'])->asDateTime(),
            'Occurrences: '.$incident['count'], 'Context: '.json_encode($diagnostics['context']),
            'Trace (arguments and exception messages omitted):', ...$diagnostics['trace'],
            '', $next,
        ]);
    }

    public function publicNotice(array $incident): array
    {
        $paused = $this->isPaused($incident);

        return [
            'id' => $incident['id'], 'feature' => $incident['feature'], 'paused' => $paused,
            'message' => $paused
                ? 'This feature has encountered a problem and is paused until a fix is released. Stop working in this feature. Keep this page open or copy any unsaved writing before leaving. Other features remain available.'
                : 'This request failed and has been reported. Copy any unsaved writing before retrying.',
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
