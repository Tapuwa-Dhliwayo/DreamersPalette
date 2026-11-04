@if($source)
    <section class="sandbox-source-reader">
        <h3>{{ $source->title }}</h3>
        <p class="sandbox-help">Saved revision {{ $source->chapter_revision }} · passages {{ $readingFrom }}–{{ $readingTo }}. The original manuscript is unchanged.</p>
        @if(($sourceRevisions[$source->chapter_id] ?? null) !== $source->chapter_revision)<p class="sandbox-notice">The chapter has changed or was archived. This is the saved version used for this response.</p>@endif
        <details><summary>Reading notes · assistant interpretation</summary>@foreach($source->reads ?? [] as $read)<p class="sandbox-help">Passages {{ $read['start'] }}–{{ $read['end'] }}</p><x-studio.markdown :text="app(\App\Services\Worldbuilding\SourceReading::class)->citations($source->turn, $read['notes'])" />@endforeach</details>
        @foreach($source->window($readingFrom, $readingTo) as $passage)
            <article><span class="sandbox-help">Passage {{ $passage['number'] }}</span><x-studio.markdown :text="$passage['text']" /></article>
        @endforeach
        <div class="sandbox-actions">
            @if($readingFrom > 1)<flux:button size="sm" icon="arrow-left" wire:click="openSource({{ $source->id }},{{ max(1,$readingFrom-12) }},{{ $readingFrom-1 }})">Earlier passages</flux:button>@endif
            @if($readingTo < count($source->passages))<flux:button size="sm" icon="arrow-right" wire:click="openSource({{ $source->id }},{{ $readingTo+1 }},{{ min(count($source->passages),$readingTo+12) }})">Later passages</flux:button>@endif
        </div>
    </section>
@endif
