<div class="studio-assistant-activity" wire:poll.keep-alive.10s="refreshActivity" x-data="{ open: false }">
    <button type="button" x-on:click="open=!open" :aria-expanded="open" aria-controls="studio-activity-list" aria-live="polite"><span @class(['sandbox-working-indicator' => $activeCount > 0]) aria-hidden="true"></span>Studio activity · {{ $activeCount }} working @if($unreadCount) · {{ $unreadCount }} {{ $unreadCount === 1 ? 'update' : 'updates' }} @endif</button>
    <section id="studio-activity-list" x-show="open" x-cloak aria-label="Assistant activity">
        <h2>Assistant activity</h2>
        <p>Reading continues when you leave an exercise. Updates are saved here.</p>
        <div role="status" aria-live="polite">{{ $activeCount }} working · {{ $unreadCount }} need review</div>
        @forelse($activity as $activityTurn)
            <article wire:key="activity-{{ $activityTurn->id }}">
                <a href="{{ route('studio.world.sandbox', ['world'=>$activityTurn->exercise->world_id,'exercise'=>$activityTurn->exercise_id]) }}">{{ $activityTurn->exercise->title }}</a>
                <strong>{{ ['queued'=>'Question saved','running'=>'Working in background','completed'=>'Answer ready','paused'=>'Question pending · continue reading','failed'=>'Needs attention','cancelled'=>'Stopped'][$activityTurn->status] ?? $activityTurn->status }}</strong>
                <p>{{ match($activityTurn->status) { 'completed' => 'Your response is saved in the exercise.', 'paused' => 'Your question is still pending. Open the exercise to continue from saved reading.', 'failed' => $activityTurn->error, 'cancelled' => 'Your question and completed reading remain saved.', default => $activityTurn->progress } }}</p>
                @if(!$activityTurn->isActive())<button type="button" wire:click="acknowledge({{ $activityTurn->id }})">Mark reviewed</button>@endif
            </article>
        @empty<p>No outstanding assistant activity.</p>@endforelse
    </section>
</div>
