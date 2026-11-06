@props(['active' => null, 'guard' => false])
@php
    $user = auth()->user();
    $links = collect([
        'write' => ['Write', route('studio.index'), 'pencil-square', $user->can('manuscripts.view')],
        'worlds' => ['Worlds', route('studio.worlds'), 'globe-alt', $user->can('worlds.view')],
        'reviews' => ['Editorial reviews', route('studio.reviews'), 'document-magnifying-glass', $user->can('reviews.view')],
        'publishing' => ['Publishing', '/dashboard/books', 'book-open', $user->can('books.view')],
        'dashboard' => ['Dashboard', '/dashboard', 'squares-2x2', true],
    ])->filter(fn (array $link): bool => $link[3]);
@endphp
<header class="studio-header">
    <a href="{{ route('studio.index') }}" class="studio-brand" @if($guard) @click="guardLink($event)" @endif>Dreamer’s Palette</a>
    @if(isset($context) && $context->hasActualContent())<div class="studio-header-context">{{ $context }}</div>@endif
    <nav class="studio-header-links" aria-label="Application">
        @foreach($links as $key => [$label, $href])
            <a href="{{ $href }}" @if($active === $key) aria-current="page" @endif @if($guard) @click="guardLink($event)" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <flux:dropdown class="studio-header-menu" position="bottom" align="end">
        <button type="button" class="studio-header-menu-button" aria-label="Menu"><flux:icon.bars-3 variant="micro" /></button>
        <flux:navmenu>
            @if(isset($context) && $context->hasActualContent())<div class="studio-header-context-menu">{{ $context }}</div><flux:navmenu.separator />@endif
            @foreach($links as $key => [$label, $href, $icon])
                <flux:navmenu.item :href="$href" :icon="$icon" :aria-current="$active === $key ? 'page' : null" :x-on:click="$guard ? 'guardLink($event)' : null">{{ $label }}</flux:navmenu.item>
            @endforeach
        </flux:navmenu>
    </flux:dropdown>
    @can('worlds.update')<livewire:studio.assistant-activity />@endcan
    <span class="studio-header-user">{{ $user->name }}</span>
</header>
