@if($canonReview)
    @php($structureCounts=$canonReview['structure_counts'] ?? [])
    <p class="sandbox-structure-headline">Changes in this review: {{ $structureCounts['event'] ?? 0 }} events · {{ $structureCounts['arc'] ?? 0 }} arcs · {{ $structureCounts['beats'] ?? 0 }} beats · {{ $structureCounts['participants'] ?? 0 }} participant links · {{ $structureCounts['placements'] ?? 0 }} placements · {{ $structureCounts['rules'] ?? 0 }} rules</p>
    <p class="sandbox-notice">Review {{ count($canonReview['changes']) }} {{ count($canonReview['changes']) === 1 ? 'record change' : 'record changes' }}. Acceptance updates the live world, not finished drafts or published prose.</p>
    <nav class="sandbox-review-jump" aria-label="Proposed records">@foreach($canonReview['changes'] as $listedChange)<a href="#review-change-{{ $listedChange['idea_id'] }}">{{ $listedChange['title'] }}</a>@endforeach</nav>
    @foreach($canonReview['changes'] as $change)
        <section id="review-change-{{ $change['idea_id'] }}" class="sandbox-review-change">
            <h3>{{ $change['target_key'] ? 'Update' : 'Create' }} {{ $change['kind'] === 'person' ? 'character sheet' : $change['kind'] }}: {{ $change['title'] }}</h3>
            @if(filled($change['uncertainty'] ?? null))<p class="sandbox-help"><strong>Open questions retained:</strong> {{ $change['uncertainty'] }}</p>@endif
            <flux:button size="sm" icon="pencil-square" wire:click="editIdea({{ $change['idea_id'] }})">Edit this draft</flux:button>
            @if($change['kind'] === 'person')<p class="sandbox-help">Profile sections and supplementary Notes are separate. Only the proposed fields change.</p>@endif
            @php($assessment=$change['provenance']['structure'] ?? [])
            @if(($assessment['completeness'] ?? 'unassessed') !== 'complete')<p class="sandbox-notice">{{ ucfirst($assessment['completeness'] ?? 'Unassessed') }} structure. {{ implode(' ', $assessment['missing'] ?? []) }}</p>@endif
            @if(!empty($change['structure_problems']))<p class="sandbox-notice">{{ implode(' ', $change['structure_problems']) }}</p>@endif
            @if($change['provenance'] ?? null)
                <x-studio.evidence-status :status="$change['provenance']['coverage_status'] ?? []" />
                <p>Proposed basis: {{ str_replace('_', ' ', $change['provenance']['basis'] ?? 'interpretation') }}</p>
                <x-studio.markdown :text="$change['provenance']['evidence'] ?? ''" />
            @endif
            @php($displayAfter=collect($change['after'])->reject(fn($content,$field)=>array_key_exists($field,$change['before'] ?? []) && $content===$change['before'][$field])->all())
            @if(!$displayAfter)<p class="sandbox-help">These fields are unchanged. Review any connections below.</p>@endif
            <div class="sandbox-review-diff">
                @if($change['before'] && $displayAfter)
                    <details open><summary>Current values</summary><x-studio.canon-fields :fields="collect($change['before'])->only(array_keys($displayAfter))" :entry-types="$entryTypes" :catalogue="$catalogue" :proposal-names="$ideas->pluck('title','id')->all()" :context="$change['before']" /></details>
                @endif
                @if($displayAfter)<details open><summary>Proposed values</summary><x-studio.canon-fields :fields="$displayAfter" :entry-types="$entryTypes" :catalogue="$catalogue" :proposal-names="$ideas->pluck('title','id')->all()" :context="$change['before'] ?? []" /></details>@endif
            </div>
        </section>
    @endforeach
    @if($canonReview['links'])
        <h3>Connections in this review</h3>
        @foreach($canonReview['links'] as $link)<p>{{ $link['endpoints'][0]['title'] }} → {{ $link['endpoints'][1]['title'] }}: {{ $link['label'] }} ({{ $link['kind'] }})@if($link['kind']==='connection') · Board only; no world relationship will be saved.@endif</p>@endforeach
    @endif
    @if(collect($canonReview['changes'])->contains(fn($change) => filled($change['uncertainty'] ?? null)))
        <flux:checkbox label="Keep the stated open questions in the saved record" wire:model="acknowledgeUncertainty" />
    @endif
    <flux:button variant="primary" icon="check-badge" wire:click="acceptCanon" wire:loading.attr="disabled">Make these changes canon</flux:button>
@else
    <p>Select record proposals on the board or list, then choose Review canon. Questions and shortlists stay in the sandbox.</p>
@endif
