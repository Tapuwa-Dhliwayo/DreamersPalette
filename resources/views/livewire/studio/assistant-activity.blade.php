<div class="studio-assistant-activity" wire:poll.keep-alive.10s="refreshActivity" x-data="{ open: false }" x-on:click.outside="open=false">
    @php($statusText = 'Studio activity · '.$activeCount.' working'.($unreadCount ? ' · '.$unreadCount.' '.($unreadCount === 1 ? 'update' : 'updates') : ''))
    <button type="button" x-on:click="open=!open" x-on:keydown.escape.window="open=false" :aria-expanded="open" aria-controls="studio-activity-list" aria-live="polite" aria-label="{{ $statusText }}" title="{{ $statusText }}">@if($activeCount > 0)<span @class(['sandbox-working-indicator' => $activeCount > 0]) aria-hidden="true"></span>@else<flux:icon.sparkles variant="micro" aria-hidden="true" />@endif<span class="studio-activity-label">Activity</span> @if($activeCount + $unreadCount > 0)<span class="studio-activity-count">{{ $activeCount + $unreadCount }}</span>@endif</button>
    <section id="studio-activity-list" x-show="open" x-cloak aria-label="Assistant activity">
        <h2>Assistant activity</h2>
        <p>Responses continue when you leave. Updates are saved here.</p>
        <div class="studio-activity-status" role="status" aria-live="polite">{{ $activeCount }} working · {{ $unreadCount }} need review</div>
        @forelse($activity as $activityTurn)
            <article wire:key="activity-{{ $activityTurn->id }}">
                <a href="{{ $activityTurn->activityUrl() }}">{{ $activityTurn->activityTitle() }}</a>
                <strong>{{ ['queued'=>'Question saved','running'=>'Working in background','completed'=>'Answer ready','paused'=>'Question pending · continue reading','failed'=>'Needs attention','cancelled'=>'Stopped'][$activityTurn->status] ?? $activityTurn->status }}</strong>
                <p>{{ match($activityTurn->status) { 'completed' => 'Your response is saved in the conversation.', 'paused' => 'Your question is still pending. Open the exercise to continue from saved reading.', 'failed' => $activityTurn->error, 'cancelled' => 'Your question and completed reading remain saved.', default => $activityTurn->progress } }}</p>
                @if(!$activityTurn->isActive())<button type="button" wire:click="acknowledge({{ $activityTurn->id }})">Mark reviewed</button>@endif
            </article>
        @empty<p>No outstanding assistant activity.</p>@endforelse
    </section>
</div>
