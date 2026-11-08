<?php

namespace App\Livewire\Studio;

use App\Services\Operations\IncidentMonitor;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.studio')]
class Incidents extends Component
{
    public string $notice = '';

    public function mount(): void
    {
        $this->authorize('incidents.manage');
    }

    public function resolve(string $id, IncidentMonitor $monitor): void
    {
        $this->authorize('incidents.manage');
        $this->notice = $monitor->resolve($id)
            ? 'Incident resolved. The feature is available again.'
            : 'That incident is no longer open.';
    }

    public function pause(string $id, IncidentMonitor $monitor): void
    {
        $this->authorize('incidents.manage');
        $this->notice = $monitor->pause($id)
            ? 'Feature paused for all users.'
            : 'That incident is not being watched.';
    }

    public function render(IncidentMonitor $monitor)
    {
        $incidents = collect($monitor->all())->sortByDesc('last_seen_at');

        return view('livewire.studio.incidents', [
            'open' => $incidents->reject(fn (array $incident) => $incident['resolved_at'])->values(),
            'resolved' => $incidents->filter(fn (array $incident) => $incident['resolved_at'])->sortByDesc('resolved_at')->take(15)->values(),
            'alertsConfigured' => $monitor->alertsAreConfigured(),
            'monitor' => $monitor,
        ]);
    }
}
