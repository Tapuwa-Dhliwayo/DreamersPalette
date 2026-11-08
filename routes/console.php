<?php

use App\Models\ManuscriptCompile;
use App\Services\Operations\IncidentMonitor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('dreamers:prune-compiles', function (): void {
    $removed = 0;
    ManuscriptCompile::where('expires_at', '<=', now())->orderBy('id')->chunkById(100, function ($compiles) use (&$removed): void {
        foreach ($compiles as $compile) {
            if ($compile->path && ! Storage::disk('local')->delete($compile->path)) {
                $this->warn('Could not remove expired export '.$compile->id.'. It will be retried.');

                continue;
            }
            $compile->delete();
            $removed++;
        }
    });
    $this->info($removed.' expired export copies removed. Manuscripts and finished drafts were retained.');
})->purpose('Remove expired temporary private manuscript export files and manifests');

Schedule::command('dreamers:prune-compiles')->daily()->withoutOverlapping();

Artisan::command('dreamers:incidents {operation=list : list, notify, or resolve} {reference?}', function (): int {
    $monitor = app(IncidentMonitor::class);
    switch ($this->argument('operation')) {
        case 'list':
            $this->table(['Reference', 'Feature', 'Occurrences', 'State', 'Email'], array_map(fn ($incident) => [
                $incident['id'], $incident['feature'], $incident['count'],
                $incident['resolved_at'] ? 'Resolved' : 'Paused', $incident['notified_at'] ? 'Sent' : 'Not delivered',
            ], $monitor->all()));
            if (! config('incidents.email') || in_array(config('incidents.mailer'), ['log', 'array'], true)) {
                $this->warn('Email alerts are not delivering. Configure INCIDENT_ALERT_TO and a delivering INCIDENT_ALERT_MAILER.');
            }

            return 0;
        case 'notify':
            $this->info($monitor->notify().' incident alert(s) delivered.');

            return 0;
        case 'resolve':
            if (! $this->argument('reference') || ! $monitor->resolve($this->argument('reference'))) {
                $this->error('Choose an open incident reference from dreamers:incidents list.');

                return 1;
            }
            $this->info('Incident resolved; feature actions are available again.');

            return 0;
        default:
            $this->error('Use list, notify, or resolve.');

            return 1;
    }
})->purpose('Inspect incidents, deliver pending alerts, or resume a feature after its fix');

Schedule::command('dreamers:incidents notify')->everyMinute()->withoutOverlapping();
