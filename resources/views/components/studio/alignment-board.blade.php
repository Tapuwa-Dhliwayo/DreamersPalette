@props(['chart', 'placements' => [], 'picker' => false, 'editable' => false])
@php
    $neutral = $chart->left_label === 'Chaotic' && $chart->right_label === 'Lawful' && $chart->top_label === 'Good' && $chart->bottom_label === 'Evil' ? 'Neutral' : 'Centre';
@endphp
<div class="alignment-map {{ $picker ? 'alignment-picker' : '' }}" @if($picker) x-data="{ x: $wire.entangle('placementForm.x'), y: $wire.entangle('placementForm.y') }" @endif aria-label="{{ $chart->title }}. {{ $chart->left_label }} to {{ $chart->right_label }}; {{ $chart->bottom_label }} to {{ $chart->top_label }}.">
    <span class="alignment-axis alignment-top">{{ $chart->top_label }}</span>
    <span class="alignment-axis alignment-left">{{ $chart->left_label }}</span>
    <div class="alignment-plot">
        @foreach([66 => $chart->top_label, 0 => null, -66 => $chart->bottom_label] as $y => $vertical)
            @foreach([-66 => $chart->left_label, 0 => null, 66 => $chart->right_label] as $x => $horizontal)
                @php($region = $horizontal && $vertical ? $horizontal.' · '.$vertical : ($horizontal ?: ($vertical ?: $neutral)))
                <button type="button" class="alignment-region" aria-label="{{ $picker ? 'Position in' : 'Add to' }} {{ $region }}" @if($picker) @click="x = {{ $x }}; y = {{ $y }}; dirty = true" @elseif($editable) wire:click="placeAt({{ $x }}, {{ $y }})" @else disabled @endif><span>{{ $region }}</span></button>
            @endforeach
        @endforeach
        @if($picker)
            <span class="alignment-preview" :style="{ left: ((Number(x) + 100) / 2) + '%', top: ((100 - Number(y)) / 2) + '%' }" aria-hidden="true"></span>
        @else
            @foreach($placements as $placement)
                <button type="button" class="alignment-point" data-alignment-id="{{ $placement->id }}" wire:key="alignment-marker-{{ $placement->id }}-{{ $placement->revision }}" x-data="alignmentMarker({ id: {{ $placement->id }}, x: {{ $placement->x }}, y: {{ $placement->y }}, revision: {{ $placement->revision }} })" :style="{ left: ((x + 100) / 2) + '%', top: ((100 - y) / 2) + '%' }" :class="{ 'is-moving': moving, 'is-saving': busy }" @if($editable) @pointerdown.stop="start($event)" @pointermove="move($event)" @pointerup.stop="finish($event)" @pointercancel="cancel()" @click.stop="if (!dragged && !busy) $wire.edit('placement', {{ $placement->id }})" @keydown.left.prevent="nudge(-5, 0)" @keydown.right.prevent="nudge(5, 0)" @keydown.up.prevent="nudge(0, 5)" @keydown.down.prevent="nudge(0, -5)" @else disabled @endif title="{{ $placement->name }}" aria-label="{{ $placement->name }}. Click to edit; drag or use arrow keys to move."><span>{{ $placement->name }}</span></button>
            @endforeach
        @endif
    </div>
    <span class="alignment-axis alignment-right">{{ $chart->right_label }}</span>
    <span class="alignment-axis alignment-bottom">{{ $chart->bottom_label }}</span>
</div>
