<?php

namespace App\Services\Operations;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class IncidentResponse
{
    public function render(Response $response, Throwable $exception, Request $request): Response
    {
        if (app(IncidentMonitor::class)->isStaleClient($exception)) {
            return response()->json(['message' => 'This page is out of date. Copy any unsaved writing, then reload before continuing.'], 409)->header('Cache-Control', 'no-store, private');
        }
        if ($response->getStatusCode() < 500 || ! config('incidents.enabled')) {
            return $response;
        }
        $monitor = app(IncidentMonitor::class);
        if (! $request->attributes->has('application_incident')) {
            $monitor->reportRequest($exception, $request);
        }
        $incident = $request->attributes->get('application_incident');
        $notice = $incident ? $monitor->publicNotice($incident) : [
            'id' => null, 'feature' => $request->attributes->get('incident_feature'),
            'message' => 'This request failed. Stop working in this feature and keep this page open or copy any unsaved writing before leaving. The error could not be recorded automatically; contact the administrator.',
        ];
        $status = $incident ? 503 : $response->getStatusCode();
        $rendered = $request->expectsJson() || $request->hasHeader('X-Livewire') || $request->is('api/*')
            ? response()->json(['message' => $notice['message'], 'incident' => $notice], $status)
            : response()->view('errors.incident', ['notice' => $notice], $status);
        $rendered->headers->set('Cache-Control', 'no-store, private');

        return $rendered;
    }
}
