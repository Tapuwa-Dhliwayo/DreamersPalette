@props(['world', 'active' => 'magic'])
<nav class="world-tools" aria-label="World tools">
    <a href="{{ route('studio.worlds') }}" class="world-tools-home"><flux:icon.globe-alt variant="micro" />{{ $world->name }}</a>
    @foreach(['entries' => ['Encyclopedia', 'book-open'], 'magic' => ['Magic', 'sparkles'], 'people' => ['Characters', 'users'], 'timeline' => ['Timeline', 'clock'], 'arcs' => ['Arcs', 'chart-bar'], 'alignments' => ['Alignment', 'squares-2x2'], 'relationships' => ['Relationships', 'share'], 'comparisons' => ['Compare', 'table-cells'], 'maps' => ['Maps', 'map'], 'calendars' => ['Calendars', 'calendar-days'], 'investigations' => ['Investigations', 'magnifying-glass'], 'knowledge' => ['Knowledge', 'eye']] as $key => [$label, $icon])
        <a href="{{ $key === 'magic' ? route('studio.world', $world) : (in_array($key, ['maps', 'calendars']) ? route('studio.world.'.$key, $world) : route(in_array($key, ['investigations', 'knowledge']) ? 'studio.world.reasoning' : (in_array($key, ['relationships', 'comparisons']) ? 'studio.world.thinking' : 'studio.world.develop'), ['world' => $world, 'section' => $key])) }}" @class(['active' => $active === $key]) @if($active === $key) aria-current="page" @endif><flux:icon :name="$icon" variant="micro" />{{ $label }}</a>
    @endforeach
</nav>
