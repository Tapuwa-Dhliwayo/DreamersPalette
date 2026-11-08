<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Services\Operations\IncidentMonitor;
use Illuminate\Http\JsonResponse;

class IncidentStatusController extends Controller
{
    public function __invoke(IncidentMonitor $monitor): JsonResponse
    {
        $paused = config('incidents.enabled') ? $monitor->paused() : [];

        return response()->json(['incidents' => array_map(fn ($incident) => $monitor->publicNotice($incident), $paused)])->header('Cache-Control', 'no-store, private');
    }
}
