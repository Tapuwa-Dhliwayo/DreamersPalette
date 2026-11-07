<p class="chronology-scale">Rows follow the shared sequence. An event involving several characters appears in each participant’s lane.</p>
<div class="character-timeline" style="--timeline-lanes:{{ max(1, $timelineLanes->count()) + ($entries->contains(fn ($event) => $event->people->isEmpty()) ? 1 : 0) }}">
    <div class="character-timeline-heading" aria-hidden="true"><span>Sequence</span>@foreach($timelineLanes as $person)<strong>{{ $person->name }}</strong>@endforeach @if($entries->contains(fn ($event) => $event->people->isEmpty()))<strong>No participants</strong>@endif</div>
    @forelse($entries as $event)
        <section class="character-timeline-row" wire:key="lane-event-{{ $event->id }}" aria-label="{{ $event->title }}">
            <div class="character-timeline-date"><span>Sequence {{ $event->position + 0 }}</span><span>{{ $event->calendarLabel() ?: ($event->date_label ?: 'Undated') }}</span></div>
            @foreach($timelineLanes as $person)
                <div @class(['character-timeline-cell', 'is-empty' => !$event->people->contains('id', $person->id)])>
                    @if($event->people->contains('id', $person->id))
                        <flux:button class="character-timeline-event" :variant="$selected === $event->id ? 'primary' : 'outline'" x-on:click="$wire.select({{ $event->id }}).then(() => { if (window.matchMedia('(max-width: 760px)').matches) document.getElementById('studio-main').scrollIntoView({ behavior: 'smooth', block: 'start' }); })" :aria-pressed="$selected === $event->id ? 'true' : 'false'"><span><small>{{ $person->name }}</small><strong>{{ $event->title }}</strong></span></flux:button>
                    @endif
                </div>
            @endforeach
            @if($entries->contains(fn ($listedEvent) => $listedEvent->people->isEmpty()))
                <div @class(['character-timeline-cell', 'is-empty' => $event->people->isNotEmpty()])>@if($event->people->isEmpty())<flux:button class="character-timeline-event" x-on:click="$wire.select({{ $event->id }}).then(() => { if (window.matchMedia('(max-width: 760px)').matches) document.getElementById('studio-main').scrollIntoView({ behavior: 'smooth', block: 'start' }); })" :aria-pressed="$selected === $event->id ? 'true' : 'false'"><span><small>No participants</small><strong>{{ $event->title }}</strong></span></flux:button>@endif</div>
            @endif
        </section>
    @empty
        <p class="world-empty">No matching events</p>
    @endforelse
</div>
