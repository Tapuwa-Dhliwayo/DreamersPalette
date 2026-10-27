<div x-data="{ browse: false }">
    <header class="studio-header world-header"><a href="{{ route('studio.index') }}" class="studio-brand">Dreamer’s Palette</a><nav class="studio-header-links" aria-label="Studio"><a href="{{ route('studio.index') }}">Write</a><a href="{{ route('studio.worlds') }}">Worlds</a></nav></header>
    <x-studio.world-tools :world="$world" active="maps" />
    <div class="world-development" :class="{ 'browse-open': browse }">
        <aside class="world-browser" aria-label="Maps"><div class="world-section-heading"><h2>Maps</h2><button class="development-browse-toggle" @click="browse = false" aria-label="Close browse"><flux:icon.x-mark /></button></div>
            <flux:select label="Show" wire:model.live.boolean="archived"><option value="false">Active</option><option value="true">Archived</option></flux:select>
            @can('update', $world)<flux:button icon="plus" variant="primary" wire:click="edit">New map</flux:button>
            
@endcan

            <div class="development-entries">@forelse($maps as $listed)<button wire:click="select({{ $listed->id }})" @click="browse = false" @class(['active' => $selected === $listed->id])>{{ $listed->title }}</button>
            
@empty
<p>No maps here</p>
            @endforelse</div>
        </aside>
        <main id="studio-main" class="world-document development-document" :inert="browse">
            <button class="development-browse-toggle" @click="browse = true"><flux:icon.bars-3 variant="micro" />Browse maps</button><p role="status">{{ $notice }}</p>
            @error('conflict')<p class="studio-alert">{{ $message }}</p>
            @enderror
            @if($map)
            <div class="world-entry-heading"><h1>{{ $viewMap->title }}</h1><div class="thinking-actions">@can('update', $world)@if(!$map->trashed())<flux:button icon="pencil-square" wire:click="edit({{ $map->id }})">Edit map</flux:button>
            
@endif

            <flux:button :icon="$map->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $map->id }}, {{ $map->revision }}, {{ $map->trashed() ? 'false' : 'true' }})">{{ $map->trashed() ? 'Restore' : 'Archive' }}</flux:button>
            
@endcan
</div></div>
            <flux:select label="Version" wire:model.live="version"><option value="">Current version {{ $map->revision }}</option>@foreach($map->versions as $saved)<option value="{{ $saved->id }}">Version {{ $saved->revision }} · {{ $saved->created_at->asDateTime() }}</option>
            
@endforeach
</flux:select>
            @if($version)<p class="studio-alert">Reading a retained version. Editing opens the current map.</p>
            
@endif

            <div class="geography-board" style="aspect-ratio: {{ $viewMap->width_px }} / {{ $viewMap->height_px }}">
                @if($viewMap->image_path)<img src="{{ route('studio.world.map.image', ['world' => $world, 'map' => $map->id, 'version' => $version]) }}" alt="{{ $viewMap->title }}">
                
@endif

                <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ collect($viewMap->journey)->map(fn ($key) => collect($viewMap->pins)->firstWhere('key', $key))->filter()->map(fn ($pin) => $pin['x'].','.$pin['y'])->implode(' ') }}" /></svg>
                @foreach($viewMap->pins as $pin)<span class="geography-pin" style="left:{{ $pin['x'] }}%;top:{{ $pin['y'] }}%">{{ $pin['name'] }}</span>
                
@endforeach

            </div>
            <section class="geography-journey"><h2>Journey</h2><p>{{ $viewMap->width_units ? 'Map width: '.number_format($viewMap->width_units, 2).' '.$viewMap->unit : 'No scale set. Distances are not estimated.' }}</p>
            @foreach($viewMap->journeyLegs() as $leg)<div><span>{{ $leg['from'] }} → {{ $leg['to'] }}</span><strong>{{ $leg['distance'] !== null ? number_format($leg['distance'], 2).' '.$viewMap->unit : 'Unknown' }}</strong></div>
            
@endforeach

            @if(count($viewMap->journey) > 1 && $viewMap->width_units)<div><span>Total</span><strong>{{ number_format(array_sum(array_column($viewMap->journeyLegs(), 'distance')), 2) }} {{ $viewMap->unit }}</strong></div><p>Straight-line segments on a flat map. Terrain and globe curvature are not included.</p>
            
@endif
</section>
            <section><h2>Locations</h2>@foreach($viewMap->pins as $pin)<p>@if($pin['entry_id'])<a href="{{ route('studio.world.develop', ['world' => $world, 'section' => 'entries', 'selected' => $pin['entry_id']]) }}">{{ $pin['name'] }}</a>
@else
{{ $pin['name'] }}
            
@endif
</p>
            
@endforeach
</section>
            <x-studio.markdown :text="$viewMap->notes_md ?? ''" />
            
@else
<div class="thinking-empty"><flux:icon.map /><h1>Maps</h1><p>Start with a blank canvas or an image. Place your locations and trace a journey.</p>@can('update', $world)<flux:button icon="plus" variant="primary" wire:click="edit">New map</flux:button>
            
@endcan
</div>
            
