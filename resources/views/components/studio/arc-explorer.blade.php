@props(['arc', 'beats', 'world', 'archived' => false])
@php
    $count = $beats->count();
    $rated = $beats->whereNotNull('intensity');
    $first = $beats->first();
    $points = $beats->values()->map(fn ($beat, $index) => [
        'beat' => $beat, 'step' => $index + 1,
        'x' => $count === 1 ? 460 : 60 + $index * 800 / ($count - 1),
        'y' => $beat->intensity === null ? null : 264 - $beat->intensity * 2.16,
    ]);
    $segments = []; $segment = [];
    foreach ($points as $point) {
        if ($point['y'] === null) {
            if ($segment) $segments[] = $segment;
            $segment = [];
            continue;
        }
        $segment[] = $point['x'].','.$point['y'];
    }
    if ($segment) $segments[] = $segment;
    $accent = ['#9b651c', '#27766f', '#805375'][($arc->person_id ?? $arc->id) % 3];
@endphp
<section class="arc-explorer" style="--arc-ink: {{ $accent }}" x-data="{ beat: {{ $first?->id ?? 'null' }} }" wire:key="arc-explorer-{{ $arc->id }}-{{ $archived ? 'archived' : 'active' }}-{{ $beats->sum('revision') }}">
    <div class="arc-scoreboard">
        <div><span class="arc-eyebrow">Latest rated beat</span><p class="arc-score">{{ $rated->last()?->intensity ?? '—' }}<small>/ 100</small></p></div>
        <div class="arc-score-context"><span>{{ $count }} {{ Str::plural('beat', $count) }} · {{ $rated->count() }} rated</span>@if($rated->isNotEmpty())<span>Opening {{ $rated->first()->intensity }} <span aria-hidden="true">→</span> Latest {{ $rated->last()->intensity }} · Range {{ $rated->min('intensity') }}–{{ $rated->max('intensity') }}</span>@endif</div>
    </div>
    <div class="arc-plot-scroll" tabindex="0" aria-label="Scrollable arc graph">
        <svg class="arc-plot" viewBox="0 0 920 316" role="group" aria-label="{{ $arc->measure }} by beat order, from zero to one hundred">
            @foreach([0,25,50,75,100] as $value)
                <line x1="60" x2="860" y1="{{ 264 - $value * 2.16 }}" y2="{{ 264 - $value * 2.16 }}" class="arc-grid" />
                <text x="40" y="{{ 269 - $value * 2.16 }}" class="arc-axis" text-anchor="end">{{ $value }}</text>
            @endforeach
            @foreach($segments as $line)
                @if(count($line) > 1)<polyline points="{{ explode(',', $line[0])[0] }},264 {{ implode(' ', $line) }} {{ explode(',', end($line))[0] }},264" class="arc-wash" />@endif
                <polyline points="{{ implode(' ', $line) }}" class="arc-line" />
            @endforeach
            @foreach($points as $point)
                @php($beat = $point['beat'])
                <g role="button" tabindex="0" aria-label="Beat {{ $point['step'] }}: {{ $beat->title }}, {{ $beat->intensity === null ? 'unrated' : $beat->intensity.' out of 100' }}" :aria-pressed="beat === {{ $beat->id }}" @click="beat = {{ $beat->id }}" @keydown.enter.prevent="beat = {{ $beat->id }}" @keydown.space.prevent="beat = {{ $beat->id }}" class="arc-node" :class="{ 'is-selected': beat === {{ $beat->id }} }">
                    <title>{{ $beat->title }} · {{ $beat->intensity ?? 'Unrated' }}</title>
                    <line x1="{{ $point['x'] }}" x2="{{ $point['x'] }}" y1="{{ $point['y'] ?? 264 }}" y2="264" class="arc-guide" />
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] ?? 264 }}" r="19" class="arc-hit" />
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] ?? 264 }}" r="7" @class(['arc-dot', 'is-unrated' => $point['y'] === null]) />
                    <text x="{{ $point['x'] }}" y="{{ ($point['y'] ?? 264) - 17 }}" text-anchor="middle" class="arc-value">{{ $beat->intensity ?? '?' }}</text>
                    <text x="{{ $point['x'] }}" y="291" text-anchor="middle" class="arc-step">{{ $point['step'] }}</text>
                </g>
            @endforeach
            <text x="860" y="313" text-anchor="end" class="arc-axis">Beat order →</text>
        </svg>
    </div>
    <nav class="arc-beat-strip" aria-label="Choose an arc beat">
        @foreach($points as $point)
            <flux:button variant="ghost" class="arc-beat-choice" x-bind:class="{ 'is-selected': beat === {{ $point['beat']->id }} }" x-bind:aria-pressed="beat === {{ $point['beat']->id }}" x-on:click="beat = {{ $point['beat']->id }}"><span class="arc-beat-number">{{ $point['step'] }}</span>{{ $point['beat']->title }}</flux:button>
        @endforeach
    </nav>
    @foreach($points as $point)
        @php($beat = $point['beat'])
        <article class="arc-beat-detail" x-show="beat === {{ $beat->id }}" @if(!$loop->first) x-cloak @endif aria-label="Beat details" wire:key="arc-detail-{{ $beat->id }}">
            <div class="arc-detail-heading"><div><span class="arc-eyebrow">Beat {{ $point['step'] }} of {{ $count }}</span><h3>{{ $beat->title }}</h3></div><span class="arc-detail-value">{{ $beat->intensity ?? '—' }}<small>/100</small></span></div>
            @if($beat->event)<a class="development-event-link" href="{{ route('studio.world.develop', ['world' => $world, 'section' => 'timeline', 'selected' => $beat->event_id]) }}">{{ $beat->event->title }} <span aria-hidden="true">↗</span></a>@endif
            <x-studio.markdown :text="$beat->notes_md ?? ''" />
            @can('update', $world)@if(!$arc->trashed())<div class="arc-detail-actions">@if(!$archived)<flux:button icon="pencil-square" size="sm" wire:click="edit('beat', {{ $beat->id }})">Edit beat</flux:button>@endif<flux:button variant="ghost" size="sm" :icon="$beat->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveChild('beat', {{ $beat->id }}, {{ $beat->revision }}, {{ $beat->trashed() ? 'false' : 'true' }})">{{ $beat->trashed() ? 'Restore' : 'Archive' }}</flux:button></div>@endif@endcan
        </article>
    @endforeach
</section>
