<?php

namespace App\Livewire\Studio;

use App\Models\AssistantRequest;
use App\Models\AssistantTurn;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssistantActivity extends Component
{
    private function turns()
    {
        abort_unless(auth()->user()?->can('worlds.update'), 403);

        return AssistantTurn::forAuthor(auth()->id());
    }

    public function refreshActivity(): void
    {
        $cutoff = now()->subSeconds(config('worldbuilding.timeout') + 60);
        $ids = $this->turns()->active()->where('updated_at', '<', $cutoff)->limit(100)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $cutoff): void {
                $turn = $this->turns()->whereKey($id)->lockForUpdate()->first();
                if (! $turn || ! $turn->isActive() || $turn->updated_at >= $cutoff) {
                    return;
                }
                $turn->update([
                    'status' => 'failed',
                    'error' => 'The response was interrupted. Your question and reading are saved. Open the exercise to retry.',
                    'finished_at' => now(),
                ]);
                AssistantRequest::where('user_id', auth()->id())
                    ->where('turn_id', $turn->id)
                    ->where('run_uuid', $turn->run_uuid)
                    ->where('status', 'running')
                    ->update(['status' => 'interrupted', 'finished_at' => now()]);
            }, 3);
        }
    }

    public function acknowledge(int $id): void
    {
        $turn = $this->turns()->findOrFail($id);
        $this->authorize('update', $turn->exercise);
        if (! $turn->isActive()) {
            $turn->update(['acknowledged_at' => now()]);
        }
    }

    public function render()
    {
        $turns = $this->turns()
            ->with('exercise')
            ->where(fn ($query) => $query->active()->orWhereNull('acknowledged_at'))
            ->latest('updated_at')
            ->get();

        return view('livewire.studio.assistant-activity', [
            'activeCount' => $turns->filter(fn ($turn) => $turn->isActive())->count(),
            'unreadCount' => $turns->reject(fn ($turn) => $turn->isActive())->count(),
            'activity' => $turns->take(20),
        ]);
    }
}
