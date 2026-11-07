<div class="sandbox-turn-state" wire:key="turn-{{ $turn->id }}">
    <x-studio.evidence-status :status="$turn->context_manifest['coverage_status'] ?? []" />
    @if($turn->isActive())
        <p role="status"><span class="sandbox-working-indicator" aria-hidden="true"></span> {{ $turn->progress ?: ($turn->status === 'queued' ? 'Question saved. Preparing…' : 'Responding…') }}</p>
        <p class="sandbox-help">Working in the background. You can leave; Studio activity will keep the result.</p>
        <flux:button size="sm" icon="stop" wire:click="stopTurn({{ $turn->id }})">Stop response</flux:button>
    @elseif(in_array($turn->status, ['failed','paused']))
        <p role="{{ $turn->status === 'failed' ? 'alert' : 'status' }}">{{ $turn->status === 'paused' ? 'Your question has not been answered yet. Reading paused at the batch limit; all completed passages are saved. Continue to finish the reading and get your answer.' : $turn->error }}</p>
        <flux:button size="sm" icon="arrow-path" wire:click="retryTurn({{ $turn->id }})" wire:loading.attr="disabled">{{ $turn->status === 'paused' ? 'Continue reading' : 'Retry saved question' }}</flux:button>
        <flux:button size="sm" icon="pencil-square" wire:click="editSavedQuestion({{ $turn->id }})">Edit saved question</flux:button>
    @elseif($turn->status === 'cancelled')
        <p>Stopped · question and completed reading retained.</p>
        <flux:button size="sm" icon="arrow-path" wire:click="retryTurn({{ $turn->id }})">Resume saved question</flux:button>
    @elseif($turn->mode === 'summary')
        <flux:button size="sm" icon="document-text" wire:click="acceptSummary({{ $turn->id }})">Use as working summary</flux:button>
    @elseif(!empty($turn->proposals['ideas']))
        @php($recordProposals = collect($turn->proposals['ideas'])->reject(fn($proposal) => in_array($proposal['kind'], ['thought','question','name'], true)))
        @php($turnRecords=$allIdeas->whereIn('id',array_values($turn->request_context['collected_proposals'] ?? []))->reject(fn($draft)=>in_array($draft->kind,['thought','question','name'])))
        @php($acceptedCount=$turnRecords->whereNotNull('canonised_at')->count())
        @if($recordProposals->isNotEmpty())
            <div class="sandbox-proposed-actions">
                <p><strong>{{ $recordProposals->count() }} {{ $recordProposals->count() === 1 ? 'record draft' : 'record drafts' }}</strong> · {{ $acceptedCount }} accepted · {{ max(0,$recordProposals->count()-$acceptedCount) }} pending</p>
                <ul>@foreach($recordProposals as $proposal)
                    <li>{{ ['person'=>'Character sheet', 'entry'=>'Encyclopedia entry', 'relationship'=>'Relationship'][$proposal['kind']] ?? ucfirst($proposal['kind']) }}: <strong>{{ $catalogue[$proposal['target_key'] ?? '']['name'] ?? $proposal['title'] }}</strong>@if(!empty($proposal['target_key'])) · update existing @endif @if($proposal['kind']==='person') · Structured sheet fields @endif</li>
                @endforeach</ul>
                <flux:button size="sm" variant="primary" icon="document-check" wire:click="reviewTurn({{ $turn->id }})" x-on:click="detailsOpen=true;conversationFocus=true;mobilePane='details'" wire:loading.attr="disabled">{{ $acceptedCount===$recordProposals->count() ? 'View accepted changes' : 'Review pending changes' }}</flux:button>
                <p class="sandbox-help">Drafts are on the board. Discuss a draft to refine it; accept only the changes you want.</p>
            </div>
        @else
            <flux:button size="sm" icon="squares-plus" wire:click="collectTurn({{ $turn->id }})">Keep ideas on board</flux:button>
        @endif
        <details wire:ignore.self class="sandbox-proposal-preview"><summary>Proposed content and evidence</summary>
            @foreach($turn->proposals['ideas'] as $proposal)
                <section class="sandbox-proposal-preview-item">
                    <h3>{{ $proposal['title'] }}</h3>
                    @if(!empty($proposal['target_key']))<p>Linked to {{ $catalogue[$proposal['target_key']]['name'] ?? $proposal['title'] }}</p>@endif
                    @if($proposal['kind']==='person')<p>Destination: character sheet → proposed fields</p>@endif
                    <x-studio.canon-fields :fields="array_replace($proposal['fields'] ?? [],array_key_exists('notes_md',$proposal) ? ['notes_md'=>$proposal['notes_md']] : [])" :entry-types="$entryTypes" :catalogue="$catalogue" :proposal-names="collect($turn->proposals['ideas'])->mapWithKeys(fn($draft,$index)=>['idea:'.$index=>$draft['title']])->all()" />
                    <p>Proposed basis: {{ str_replace('_', ' ', $proposal['basis'] ?? 'interpretation') }}</p>
                    <x-studio.markdown :text="$proposal['evidence'] ?? 'No supporting evidence supplied.'" />
                    @if($proposal['uncertainty'] ?? null)<p>Still undecided: {{ $proposal['uncertainty'] }}</p>@endif
                </section>
            @endforeach
        </details>
    @endif
    @if($turn->status==='completed' && $turn->mode!=='summary' && ($turn->request_context['response_contract'] ?? 0)<3 && !empty($turn->proposals['ideas']))<flux:button size="sm" icon="document-plus" wire:click="restructureSavedTurn({{ $turn->id }})" wire:loading.attr="disabled">Restructure saved output</flux:button><p class="sandbox-help">Uses the original connection and saved content. Accepted records remain unchanged until a new review.</p>@endif
    @if($turn->proposals['continuation']['has_more'] ?? false)<section class="sandbox-notice"><strong>Partial batch</strong><p>Completed: {{ $turn->proposals['continuation']['completed_scope'] }}</p><p>Next: {{ $turn->proposals['continuation']['next_scope'] }}</p><flux:button size="sm" wire:click="continueStructuredTurn({{ $turn->id }})" wire:loading.attr="disabled">Continue this draft</flux:button></section>@endif
    <details wire:ignore.self wire:key="source-disclosure-{{ $turn->id }}"><summary>Sources consulted · {{ $turn->created_at->asDateTime() }}</summary>
        @if($turn->request_context['recovery'] ?? null)<p>Prepared from a saved response or thought. Original source citations are retained; the novel was not reread.</p>@endif
        <p>{{ $turn->reading_depth === 'whole' ? 'Whole-source reading' : 'Focused investigation' }} · {{ count($turn->request_context['passages'] ?? $turn->context_manifest['source_collection'] ?? []) }} chapters available</p>
        <p>World records: {{ count($turn->context_manifest['records'] ?? []) }} · Reviewed naming conventions: {{ count($turn->context_manifest['conventions'] ?? []) }}</p>
        @if(($turn->context_manifest['coverage_status']['omitted_notes_count'] ?? count($turn->context_manifest['coverage_status']['omitted_notes'] ?? [])) > 0)
            <p>{{ $turn->context_manifest['coverage_status']['omitted_notes_count'] ?? count($turn->context_manifest['coverage_status']['omitted_notes'] ?? []) }} saved analysis ranges were omitted from the answer context. Historical analysis below does not mean every note was supplied.</p>
        @endif
        @if(($turn->context_manifest['coverage_status']['stop_reason'] ?? 'ready') !== 'ready')
            <p>Information gathering stopped before the model reported sufficient evidence ({{ str_replace('_', ' ', $turn->context_manifest['coverage_status']['stop_reason']) }}).</p>
        @endif
        @foreach($readingSources->get($turn->id, collect()) as $reading)
            @php($ranges=app(\App\Services\Worldbuilding\SourceReading::class)->ranges($reading, true))
            @php($readCount=array_sum(array_map(fn($range)=>$range[1]-$range[0]+1,$ranges)))
            <article class="sandbox-source-coverage">
                <strong>{{ $reading->title }}</strong>@if(collect($reading->reads)->contains(fn($read)=>isset($read['reused_from'])))<span class="sandbox-reference-label">Saved reference reused</span>@endif
                <p>{{ count($reading->passages) === 0 ? 'No prose' : ($readCount === 0 ? 'Unread' : ($readCount === count($reading->passages) ? 'Historically analysed in full' : 'Historically partially analysed')) }} · {{ $readCount }}/{{ count($reading->passages) }} passages · revision {{ $reading->chapter_revision }}</p>
                @if(($sourceRevisions[$reading->chapter_id] ?? null) !== $reading->chapter_revision)<p>Chapter changed or was archived after this reading. These are the saved passages used for the response.</p>@endif
                @foreach($ranges as [$start,$end])<button class="sandbox-text-action" wire:click="openSource({{ $reading->id }},{{ $start }},{{ $end }})" x-on:click="detailsOpen=true;conversationFocus=true;mobilePane='details'">Read passages {{ $start }}–{{ $end }}</button>@endforeach
                @foreach(collect($reading->reads ?? [])->filter(fn($read)=>!\App\Models\AssistantSource::isAnalysis($read))->unique(fn($read)=>$read['start'].'-'.$read['end']) as $consultation)
                    <button class="sandbox-text-action" wire:click="openSource({{ $reading->id }},{{ $consultation['start'] }},{{ $consultation['end'] }})" x-on:click="detailsOpen=true;conversationFocus=true;mobilePane='details'">Consulted without analysis: {{ $consultation['start'] }}–{{ $consultation['end'] }}</button>
                @endforeach
                @if(!$ranges && count($reading->passages)>0)<button class="sandbox-text-action" wire:click="openSource({{ $reading->id }})" x-on:click="detailsOpen=true;conversationFocus=true;mobilePane='details'">Inspect available source</button>@endif
            </article>
        @endforeach
        @if($turn->mode === 'summary')<p>This request summarises the discussion. It does not reread the manuscript collection.</p>@endif
        @foreach($turn->context_manifest['records'] ?? [] as $record)<p>{{ $catalogue[$record['key']]['name'] ?? $record['key'] }} · revision {{ $record['revision'] }}</p>@endforeach
    </details>
</div>
