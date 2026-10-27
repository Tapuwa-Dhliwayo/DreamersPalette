<div>
    <header class="studio-header world-header">
        <a href="{{ route('studio.index') }}" class="studio-brand">Dreamer’s Palette</a>
        <nav class="studio-header-links" aria-label="Studio"><a href="{{ route('studio.index') }}">Write</a><a href="{{ route('studio.worlds') }}" aria-current="page">Worlds</a><a href="/dashboard">Dashboard</a><span>{{ auth()->user()->name }}</span></nav>
    </header>
    <main id="studio-main" class="manuscript-library">
        <div class="library-heading"><h1>Worlds</h1>@can('create', \App\Models\World::class)<flux:button variant="primary" icon="plus" wire:click="$set('creating', true)">New world</flux:button>@endcan</div>
        <flux:select class="archive-filter" label="Show" wire:model.live.boolean="archived"><flux:select.option value="false">Active</flux:select.option><flux:select.option value="true">Archived</flux:select.option></flux:select>
        <div class="manuscript-shelf">
            @forelse($worlds as $world)
                <div class="studio-library-record"><a class="manuscript-book" @if(!$archived) href="{{ route('studio.world', $world) }}" @endif><div class="book-spine"><flux:icon.globe-alt /></div><div><h2>{{ $world->name }}</h2>@if($world->description)<p>{{ $world->description }}</p>@endif<div class="manuscript-meta">{{ $world->systems_count }} {{ Str::plural('system', $world->systems_count) }} · {{ $world->manuscripts_count }} {{ Str::plural('manuscript', $world->manuscripts_count) }}</div></div><flux:icon.arrow-up-right class="book-open-arrow" /></a>@can($archived ? 'restore' : 'update', $world)<flux:button size="sm" :icon="$archived ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $world->id }}, {{ $archived ? 'false' : 'true' }})">{{ $archived ? 'Restore' : 'Archive' }}</flux:button>@endcan</div>
            @empty
                <div class="studio-empty"><h2>No worlds</h2></div>
            @endforelse
        </div>
    </main>
    <flux:modal wire:model="creating" class="md:w-[32rem]" :dismissible="false" :closable="false"><form wire:submit="createWorld" class="studio-form"><flux:heading size="lg">New world</flux:heading><flux:input label="Name" wire:model="form.name" maxlength="255" /><flux:textarea label="Description" badge="Optional" wire:model="form.description" rows="3" /><div class="studio-form-actions"><flux:button wire:click="$set('creating', false)">Cancel</flux:button><flux:button type="submit" variant="primary">Create world</flux:button></div></form></flux:modal>
</div>
