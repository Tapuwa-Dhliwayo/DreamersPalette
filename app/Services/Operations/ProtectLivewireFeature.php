<?php

namespace App\Services\Operations;

use Livewire\ComponentHook;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ProtectLivewireFeature extends ComponentHook
{
    public function hydrate(): void
    {
        $this->guard();
    }

    public function call(string $method): void
    {
        request()->attributes->set('incident_action', $method);
        $this->guard();
    }

    public function render(): void
    {
        $this->identify();
    }

    public function exception(): void
    {
        $this->identify();
    }

    private function identify(): ?string
    {
        $feature = app(FeatureScope::class)->forComponent($this->component);
        if ($feature) {
            request()->attributes->set('incident_feature', $feature);
        }

        return $feature;
    }

    private function guard(): void
    {
        if ($incident = app(IncidentMonitor::class)->active($this->identify())) {
            request()->attributes->set('application_incident', $incident);
            throw new ServiceUnavailableHttpException(null, 'This feature is paused until a fix is released.');
        }
    }
}
