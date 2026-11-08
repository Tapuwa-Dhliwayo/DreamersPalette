@props(['records', 'catalogue', 'selected', 'subjectFilter' => ''])
@php
    $allKeys = $records->flatMap(fn ($link) => [$link->source_key, $link->target_key])->unique()->values();
    $focus = $subjectFilter ?: ($allKeys->count() > 8 ? $records->firstWhere('id', $selected)?->source_key : null);
    $links = $focus ? $records->filter(fn ($link) => $link->source_key === $focus || $link->target_key === $focus) : $records;
    $selectedLink = $links->firstWhere('id', $selected);
    $visibleKeys = $links->flatMap(fn ($link) => [$link->source_key, $link->target_key])->unique();
    if ($selectedLink) { $visibleKeys = collect([$selectedLink->source_key, $selectedLink->target_key])->concat($visibleKeys)->unique(); }
    $visibleKeys = $visibleKeys->take(8)->all();
    $links = $links->filter(fn ($link) => in_array($link->source_key, $visibleKeys, true) && in_array($link->target_key, $visibleKeys, true));
    $links = $links->sortByDesc(fn ($link) => $link->id === $selected)->take(24)->sortBy(fn ($link) => $link->id === $selected);
    $keys = $allKeys->filter(fn ($key) => in_array($key, $visibleKeys, true) && $links->contains(fn ($link) => $link->source_key === $key || $link->target_key === $key))->values();
    $names = [];
    foreach ($links as $link) { $names[$link->source_key] = $catalogue->get($link->source_key)['name'] ?? $link->source_name; $names[$link->target_key] = $catalogue->get($link->target_key)['name'] ?? $link->target_name; }
    $positions = [];
    foreach ($keys as $index => $key) {
        $angle = -M_PI / 2 + 2 * M_PI * $index / max(1, $keys->count());
        $positions[$key] = ['x' => 450 + 290 * cos($angle), 'y' => 290 + 195 * sin($angle)];
    }
    $pairUses = [];
@endphp
<div class="relationship-map" aria-label="Relationship network">
    @if($focus && !$subjectFilter)<p class="relationship-map-scope">Showing connections around {{ $names[$focus] ?? 'the selected subject' }}. Use Connected to to explore another subject.</p>@endif
    @if($links->count() < $records->count())<p class="relationship-map-scope">{{ $links->count() }} of {{ $records->count() }} connections on this map. All matching connections are listed below.</p>@endif
    <svg viewBox="0 0 900 580" role="group" aria-label="Connection map. Arrows show direction; select a numbered connection to inspect it.">
        <defs>
            <marker id="relationship-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="currentColor" /></marker>
        </defs>
        @foreach($links as $link)
            @php
                $source = $positions[$link->source_key]; $target = $positions[$link->target_key];
                $dx = $target['x'] - $source['x']; $dy = $target['y'] - $source['y']; $length = max(1, hypot($dx, $dy));
                $startX = $source['x'] + $dx / $length * 38; $startY = $source['y'] + $dy / $length * 38;
                $endX = $target['x'] - $dx / $length * 38; $endY = $target['y'] - $dy / $length * 38;
                $pairKeys = [$link->source_key, $link->target_key]; sort($pairKeys); $pairKey = implode('|', $pairKeys);
                $lane = $pairUses[$pairKey] ?? 0; $pairUses[$pairKey] = $lane + 1;
                $bend = 36 + floor($lane / 2) * 44;
                if ($lane % 2) { $bend *= -1; }
                $orientation = $link->source_key === $pairKeys[0] ? 1 : -1;
                $controlX = ($startX + $endX) / 2 - $dy / $length * $bend * $orientation;
                $controlY = ($startY + $endY) / 2 + $dx / $length * $bend * $orientation;
                $labelX = ($startX + 2 * $controlX + $endX) / 4; $labelY = ($startY + 2 * $controlY + $endY) / 4;
            @endphp
            <a href="?section=relationships&amp;selected={{ $link->id }}" wire:click.prevent="select({{ $link->id }})" @class(['relationship-map-edge', 'is-selected' => $selected === $link->id]) aria-label="{{ $names[$link->source_key] }} {{ $link->is_mutual ? 'and' : 'to' }} {{ $names[$link->target_key] }}: {{ $link->displayLabel() }}">
                <title>{{ $names[$link->source_key] }} {{ $link->is_mutual ? '↔' : '→' }} {{ $names[$link->target_key] }}: {{ $link->displayLabel() }}</title>
                <path class="relationship-edge-hit" d="M {{ $startX }} {{ $startY }} Q {{ $controlX }} {{ $controlY }} {{ $endX }} {{ $endY }}" />
                <path class="relationship-edge-line" d="M {{ $startX }} {{ $startY }} Q {{ $controlX }} {{ $controlY }} {{ $endX }} {{ $endY }}" marker-end="url(#relationship-arrow)" @if($link->is_mutual) marker-start="url(#relationship-arrow)" @endif />
                <circle cx="{{ $labelX }}" cy="{{ $labelY }}" r="14" /><text x="{{ $labelX }}" y="{{ $labelY + 5 }}" text-anchor="middle">{{ $records->values()->search(fn ($record) => $record->id === $link->id) + 1 }}</text>
            </a>
        @endforeach
        @foreach($keys as $key)
            @php $position = $positions[$key]; $type = $catalogue->get($key)['type'] ?? 'Archived'; @endphp
            <a class="relationship-map-node" href="?section=relationships&amp;subjectFilter={{ urlencode($key) }}" wire:click.prevent="$set('subjectFilter', '{{ $key }}')" aria-label="Show connections to {{ $names[$key] }}">
                <circle cx="{{ $position['x'] }}" cy="{{ $position['y'] }}" r="31" />
                <text x="{{ $position['x'] }}" y="{{ $position['y'] + 6 }}" text-anchor="middle" aria-hidden="true">{{ mb_substr($names[$key], 0, 1) }}</text>
                <rect x="{{ $position['x'] - 105 }}" y="{{ $position['y'] + 36 }}" width="210" height="46" rx="4" />
                <text class="relationship-node-name" x="{{ $position['x'] }}" y="{{ $position['y'] + 54 }}" text-anchor="middle">{{ \Illuminate\Support\Str::limit($names[$key], 26) }}</text>
                <text class="relationship-node-type" x="{{ $position['x'] }}" y="{{ $position['y'] + 72 }}" text-anchor="middle">{{ $type }}</text>
            </a>
        @endforeach
    </svg>
    <p class="relationship-map-key">→ One direction &nbsp; ↔ Mutual &nbsp; · Select a numbered connection to read or edit it.</p>
</div>
