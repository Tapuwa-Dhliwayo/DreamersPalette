<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Services\Operations\IncidentMonitor;
use Illuminate\Http\JsonResponse;

class IncidentStatusController extends Controller
{
    public function __invoke(IncidentMonitor $monitor): JsonResponse
    {
        return response()->json(['incidents' => array_values(array_map(
            fn ($incident) => $monitor->publicNotice($incident),
            array_filter(config('incidents.enabled') ? $monitor->all() : [], fn ($incident) => ! $incident['resolved_at']),
        ))])->header('Cache-Control', 'no-store, private');
    }
}
