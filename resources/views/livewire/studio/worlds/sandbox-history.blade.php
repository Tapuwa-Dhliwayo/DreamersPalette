@forelse($changes as $change)
    <article class="sandbox-review-change">
        <h3>{{ $change->reversed_at ? 'Reversed acceptance' : 'Accepted changes' }} · {{ $change->created_at->asDateTime() }}</h3>
        @foreach($change->changes as $entry)
            <p>{{ $entry['after']['name'] ?? $entry['after']['title'] ?? $entry['after']['label'] ?? 'Event participation' }}</p>
            @if(isset($entry['key']) && isset($catalogue[$entry['key']]))
                <flux:button size="sm" icon="arrow-top-right-on-square" :href="app(\App\Services\Worldbuilding\WorldContext::class)->url($world, $entry['key'])" target="_blank" rel="noopener">Open record</flux:button>
            @endif
            @if($entry['provenance'] ?? null)<p class="sandbox-help">Proposed basis: {{ str_replace('_', ' ', $entry['provenance']['basis'] ?? 'interpretation') }}</p><x-studio.markdown :text="$entry['provenance']['evidence'] ?? ''" />@endif
        @endforeach
        @if(!$change->reversed_at)
            <p class="sandbox-help">Undo is refused if a record has newer changes. Created records are archived.</p>
            <flux:button size="sm" icon="arrow-uturn-left" wire:click="reverseCanon({{ $change->id }})">Undo acceptance</flux:button>
        @endif
    </article>
@empty
    <p>No sandbox ideas have been made canon in this exercise.</p>
@endforelse
