<?php

namespace App\Http\Middleware;

use App\Services\Operations\FeatureScope;
use App\Services\Operations\IncidentMonitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ProtectPausedFeature
{
    public function handle(Request $request, Closure $next)
    {
        $feature = app(FeatureScope::class)->forRequest($request);
        $request->attributes->set('incident_feature', $feature);
        if (! $request->isMethodSafe() && $incident = app(IncidentMonitor::class)->active($feature)) {
            $request->attributes->set('application_incident', $incident);
            throw new ServiceUnavailableHttpException(null, 'This feature is paused until a fix is released.');
        }

        return $next($request);
    }
}
