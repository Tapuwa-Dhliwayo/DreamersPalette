<div class="sandbox-turn-state" wire:key="turn-{{ $turn->id }}">
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
    @elseif($turn->proposals)
        <flux:button size="sm" icon="squares-plus" wire:click="collectTurn({{ $turn->id }})">Collect {{ count($turn->proposals['ideas'] ?? []) }} proposals</flux:button>
    @endif
    <details wire:ignore.self wire:key="source-disclosure-{{ $turn->id }}"><summary>Sources consulted · {{ $turn->created_at->asDateTime() }}</summary>
        <p>{{ $turn->reading_depth === 'whole' ? 'Whole-source reading' : 'Focused investigation' }} · {{ count($turn->request_context['passages'] ?? $turn->context_manifest['source_collection'] ?? []) }} chapters available</p>
        <p>World records: {{ count($turn->context_manifest['records'] ?? []) }} · Reviewed naming conventions: {{ count($turn->context_manifest['conventions'] ?? []) }}</p>
        @foreach($readingSources->get($turn->id, collect()) as $reading)
            @php($ranges=app(\App\Services\Worldbuilding\SourceReading::class)->ranges($reading))
            @php($readCount=array_sum(array_map(fn($range)=>$range[1]-$range[0]+1,$ranges)))
            <article class="sandbox-source-coverage">
                <strong>{{ $reading->title }}</strong>@if(collect($reading->reads)->contains(fn($read)=>isset($read['reused_from'])))<span class="sandbox-reference-label">Saved reference reused</span>@endif
                <p>{{ count($reading->passages) === 0 ? 'No prose' : ($readCount === 0 ? 'Unread' : ($readCount === count($reading->passages) ? 'Read in full' : 'Partially read')) }} · {{ $readCount }}/{{ count($reading->passages) }} passages · revision {{ $reading->chapter_revision }}</p>
                @if(($sourceRevisions[$reading->chapter_id] ?? null) !== $reading->chapter_revision)<p>Chapter changed or was archived after this reading. These are the saved passages used for the response.</p>@endif
                @foreach($ranges as [$start,$end])<button class="sandbox-text-action" wire:click="openSource({{ $reading->id }},{{ $start }},{{ $end }})" x-on:click="detailsOpen=true;conversationFocus=true;mobilePane='details'">Read passages {{ $start }}–{{ $end }}</button>@endforeach
                @if(!$ranges && count($reading->passages)>0)<button class="sandbox-text-action" wire:click="openSource({{ $reading->id }})" x-on:click="detailsOpen=true;conversationFocus=true;mobilePane='details'">Inspect available source</button>@endif
            </article>
        @endforeach
        @if($turn->mode === 'summary')<p>This request summarises the discussion. It does not reread the manuscript collection.</p>@endif
        @foreach($turn->context_manifest['records'] ?? [] as $record)<p>{{ $catalogue[$record['key']]['name'] ?? $record['key'] }} · revision {{ $record['revision'] }}</p>@endforeach
    </details>
</div>
