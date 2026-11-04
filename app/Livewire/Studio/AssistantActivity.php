<?php

namespace App\Livewire\Studio;

use App\Models\AssistantTurn;
use Livewire\Component;

class AssistantActivity extends Component
{
    private function turns()
    {
        abort_unless(auth()->user()?->can('worlds.update'), 403);

        return AssistantTurn::whereHas('exercise.world', fn ($query) => $query->where('author_id', auth()->id()));
    }

    public function refreshActivity(): void
    {
        $this->turns()->whereIn('status', ['queued', 'running'])->where('updated_at', '<', now()->subSeconds(config('worldbuilding.timeout') + 60))->update(['status' => 'failed', 'error' => 'The response was interrupted. Your question and reading are saved. Open the exercise to retry.', 'finished_at' => now()]);
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
        $turns = $this->turns()->with('exercise')->where(fn ($query) => $query->whereIn('status', ['queued', 'running'])->orWhereNull('acknowledged_at'))->latest('updated_at')->get();

        return view('livewire.studio.assistant-activity', ['activeCount' => $turns->filter(fn ($turn) => $turn->isActive())->count(), 'unreadCount' => $turns->reject(fn ($turn) => $turn->isActive())->count(), 'activity' => $turns->take(20)]);
    }
}
