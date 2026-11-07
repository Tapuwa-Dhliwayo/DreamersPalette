<?php

namespace App\Livewire\Studio;

use App\Models\AssistantTurn;
use Livewire\Component;

class AssistantActivity extends Component
{
    private function turns()
    {
        abort_unless(auth()->user()?->canAny(['worlds.update', 'poems.update']), 403);

        return AssistantTurn::forAuthor(auth()->id());
    }

    public function refreshActivity(): void
    {
        AssistantTurn::expireStalled($this->turns());
    }

    public function acknowledge(int $id): void
    {
        $turn = $this->turns()->findOrFail($id);
        $this->authorize('update', $turn->subject());
        if (! $turn->isActive()) {
            $turn->update(['acknowledged_at' => now()]);
        }
    }

    public function render()
    {
        $this->turns();
        $turns = AssistantTurn::outstandingFor(auth()->user());

        return view('livewire.studio.assistant-activity', [
            'activeCount' => $turns->filter(fn ($turn) => $turn->isActive())->count(),
            'unreadCount' => $turns->reject(fn ($turn) => $turn->isActive())->count(),
            'activity' => $turns->take(20),
        ]);
    }
}
