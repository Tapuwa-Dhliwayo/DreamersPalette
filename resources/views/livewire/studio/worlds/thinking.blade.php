<div x-data="{ dirty: false }" @input="if ($event.target.closest('form')) dirty = true" @change="if ($event.target.closest('form')) dirty = true" @world-saved.window="dirty = false" @relationship-selected.window="$nextTick(() => { if (window.innerWidth <= 1050) $refs.relationshipInspector?.scrollIntoView({ block: 'start' }); })" @beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = ''; }">
    <x-studio.header active="worlds" />
    <x-studio.world-tools :world="$world" :active="$section" />
    @switch($section)
        @case('relationships') @include('livewire.studio.worlds.relationship-workspace') @break
        @case('comparisons') @include('livewire.studio.worlds.comparison-workspace') @break
    @endswitch
</div>
