<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\AssistantTurn;
use Illuminate\Support\Facades\Gate;

class AssistantActivityController extends Controller
{
    public function index()
    {
        AssistantTurn::expireStalled(AssistantTurn::forAuthor(auth()->id()));
        $turns = AssistantTurn::outstandingFor(auth()->user());

        return response()->json($turns->map(fn ($turn): array => [
            ...$turn->only(['id', 'status', 'progress', 'error']),
            'title' => $turn->activityTitle(),
            'url' => $turn->activityUrl(),
        ])->values());
    }

    public function acknowledge(int $turn)
    {
        $record = AssistantTurn::forAuthor(auth()->id())->findOrFail($turn);
        Gate::authorize('update', $record->subject());
        if (! $record->isActive()) {
            $record->update(['acknowledged_at' => now()]);
        }

        return response()->noContent();
    }
}
