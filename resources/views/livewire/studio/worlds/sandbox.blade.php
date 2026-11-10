<div id="studio-main" tabindex="-1" class="world-sandbox" x-data="{ ...sandboxWorkspace(), drawerOpen: false, allLinks: false, dirty: false }" @sandbox-conversation-focus.window="drawerOpen = true; $nextTick(() => document.getElementById('sandbox-message')?.focus())" @sandbox-saved.window="dirty = false" @sandbox-move-failed.window="$el.querySelector('[data-move-error]').textContent=$event.detail.message" @click.capture="const sourceLink=$event.target.closest('a'); if(sourceLink && openCitation($event, sourceLink, $wire, {{ $exerciseId ?? 0 }})) return; if(dirty) { const action=$event.target.closest('button,a')?.getAttribute('wire:click') || ''; if(/^(editIdea|openExercise|newExercise|forkIdea|editConvention|openSource|closeDetails)/.test(action)) { if(!confirm('You have unsaved details. Discard them and continue?')) { $event.preventDefault(); $event.stopImmediatePropagation(); } else dirty=false; } }" @beforeunload.window="if(dirty) { $event.preventDefault(); $event.returnValue = ''; }">
    <x-studio.header active="worlds" />
    <x-studio.world-tools :world="$world" active="sandbox" />
    <div class="world-sandbox-body">
    <h1 class="sr-only">{{ $exercise?->title ?? 'World sandbox' }}</h1>
    <div class="sandbox-bar">
        <label class="sandbox-exercise-picker"><span class="sr-only">Open exercise</span><select aria-label="Open exercise" x-on:change="if(!dirty || confirm('You have unsaved details. Open another exercise?')) { dirty=false; $wire.openExercise(Number($event.target.value)) }"><option value="" @selected(!$exercise)>Choose an exercise</option>@foreach($exercises as $listed)<option value="{{ $listed->id }}" @selected($exerciseId === $listed->id)>{{ $listed->title }}</option>@endforeach</select></label>
        @if($exercise)<div class="sandbox-view-switch" role="group" aria-label="Working view">@foreach(['discuss' => 'Discuss', 'board' => 'Board', 'timeline' => 'Timeline', 'list' => 'List'] as $key => $label)<button type="button" wire:click="$set('view', '{{ $key }}')" x-on:click="drawerOpen=false" aria-pressed="{{ $view === $key ? 'true' : 'false' }}" @class(['active' => $view === $key])>{{ $label }}</button>@endforeach</div>@endif
        <div class="sandbox-bar-actions">
            @if($undoMove)<button type="button" class="sandbox-text-action" wire:click="undoMovement">Undo move</button>@endif
            @if($undoTidy)<button type="button" class="sandbox-text-action" wire:click="undoTidyBoard">Undo tidy</button>@endif
            @if($exercise)<flux:button size="sm" icon="check-badge" wire:click="reviewCanon">Review canon ({{ count($selectedIdeas) }})</flux:button>@endif
            <flux:dropdown position="bottom" align="end">
                <flux:button size="sm" icon:trailing="chevron-down">Exercise</flux:button>
                <flux:menu>
                    <flux:menu.item icon="plus" wire:click="newExercise">New exercise</flux:menu.item>
                    <flux:modal.trigger name="import-work"><flux:menu.item icon="arrow-up-tray">Import work</flux:menu.item></flux:modal.trigger>
                    @if($exercise)
                        <flux:menu.item icon="pencil-square" wire:click="$set('panel', 'exercise')">Exercise &amp; context</flux:menu.item>
                        <flux:menu.item icon="language" wire:click="editConvention">Naming</flux:menu.item>
                        <flux:menu.item icon="clock" wire:click="$set('panel', 'history')">History</flux:menu.item>
                    @endif
                    <flux:menu.separator />
                    <flux:menu.item icon="adjustments-horizontal" :href="route('studio.assistant-connections')">Assistant connections</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
    <p data-move-error role="alert" class="sandbox-errors" x-show="$el.textContent.length > 0"></p>
    @if($notice)<p class="sandbox-notice" role="status">{{ $notice }}</p>@endif
    @if($errors->any())<div role="alert" class="sandbox-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(!$exercise)
        <div class="sandbox-start"><section><h2>A place to think with your world</h2><p>Start with a situation, a scene or a question. Talk it through, connect possibilities and choose what becomes canon.</p><details><summary>Archived exercises ({{ $archivedExercises->count() }})</summary>@foreach($archivedExercises as $archived)<div class="sandbox-actions"><span>{{ $archived->title }}</span><flux:button size="sm" icon="arrow-uturn-left" wire:click="archiveExercise({{ $archived->id }}, {{ $archived->revision }}, false)">Restore</flux:button></div>@endforeach</details></section>@include('livewire.studio.worlds.sandbox-exercise')</div>
    @else
        @php($structureFocused = ($panel === 'canon' && !empty($canonReview)) || ($panel === 'idea' && isset(\App\Services\Worldbuilding\ProposalTypes::definition($ideaForm->kind)['relation'])))
        <div @class(['sandbox-workspace', 'mode-'.$view, 'is-working' => $view !== 'discuss', 'structure-focused' => $structureFocused]) :class="{ 'drawer-open': drawerOpen }">
            <section wire:key="conversation-pane" class="sandbox-conversation" aria-label="Exercise conversation" x-on:keydown.escape="drawerOpen=false">
                <div class="sandbox-pane-heading"><h2>Conversation</h2><div class="sandbox-pane-actions"><button type="button" class="sandbox-summary-action" aria-label="Summarise discussion" wire:click="summariseDiscussion" wire:loading.attr="disabled" @disabled(!$conversationForm->connection_id || $turns->contains(fn($working) => $working->isActive()))>Summarise</button>@if($view !== 'discuss')<button type="button" class="sandbox-summary-action" wire:click="$set('view', 'discuss')" x-on:click="drawerOpen=false">Open full conversation</button><button type="button" aria-label="Close conversation" x-on:click="drawerOpen=false">×</button>@endif</div></div>
                <div class="sandbox-messages" @if($turns->contains(fn($turn) => $turn->isActive())) wire:poll.2s="refreshConversation" @endif>
                    @forelse($messages->filter(fn($message) => $message->role !== 'assistant' || filled($message->content) || $turns->contains('response_id',$message->id)) as $message)
                        @php($messageTurn = $turns->firstWhere('response_id', $message->id))
                        <article id="sandbox-message-{{ $message->id }}" wire:key="message-{{ $message->id }}" @class(['sandbox-message', 'from-author' => $message->role === 'user'])>
                            <div class="sandbox-message-label">@if($message->isImported())Imported<span>{{ collect([$message->import->title, $message->import->external_origin['client'] ?? null, $message->import->external_origin['model'] ?? null, $message->import->saved_at?->asDate()])->filter()->implode(' · ') }}</span>@else{{ $message->role === 'user' ? 'You' : 'Assistant' }}@if($messageTurn)<span>{{ $messageTurn->provider }} · {{ $messageTurn->model }}</span>@endif @endif</div>
                            @if($message->readsAsDocument())
                                <div class="sandbox-reply-card">
                                    <h3>{{ $message->documentTitle() }}</h3>
                                    <p class="sandbox-help">@if($sections = $message->documentSections()){{ implode(' · ', array_slice($sections, 0, 6)) }} — @endif{{ number_format(mb_strlen($message->content)) }} characters @if($message->citationCount()) · {{ $message->citationCount() }} cited passages @endif</p>
                                    <p class="sandbox-reply-excerpt">{{ $message->documentExcerpt() }}</p>
                                    <flux:modal.trigger name="reply-{{ $message->id }}"><flux:button size="sm" variant="primary" icon="book-open">Read in full</flux:button></flux:modal.trigger>
                                </div>
                                <flux:modal name="reply-{{ $message->id }}" class="sandbox-reader">
                                    <flux:heading size="lg">{{ $message->documentTitle() }}</flux:heading>
                                    <x-studio.markdown :text="$message->content" />
                                </flux:modal>
                            @else
                                <x-studio.markdown :text="$message->content ?: ($messageTurn?->isActive() ? ($messageTurn->progress ?: 'Preparing…') : ($messageTurn?->status === 'paused' ? 'Reading saved; your answer is pending.' : 'No response was generated. Your question is saved.'))" />
                            @endif
                            @if($message->isImported())
                                <flux:button size="sm" icon="document-check" wire:click="reviewImport({{ $message->import_id }})">Review imported items</flux:button>
                            @elseif(filled($message->content))
                                <button type="button" wire:click="messageToBoard({{ $message->id }})" class="sandbox-text-action">Keep as thought</button>
                                @if($messageTurn?->status === 'completed' && empty($messageTurn->proposals['ideas']) && $messageTurn->mode !== 'summary')
                                    @if($recoveredMessages->has($message->id))
                                        <flux:button size="sm" icon="document-check" wire:click="reviewTurn({{ $recoveredMessages->get($message->id)->id }})">Review linked updates</flux:button>
                                    @else
                                    <flux:button size="sm" icon="document-plus" wire:click="prepareMessageUpdates({{ $message->id }})" :disabled="$turns->contains(fn($working) => $working->isActive())" wire:loading.attr="disabled" wire:target="prepareMessageUpdates">Prepare linked updates</flux:button>
                                    @endif
                                @endif
                            @endif
                            @if($messageTurn)@php($turn = $messageTurn) @include('livewire.studio.worlds.sandbox-reading')@endif
                        </article>
                    @empty
                        <div class="sandbox-conversation-empty"><h3>Where would you like to begin?</h3><p>Talk through an idea or ask to build your world. Proposed updates appear here for review before anything becomes canon.</p></div>
                    @endforelse
                    @foreach($turns->whereNull('response_id') as $turn) @include('livewire.studio.worlds.sandbox-reading') @endforeach
                </div>
                <form wire:submit="send" class="sandbox-composer"><details wire:ignore.self class="sandbox-composer-settings" @if(!$conversationForm->connection_id) open @endif><summary>Chat settings · {{ $connections->firstWhere('id',$conversationForm->connection_id)?->name ?? 'Choose a connection' }} · {{ $conversationForm->reading_depth === 'whole' ? 'Whole-source reading' : 'Focused reading' }}</summary><div class="sandbox-composer-settings-body"><div class="sandbox-composer-options"><div class="sandbox-composer-choice"><label for="sandbox-connection">Connection</label><select id="sandbox-connection" wire:model.live="conversationForm.connection_id"><option value="">Choose connection</option>@foreach($connections as $connection)<option value="{{ $connection->id }}">{{ $connection->name }} · {{ $connection->isLocal() ? 'subscription login' : 'API billing' }} · {{ $connection->model }}</option>@endforeach</select>@if($connections->isEmpty())<a href="{{ route('studio.assistant-connections') }}">Add connection</a>@endif</div><div class="sandbox-composer-choice sandbox-model-choice" wire:key="models-for-{{ $exerciseId }}-{{ $conversationForm->connection_id }}" wire:init="loadConnectionModels">
                    @php($selectedConnection = $connections->firstWhere('id', $conversationForm->connection_id))
                    @php($effectiveModel = $conversationForm->model ?: $selectedConnection?->model)
                    @php($selectedModel = collect($modelCatalogue['models'])->firstWhere('id', $effectiveModel))
                    <div wire:key="question-model-{{ $conversationForm->connection_id }}-{{ $modelCatalogue['checked_at'] ?? $modelCatalogue['status'] }}"><flux:select variant="listbox" searchable options:class="assistant-model-options" label="Model for this question" wire:model.live="conversationForm.model" placeholder="Choose a model" :disabled="!$selectedConnection" wire:loading.attr="disabled" wire:target="loadConnectionModels,conversationForm.connection_id">
                        <x-slot name="search"><flux:select.search placeholder="Search models" aria-label="Search models" /></x-slot>
                        @if($selectedConnection)<flux:select.option value="">Connection default</flux:select.option>@endif
                        @if($conversationForm->model && !$selectedModel)<flux:select.option :value="$conversationForm->model">{{ $conversationForm->model }} · previous selection, not refreshed</flux:select.option>@endif
                        @foreach($modelCatalogue['models'] as $model)<flux:select.option :value="$model['id']">{{ $model['name'] }}</flux:select.option>@endforeach
                    </flux:select></div>
                    <p class="sandbox-help" role="status" wire:loading.remove wire:target="loadConnectionModels,conversationForm.connection_id">{{ $modelCatalogue['message'] }}</p>
                    <p class="sandbox-help" role="status" wire:loading wire:target="loadConnectionModels,conversationForm.connection_id">Loading models…</p>
                    <button type="button" class="sandbox-text-action" wire:click="loadConnectionModels(true)" wire:loading.attr="disabled" wire:target="loadConnectionModels,conversationForm.connection_id">Refresh models</button>
                </div><div class="sandbox-composer-choice"><label for="sandbox-effort">Thinking effort</label><select id="sandbox-effort" wire:model="conversationForm.effort" @disabled(empty($selectedModel['efforts']))><option value="default">Provider default</option>@foreach($selectedModel['efforts'] ?? [] as $effort)<option value="{{ $effort }}">{{ ['xhigh'=>'Extra high', 'max'=>'Maximum', 'ultra'=>'Ultra', 'minimal'=>'Minimal', 'none'=>'None'][$effort] ?? ucfirst($effort) }}</option>@endforeach</select><p class="sandbox-help">{{ !empty($selectedModel['efforts']) ? 'Effort choices reported for this model.' : 'This connection uses its provider’s default thinking effort.' }}</p></div><div class="sandbox-reading-choice"><label for="sandbox-reading-depth">Reading</label><select id="sandbox-reading-depth" wire:model.live="conversationForm.reading_depth"><option value="focused">Find relevant passages</option><option value="whole">Read whole source collection</option></select><p class="sandbox-help">Saved reading is reused for unchanged chapters. New reading uses additional requests on this connection.</p></div><label class="sandbox-auto-continue"><input type="checkbox" wire:model="conversationForm.auto_continue"> Continue whole-source reading automatically until my question can be answered</label></div>@include('livewire.studio.assistant-usage', ['totals' => $usageTotals, 'usageConnection' => $connections->firstWhere('id', $conversationForm->connection_id), 'discussion' => true])<p class="sandbox-help">Ask naturally. Draft updates appear beside the answer for your review. Your question is saved before reading; Studio activity keeps your updates.</p></div></details><div class="sandbox-composer-input"><label for="sandbox-message" class="sr-only">Your message</label><textarea id="sandbox-message" wire:model="conversationForm.content" rows="3" placeholder="Think aloud, ask a question, explore a possibility…" x-on:keydown.ctrl.enter.prevent="$wire.send()" x-on:keydown.meta.enter.prevent="$wire.send()"></textarea><flux:button type="submit" variant="primary" icon="paper-airplane" :disabled="$turns->contains(fn($turn) => $turn->isActive())" wire:loading.attr="disabled" wire:target="send">Send</flux:button></div></form>
            </section>
            @if($view === 'discuss')
                @php($activeIdeas = $allIdeas->reject->trashed())
                <aside class="sandbox-rail" aria-label="From this conversation">
                    @if($exercise->purpose)<section><h2>Exploring</h2><p>{{ $exercise->purpose }}</p></section>@endif
                    <section><h2>On the board</h2>
                        @forelse($activeIdeas->groupBy('kind')->sortKeysUsing(fn ($a, $b) => [['person' => 0, 'arc' => 1, 'event' => 2][$a] ?? ($a === 'thought' ? 9 : 3), $a] <=> [['person' => 0, 'arc' => 1, 'event' => 2][$b] ?? ($b === 'thought' ? 9 : 3), $b]) as $kind => $group)
                            <button type="button" class="sandbox-rail-count" wire:click="$set('view', '{{ $kind === 'event' ? 'timeline' : 'board' }}')"><span class="sandbox-kind-dot kind-{{ $kind }}" aria-hidden="true"></span><span>{{ $kind === 'person' ? 'Characters' : \Illuminate\Support\Str::plural(ucfirst($kind)) }}</span><strong>{{ $group->count() }}</strong></button>
                        @empty
                            <p class="sandbox-help">Nothing on the board yet. Keep a message as a thought, or ask for proposals.</p>
                        @endforelse
                        @if($pendingCount = $activeIdeas->whereNull('canonised_at')->where('kind', '!=', 'thought')->count())
                            <button type="button" class="sandbox-rail-count" wire:click="$set('view', 'list')"><span class="sandbox-kind-dot kind-pending" aria-hidden="true"></span><span>Not yet canon</span><strong>{{ $pendingCount }}</strong></button>
                        @elseif($activeIdeas->where('kind', '!=', 'thought')->isNotEmpty())
                            <p class="sandbox-help">Everything on the board is canonised.</p>
                        @endif
                    </section>
                    @if(($thoughts = $activeIdeas->where('kind', 'thought')->sortByDesc('id')->take(5))->isNotEmpty())
                        <section><h2>Kept thoughts</h2>@foreach($thoughts as $thought)<button type="button" class="sandbox-rail-thought" wire:click="editIdea({{ $thought->id }})">{{ \Illuminate\Support\Str::limit(trim(preg_replace('/(?:^|\s)>\s*/', ' ', $thought->title)), 140) }}</button>@endforeach</section>
                    @endif
                </aside>
            @else
            <section wire:key="stage-pane" class="sandbox-stage" aria-label="Sandbox working surface">
                <div class="sandbox-stage-tools">
                    <h2 class="sandbox-view-title">{{ ['board'=>'Idea board','timeline'=>'Event timeline','list'=>'Idea list'][$view] }}</h2>
                    <flux:dropdown position="bottom" align="start">
                        <flux:button size="sm" icon="plus" icon:trailing="chevron-down">Add</flux:button>
                        <flux:menu>
                            <flux:menu.item icon="light-bulb" wire:click="editIdea">New idea</flux:menu.item>
                            <flux:menu.item icon="share" wire:click="$set('panel', 'links')">Connect two ideas</flux:menu.item>
                            <flux:menu.item icon="link" wire:click="$set('panel', 'reference')">Bring in a world record…</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    @if($view === 'board')
                        <flux:button size="sm" icon="squares-2x2" x-on:click="tidyBoard($wire)">Tidy layout</flux:button>
                        <flux:button size="sm" x-on:click="toggleCompact()" x-bind:aria-pressed="compact"><span x-text="compact ? 'Show card notes' : 'Compact cards'">Compact cards</span></flux:button>
                        <label class="sandbox-inline-check"><input type="checkbox" x-model="allLinks"> All connections</label>
                    @endif
                </div>
                <div class="sandbox-filters"><label>Scenario <select wire:model.live="alternativeFilter"><option value="">All possibilities</option>@foreach($ideas->pluck('alternative')->filter()->unique() as $alternative)<option>{{ $alternative }}</option>@endforeach</select></label><label><input type="checkbox" wire:model.live="showArchived"> Archived ideas</label></div>
                @if($visibleIdeas->isEmpty())<div class="sandbox-board-empty"><h2>{{ $showArchived ? 'No archived ideas in this '.$view : ['board'=>'Begin with one possibility','timeline'=>'Your timeline has no events yet','list'=>'Your idea list is empty'][$view] }}</h2><p>{{ $view === 'timeline' ? 'Add an event idea or collect proposed events from the conversation to arrange their sequence here.' : ($view === 'list' ? 'Ideas, questions and proposals you collect will appear here as a list.' : 'Add an idea, reference an established record, or collect proposals from your conversation.') }}</p><flux:button icon="plus" wire:click="editIdea">Add an idea</flux:button></div>
                @elseif($view==='board')
                    @php($nodes=$visibleIdeas->map(fn($idea)=>['id'=>$idea->id,'revision'=>$idea->revision,'x'=>$idea->x,'y'=>$idea->y])->values())
                    <div class="sandbox-board-scroll"><div class="sandbox-board" wire:key="board-{{ md5($nodes->toJson().$links->toJson()) }}" x-data="sandboxBoard(@js($nodes))" style="min-width:{{ max(900,($visibleIdeas->max('x') ?? 0)+280) }}px;min-height:{{ max(600,($visibleIdeas->max('y') ?? 0)+230) }}px">
                        <svg class="sandbox-wires" :class="{ 'show-all': allLinks }" aria-hidden="true" width="100%" height="100%"><defs><marker id="sandbox-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="currentColor" /></marker></defs>@foreach($links as $link)@if($visibleIdeas->contains('id',$link->source_id) && $visibleIdeas->contains('id',$link->target_id))<path :d="path({{ $link->source_id }},{{ $link->target_id }},compact)" @class(['is-related' => $ideaId && in_array($ideaId, [$link->source_id, $link->target_id], true)]) marker-end="url(#sandbox-arrow)"><title>{{ $link->label }}</title></path>@endif@endforeach</svg>
                        @foreach($visibleIdeas as $idea)<article wire:key="idea-{{ $idea->id }}" :style="style({{ $idea->id }})" @class(['sandbox-piece', 'is-selected' => $ideaId===$idea->id]) :class="{ 'is-compact': compact }"><button class="sandbox-piece-handle" type="button" aria-label="Move {{ $idea->title }} with arrow keys or drag" @pointerdown="start($event,{{ $idea->id }})" @keydown.arrow-left.prevent="nudge({{ $idea->id }},-20,0)" @keydown.arrow-right.prevent="nudge({{ $idea->id }},20,0)" @keydown.arrow-up.prevent="nudge({{ $idea->id }},0,-20)" @keydown.arrow-down.prevent="nudge({{ $idea->id }},0,20)"><span><span class="sandbox-kind-dot kind-{{ $idea->kind }}" aria-hidden="true"></span>{{ $idea->kind === 'person' ? 'Character' : ucfirst($idea->kind) }}</span><span aria-hidden="true">⠿</span></button><button class="sandbox-piece-title" type="button" wire:click="editIdea({{ $idea->id }})">{{ $idea->title }}</button><p>{{ \Illuminate\Support\Str::limit($idea->uncertainty ?: strip_tags($idea->notes_md ?? ''),110) }}</p><div class="sandbox-piece-footer"><div class="sandbox-piece-meta">@if($idea->canonised_at)<span>Canonised</span>@elseif($idea->target_key)<span>Linked record</span>@else<span>{{ $idea->origin==='author' ? 'Your idea' : $idea->originLabel() }}</span>@endif @if($idea->alternative)<span>{{ $idea->alternative }}</span>@endif</div>@if(!$idea->canonised_at && !$showArchived)<label class="sandbox-select-piece"><input type="checkbox" wire:model.live="selectedIdeas" value="{{ $idea->id }}"> Include in review</label>@endif<flux:button size="xs" variant="subtle" :icon="$idea->trashed() ? 'arrow-uturn-left' : 'archive-box'" :tooltip="$idea->trashed() ? 'Restore to board' : 'Remove from board'" :aria-label="$idea->trashed() ? 'Restore to board' : 'Remove from board'" wire:click="archiveIdea({{ $idea->id }}, {{ $idea->revision }}, {{ $idea->trashed() ? 'false' : 'true' }})" /></div></article>@endforeach
                    </div></div>
                @elseif($view==='timeline')
                    <div class="sandbox-timeline" x-data="{ dragged:null, dragRevision:null }"><p class="sandbox-help">Drag an event onto another to place it before that event, or use Earlier / Later. Spacing shows sequence, not elapsed time.</p>
                    @foreach($visibleIdeas->where('kind','event')->sortBy('position')->groupBy(fn($idea)=>($idea->lane ?: 'Events').($idea->alternative ? ' · '.$idea->alternative : '')) as $lane=>$events)
                        <section class="sandbox-timeline-lane"><h2>{{ $lane }}</h2><ol>
                        @foreach($events->values() as $index=>$idea)
                            <li draggable="{{ $idea->canEdit() ? 'true' : 'false' }}" @dragstart="dragged={{ $idea->id }};dragRevision={{ $idea->revision }};$event.dataTransfer.effectAllowed='move'" @dragover.prevent @drop.prevent="if(dragged && dragged!=={{ $idea->id }}) $wire.moveEvent(dragged,dragRevision,{{ $idea->id }},{{ $idea->revision }});dragged=null" @dragend="dragged=null">
                                <span class="sandbox-sequence">{{ $idea->position }}</span><button wire:click="editIdea({{ $idea->id }})"><strong>{{ $idea->title }}</strong><span>{{ $idea->date_label ?: 'Date undecided' }}</span></button>
                                @if($idea->canEdit())<div class="sandbox-timeline-move">@if($index>0 && $events->values()[$index-1]->canEdit())<button aria-label="Move {{ $idea->title }} earlier" wire:click="moveEvent({{ $idea->id }},{{ $idea->revision }},{{ $events->values()[$index-1]->id }},{{ $events->values()[$index-1]->revision }})">← Earlier</button>@endif @if($index<$events->count()-1 && $events->values()[$index+1]->canEdit())<button aria-label="Move {{ $idea->title }} later" wire:click="moveEvent({{ $idea->id }},{{ $idea->revision }},{{ $events->values()[$index+1]->id }},{{ $events->values()[$index+1]->revision }},true)">Later →</button>@endif</div>@endif
                                <label><input type="checkbox" wire:model.live="selectedIdeas" value="{{ $idea->id }}" @disabled($idea->canonised_at)> Review</label>
                            </li>
                        @endforeach</ol></section>
                    @endforeach
                    @if(!$visibleIdeas->contains('kind','event'))<p>No proposed events yet. Add an event or ask the assistant to collect a sequence.</p>@endif</div>
                @else<div class="sandbox-idea-list">@foreach($visibleIdeas as $idea)<div><label><input type="checkbox" wire:model.live="selectedIdeas" value="{{ $idea->id }}" @disabled($idea->canonised_at)> <span class="sr-only">Include {{ $idea->title }} in canon review</span></label><button wire:click="editIdea({{ $idea->id }})"><strong>{{ $idea->title }}</strong><span>{{ $idea->kind === 'person' ? 'Character' : ucfirst($idea->kind) }} · {{ $idea->canonised_at ? 'Canonised' : ($idea->uncertainty ?: 'Sandbox') }}</span></button></div>@endforeach</div>@endif
                <button type="button" class="sandbox-drawer-toggle" x-on:click="drawerOpen = !drawerOpen" x-bind:aria-expanded="drawerOpen"><flux:icon.chat-bubble-left-right variant="mini" />Conversation · {{ $messages->where('role', 'user')->count() }}</button>
            </section>
            @endif
            @if($panel !== '')
            <aside wire:key="details-pane" class="sandbox-details" aria-label="Selected details"><div class="sandbox-pane-heading"><h2>{{ ['idea'=>($ideaId ? 'Idea details' : 'New idea'),'exercise'=>'Exercise & context','links'=>'Connections','naming'=>'Naming conventions','canon'=>'Review canon','history'=>'Accepted changes','source'=>'Source passages','reference'=>'Bring in a world record'][$panel] ?? 'Details' }}</h2>@if($structureFocused)<button type="button" class="sandbox-back-to-workspace" wire:click="closeDetails">Back to workspace</button>@else<button type="button" aria-label="Close details" wire:click="closeDetails">×</button>@endif</div>
                @if($panel==='source')@include('livewire.studio.worlds.sandbox-source')
                @elseif($panel==='exercise')@include('livewire.studio.worlds.sandbox-exercise')
                @elseif($panel==='links')@include('livewire.studio.worlds.sandbox-links')
                @elseif($panel==='naming')@include('livewire.studio.worlds.sandbox-naming')
                @elseif($panel==='canon')@include('livewire.studio.worlds.sandbox-canon')
                @elseif($panel==='history')@include('livewire.studio.worlds.sandbox-history')
                @elseif($panel==='reference')<div class="sandbox-form"><div><label for="sandbox-reference">World record</label><select id="sandbox-reference" wire:model="referenceKey"><option value="">Choose world record</option>@foreach($catalogue as $record)@if(!($record['archived'] ?? false))<option value="{{ $record['key'] }}">{{ ucfirst($record['kind']) }} · {{ $record['name'] }}</option>@endif @endforeach</select></div><p class="sandbox-help">The record appears on the board as a linked proposal. Edits there stay proposals until you review them.</p><div><flux:button size="sm" variant="primary" icon="link" wire:click="reference">Reference on board</flux:button></div></div>
                @else @include('livewire.studio.worlds.sandbox-idea') @endif
            </aside>
            @endif
        </div>
    @endif
    </div>
    @include('livewire.studio.worlds.sandbox-import')
</div>