@endif

        </main>
    </div>
    <flux:modal wire:model="editing" class="thinking-modal" :dismissible="false" :closable="false">
        <form wire:submit="save" class="thinking-form"><h2>{{ $editingId ? 'Edit map' : 'New map' }}</h2>
            @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>
            @enderror
            <flux:input label="Title" wire:model="form.title" />
            <flux:input type="file" label="Map image (optional)" wire:model="form.image" accept="image/png,image/jpeg,image/webp" />
            <p wire:loading wire:target="form.image" role="status">Uploading map…</p>
            <div class="thinking-form-pair"><flux:input type="number" label="Canvas width" wire:model="form.width_px" /><flux:input type="number" label="Canvas height" wire:model="form.height_px" /></div>
            <div class="thinking-form-pair"><flux:input type="number" step="any" label="Full map width in units (optional)" wire:model="form.width_units" /><flux:input label="Unit" wire:model="form.unit" placeholder="km, metres, light-years…" /></div>
            <section><div class="compile-section-heading"><h3>Place locations</h3><flux:button size="sm" icon="plus" wire:click="addPin">Add pin</flux:button></div><p class="compile-source-note">Click the canvas to add a pin. Drag a pin, or use its arrow keys to move it. Save the map to keep changes.</p>
            <div class="geography-board geography-edit" style="aspect-ratio: {{ max(100, $form->width_px) }} / {{ max(100, $form->height_px) }}" @click="if (!$event.target.closest('button')) { const bounds = $el.getBoundingClientRect(); $wire.addPin(($event.clientX - bounds.left) / bounds.width * 100, ($event.clientY - bounds.top) / bounds.height * 100); }">
                @if($editingId && $world->maps()->find($editingId)?->image_path)<img src="{{ route('studio.world.map.image', ['world' => $world, 'map' => $editingId]) }}" alt="Map background">
                
@endif

                @foreach($form->pins as $index => $pin)
                <button type="button" wire:key="map-marker-{{ $pin['key'] }}-{{ $index }}" class="geography-pin" x-data="{ px: $wire.entangle('form.pins.{{ $index }}.x'), py: $wire.entangle('form.pins.{{ $index }}.y'), dragging: false }" :style="{ left: px + '%', top: py + '%' }" @pointerdown.prevent="dragging = true; $el.setPointerCapture($event.pointerId)" @pointermove="if (dragging) { const bounds = $el.parentElement.getBoundingClientRect(); px = Math.max(0, Math.min(100, ($event.clientX - bounds.left) / bounds.width * 100)); py = Math.max(0, Math.min(100, ($event.clientY - bounds.top) / bounds.height * 100)); }" @pointerup="dragging = false" @pointercancel="dragging = false" @click.stop @keydown.arrow-right.prevent="px = Math.min(100, Number(px) + 1)" @keydown.arrow-left.prevent="px = Math.max(0, Number(px) - 1)" @keydown.arrow-up.prevent="py = Math.max(0, Number(py) - 1)" @keydown.arrow-down.prevent="py = Math.min(100, Number(py) + 1)">{{ $pin['name'] ?: 'Pin '.($index + 1) }}</button>
                
@endforeach

            </div>
            @foreach($form->pins as $index => $pin)<div class="geography-pin-form" wire:key="map-pin-{{ $pin['key'] }}"><div class="thinking-form-pair"><flux:input label="Pin {{ $index + 1 }} name" wire:model="form.pins.{{ $index }}.name" /><flux:select label="Link location (optional)" wire:model.live="form.pins.{{ $index }}.entry_id"><option value="">Unlinked</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}{{ $location->trashed() ? ' · Archived' : '' }}</option>
            
@endforeach
</flux:select></div><div class="thinking-form-pair"><flux:input type="number" step="any" label="Horizontal position (%)" wire:model="form.pins.{{ $index }}.x" /><flux:input type="number" step="any" label="Vertical position (%)" wire:model="form.pins.{{ $index }}.y" /></div><flux:button size="sm" icon="trash" wire:click="removePin({{ $index }})">Remove pin</flux:button></div>
            
@endforeach
<flux:error name="form.pins" /></section>
            <section><h3>Journey stops, in travel order</h3>@foreach($form->journey as $index => $stop)<div class="thinking-field-action"><flux:select label="Stop {{ $index + 1 }}" wire:model="form.journey.{{ $index }}"><option value="">Choose pin</option>@foreach($form->pins as $pin)<option value="{{ $pin['key'] }}">{{ $pin['name'] ?: 'Unnamed pin' }}</option>
            
@endforeach
</flux:select><flux:button icon="trash" wire:click="removeStop({{ $index }})" aria-label="Remove stop {{ $index + 1 }}" /></div>
            
@endforeach
<flux:button size="sm" icon="plus" wire:click="addStop">Add stop</flux:button><flux:error name="form.journey" /><flux:error name="form.journey.*" /></section>
            <flux:textarea label="Geography & journey notes" wire:model="form.notes_md" rows="4" />
            <div class="thinking-actions"><flux:button wire:click="$set('editing', false)">Cancel</flux:button><flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save,form.image">Save map</flux:button></div>
        </form>
    </flux:modal>
</div>
