@if($canonReview)
    <p class="sandbox-notice">Review {{ count($canonReview['changes']) }} {{ count($canonReview['changes']) === 1 ? 'record change' : 'record changes' }}. Acceptance updates the live world, not finished drafts or published prose.</p>
    @foreach($canonReview['changes'] as $change)
        <section class="sandbox-review-change">
            <h3>{{ $change['target_key'] ? 'Update' : 'Create' }} {{ $change['kind'] === 'person' ? 'character sheet' : $change['kind'] }}: {{ $change['title'] }}</h3>
            @if(filled($change['uncertainty'] ?? null))<p class="sandbox-help"><strong>Open questions retained in Notes:</strong> {{ $change['uncertainty'] }}</p>@endif
            <flux:button size="sm" icon="pencil-square" wire:click="editIdea({{ $change['idea_id'] }})">Edit this draft</flux:button>
            @if($change['kind'] === 'person')<p class="sandbox-help">Summary sections are saved in this character’s Notes. Aliases, roles and domain change only as shown below.</p>@endif
            @if($change['provenance'] ?? null)
                <x-studio.evidence-status :status="$change['provenance']['coverage_status'] ?? []" />
                <p>Proposed basis: {{ str_replace('_', ' ', $change['provenance']['basis'] ?? 'interpretation') }}</p>
                <x-studio.markdown :text="$change['provenance']['evidence'] ?? ''" />
            @endif
            @if($change['before'])
                <details open><summary>Current record</summary><x-studio.canon-fields :fields="collect($change['before'])->only(array_keys($change['after']))" :entry-types="$entryTypes" :catalogue="$catalogue" /></details>
            @endif
            <details open><summary>Proposed content</summary><x-studio.canon-fields :fields="$change['after']" :entry-types="$entryTypes" :catalogue="$catalogue" /></details>
        </section>
    @endforeach
    @if($canonReview['links'])
        <h3>Connections in this review</h3>
        @foreach($canonReview['links'] as $link)<p>{{ $ideas->firstWhere('id',$link['source_id'])?->title }} → {{ $ideas->firstWhere('id',$link['target_id'])?->title }}: {{ $link['label'] }} ({{ $link['kind'] }})</p>@endforeach
    @endif
    @if(collect($canonReview['changes'])->contains(fn($change) => filled($change['uncertainty'] ?? null)))
        <flux:checkbox label="Keep the stated open questions in the saved Notes" wire:model="acknowledgeUncertainty" />
    @endif
    <flux:button variant="primary" icon="check-badge" wire:click="acceptCanon" wire:loading.attr="disabled">Make these changes canon</flux:button>
@else
    <p>Select record proposals on the board or list, then choose Review canon. Questions and shortlists stay in the sandbox.</p>
@endif
