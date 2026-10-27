<div x-data="manuscriptEditor(@js($initialChapter))" @studio-reference-updated.window="manuscriptRevision = $event.detail.revision; worldCatalogue = $event.detail.catalogue; updatePreview()" x-on:studio-draft-finished.window="load($event.detail.chapter)" :class="{ 'studio-focused': focus }">
    <header class="studio-header">
        <a href="{{ route('studio.index') }}" class="studio-brand" @click="guardLink($event)">Dreamer’s Palette</a>
        <nav class="studio-header-links" aria-label="Application">@can('worlds.view')<a href="{{ $manuscript->world ? route('studio.world', ['world' => $manuscript->world_id, 'manuscript' => $manuscript->id]) : route('studio.worlds') }}" @click="guardLink($event)">World</a>
@endcan
<a href="/dashboard/books" @click="guardLink($event)">Publishing</a><span>{{ auth()->user()->name }}</span></nav>
    </header>
    <div class="studio-manuscript-bar">
        <div class="studio-tabs" aria-label="Manuscript views">
            <button @click="showTab('write')" @class(['active' => $tab === 'write'])><flux:icon.pencil-square variant="micro" /> Write</button>
            <button @click="showTab('drafts')" @class(['active' => $tab === 'drafts'])><flux:icon.rectangle-stack variant="micro" /> Drafts <span>{{ $drafts->count() }}</span></button>
        </div>
        @can('export', $manuscript)<flux:button size="sm" icon="arrow-down-tray" href="{{ route('studio.compile', $manuscript) }}" @click="guardLink($event)">Compile</flux:button>
@endcan

        @can('update', $manuscript)<flux:button size="sm" icon="cog-6-tooth" href="{{ route('studio.manuscript.details', $manuscript) }}" @click="guardLink($event)">Details</flux:button>

@endcan

<div class="studio-working"><span class="status-dot"></span> Draft {{ $manuscript->working_draft_number }} <span class="studio-muted">In progress</span></div>
        @can('finish', $manuscript)<flux:button size="sm" icon="check" @click="finish()" x-bind:disabled="busy || conflict">Finish draft</flux:button>
@endcan

    </div>
    <div class="studio-alert" x-show="error" x-cloak role="alert"><div><strong x-text="conflict ? 'Your local writing is safe. A newer version needs your attention.' : 'Your writing needs attention.'"></strong><p x-text="error"></p></div><button @click="downloadLocal()">Download local copy</button><button x-show="!conflict" @click="flush()">Retry save</button><button x-show="conflict" @click="compareServer()">Compare server copy</button></div>
    <div class="studio-recovery" x-show="recovery" x-cloak><div><strong>There is writing saved on this device.</strong><p>Compare it with this server version before continuing.</p></div><button @click="showRecovery = true">Compare copies</button><button @click="restoreRecovery()">Use local copy</button><button @click="discardRecovery()">Keep server copy</button></div>
    @error('structure')<p class="studio-alert">{{ $message }}</p>@enderror
    @if($tab === 'write')
        <div class="studio-workspace" :class="{ 'chapters-open': chaptersOpen }">
            <aside class="studio-chapters" aria-label="Chapters">
                <div class="chapter-list-heading"><div><h2>Manuscript</h2><span>{{ $chapters->count() }} {{ Str::plural('chapter', $chapters->count()) }}</span></div><button class="studio-icon-button" title="Add chapter" aria-label="Add chapter" @click="newChapter()" x-bind:disabled="!canWrite"><flux:icon.plus variant="micro" /></button></div>
                <button class="studio-archive-toggle" wire:click="$toggle('archivedChapters')">{{ $archivedChapters ? 'Active chapters' : 'Archived chapters' }} @if(!$archivedChapters)({{ $archivedChapterRows->count() }})
@endif
</button>
                @if($archivedChapters)<div class="chapter-list">@forelse($archivedChapterRows as $archivedChapter)<div class="chapter-list-row"><span>{{ $archivedChapter->title }}</span>@can('update', $manuscript)<flux:button size="sm" icon="arrow-uturn-left" @click="archiveChapter({{ $archivedChapter->id }}, false)">Restore</flux:button>
@endcan
</div>
@empty
<p>No archived chapters</p>@endforelse</div>
@else

                <div class="chapter-list">
                    @foreach($chapters as $chapter)
                        <div class="chapter-list-row" wire:key="chapter-row-{{ $chapter->id }}" :class="{ selected: chapterId === {{ $chapter->id }} }">
                            <button class="chapter-select" @click="switchChapter({{ $chapter->id }})"><span class="chapter-number">{{ $loop->iteration }}</span><span><span class="chapter-name">{{ $chapter->title }}</span>@if($chapter->summary_md)<span class="chapter-description">{{ Str::limit(strip_tags($chapter->summary_md), 75) }}</span>
@endif
</span></button>
                            <div class="chapter-move">@can('update', $manuscript)<button aria-label="Archive {{ $chapter->title }}" @click="archiveChapter({{ $chapter->id }}, true)"><flux:icon.archive-box variant="micro" /></button>
@endcan
<button aria-label="Move {{ $chapter->title }} up" @disabled($loop->first || auth()->user()->cannot('update', $manuscript)) @click="move({{ $chapter->id }}, 'up')"><flux:icon.chevron-up variant="micro" /></button><button aria-label="Move {{ $chapter->title }} down" @disabled($loop->last || auth()->user()->cannot('update', $manuscript)) @click="move({{ $chapter->id }}, 'down')"><flux:icon.chevron-down variant="micro" /></button></div>
                        </div>
                    
@endforeach

                </div>
                
@endif

            </aside>
            <main id="studio-main" class="studio-writing">
                <div class="writing-toolbar">
                    <button class="studio-mobile-toggle" @click="chaptersOpen = !chaptersOpen"><flux:icon.bars-3 variant="micro" /> Chapters</button>
                    <div class="editor-modes" aria-label="Writing mode"><button @click="setMode('write')" :aria-pressed="mode === 'write'">Write</button><button @click="setMode('preview')" :aria-pressed="mode === 'preview'">Preview</button><button @click="setMode('split')" :aria-pressed="mode === 'split'">Split</button></div>
@if($canBrowseWorld)<button @click="referenceBrowser()"><flux:icon.book-open variant="micro" /> Encyclopedia</button>@elseif(!$manuscript->world_id)@can('worlds.view')<button @click="linkWorld()"><flux:icon.link variant="micro" /> Link world</button>
@endcan
 
@endif

                    <span class="writing-save" role="status" x-text="status"></span>
                    <button @click="flush()" :disabled="busy || conflict || !dirty || !canWrite"><flux:icon.cloud-arrow-up variant="micro" /> Save</button>
                    <button @click="focus = !focus; chaptersOpen = false" :aria-pressed="focus"><flux:icon.arrows-pointing-out variant="micro" /><span x-text="focus ? 'Exit focus' : 'Focus'"></span></button>
                </div>
                <div wire:key="chapter-surface-{{ $chapterId ?? 'none' }}-world-{{ $manuscript->world_id ?? 'none' }}">
                @if($chapterId)
                    <div class="writing-document" wire:ignore>
                        <div class="writing-document-heading"><span class="studio-eyebrow">Chapter <span x-text="chapterNumber"></span></span><label for="chapter-title" class="sr-only">Chapter title</label><input id="chapter-title" class="writing-title" x-bind:readonly="!canWrite" x-model="title" @input="changed()" maxlength="255" aria-label="Chapter title" /></div>
                        <div class="markdown-toolbar" aria-label="Markdown tools"><button x-bind:disabled="!canWrite" @mousedown.prevent @click="wrap('**')" title="Bold (Ctrl or Cmd B)"><strong>B</strong><span class="sr-only">Bold</span></button><button x-bind:disabled="!canWrite" @mousedown.prevent @click="wrap('*')" title="Italic (Ctrl or Cmd I)"><em>I</em><span class="sr-only">Italic</span></button><button x-bind:disabled="!canWrite" @mousedown.prevent @click="prefix('## ')" title="Heading">H2</button><button x-bind:disabled="!canWrite" @mousedown.prevent @click="prefix('> ')" title="Quotation"><flux:icon.chat-bubble-bottom-center-text variant="micro" /><span class="sr-only">Quote</span></button><button x-bind:disabled="!canWrite" @mousedown.prevent @click="prefix('- ')" title="List"><flux:icon.list-bullet variant="micro" /><span class="sr-only">List</span></button><span></span><button @mousedown.prevent @click="openAssist()" :disabled="!selection || !canWrite" class="assist-trigger"><flux:icon.language variant="micro" /> Assist</button>@if($canBrowseWorld)<button @mousedown.prevent @click="referenceBrowser(true)" :disabled="!selection || !canWrite"><flux:icon.link variant="micro" /> Link to world</button>
@endif
</div>
                        <div class="markdown-panes" :class="{ split: mode === 'split' }">
                            <textarea x-ref="writing" x-bind:readonly="!canWrite" x-model="content" @input="changed()" @select="captureSelection()" @keyup="captureSelection()" @mouseup="captureSelection()" @keydown="shortcut($event)" x-show="mode !== 'preview'" class="markdown-input" aria-label="Chapter Markdown" spellcheck="true"></textarea>
                            <article class="markdown-preview studio-prose" x-show="mode !== 'write'" x-html="previewHtml" @click="inspectPreviewReference($event)" aria-label="Rendered Markdown preview"></article>
                        </div>
                        <details class="chapter-summary"><summary>Chapter summary <span>Optional · private</span></summary><textarea x-bind:readonly="!canWrite" x-model="summary" @input="changed()" aria-label="Private chapter summary" maxlength="50000"></textarea></details>
                        <div class="writing-document-footer"><span><span x-text="wordCount()"></span> words</span><span>Markdown · Draft <span x-text="draftNumber"></span></span></div>
                    </div>
                
@else

                    <div class="studio-empty"><h2>No chapters</h2><flux:button @click="newChapter()" x-bind:disabled="!canWrite" icon="plus">Add a chapter</flux:button></div>
                
@endif

                </div>
            </main>
        </div>
    
@else

        <main id="studio-main" class="drafts-page">
            <div class="drafts-heading"><div><h1>Manuscript drafts</h1></div></div>
            <div class="active-draft-row"><div class="draft-symbol"><flux:icon.pencil-square /></div><div><h2>Draft {{ $manuscript->working_draft_number }} <span class="draft-label">In progress</span></h2><p>{{ $chapters->count() }} {{ Str::plural('chapter', $chapters->count()) }}</p></div><flux:button variant="primary" icon="arrow-right" @click="showTab('write')">Continue writing</flux:button></div>
            <h2 class="finished-heading">Finished drafts <span>{{ $drafts->count() }}</span></h2>
            @forelse($drafts as $draft)
                <div class="finished-draft-row" wire:key="draft-{{ $draft->id }}"><flux:icon.lock-closed class="draft-lock" /><div><h3>{{ $draft->name }}</h3><p>Draft {{ $draft->number }} · {{ $draft->chapters_count }} {{ Str::plural('chapter', $draft->chapters_count) }} · {{ $draft->created_at->asDateTime() }} · {{ $draft->creator?->name ?? 'Author' }}</p>@if($draft->note)<p class="draft-note">{{ $draft->note }}</p>
@endif
</div><div class="draft-row-actions">@can('undoFinish', $manuscript)@if($manuscript->canUndoFinish($draft))<flux:button size="sm" icon="arrow-uturn-left" @click="undoFinish({{ $draft->id }})" x-bind:disabled="busy || undoing || dirty || conflict || !!recovery">Undo finish</flux:button>
@endif

@endcan
@can('restoreDraft', $manuscript)<flux:button size="sm" icon="arrow-path" href="{{ route('studio.draft.restore', [$manuscript, $draft->id]) }}" @click="guardLink($event)">Restore</flux:button>

@endcan

<flux:button size="sm" wire:click="readDraft({{ $draft->id }})" icon="document-magnifying-glass">Read & compare</flux:button>@can('export', $manuscript)<flux:button size="sm" href="{{ route('studio.draft.export', [$manuscript, $draft->id]) }}" icon="arrow-down-tray">Markdown</flux:button>
@endcan
</div></div>
            
@empty

                <p class="studio-empty-inline">No finished drafts yet. When you’re ready, finish Draft 1 to lock it and start Draft 2 from the same writing.</p>
            @endforelse

        </main>
    
@endif

    <flux:modal wire:model="linkingWorld" class="md:w-[32rem]" :dismissible="false" :closable="false">
        <form wire:submit="attachWorld" class="studio-form">
            <flux:heading size="lg">Link a world</flux:heading>
            <flux:select label="World" wire:model="attachWorldForm.world_id"><flux:select.option value="0">Choose world</flux:select.option>@foreach($availableWorlds as $availableWorld)<flux:select.option value="{{ $availableWorld->id }}">{{ $availableWorld->name }}</flux:select.option>
@endforeach
</flux:select>
            @error('linkForm.manuscript_id')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
            @if($availableWorlds->isEmpty())<a href="{{ route('studio.worlds') }}" @click="guardLink($event)">Create a world</a>
@endif

            <div class="studio-form-actions"><flux:button wire:click="$set('linkingWorld', false)">Cancel</flux:button><flux:button type="submit" variant="primary" icon="link">Link world</flux:button></div>
        </form>
    </flux:modal>
    <flux:modal wire:model="referencesOpen" class="studio-reference-modal" :dismissible="!$creatingReference" :closable="!$creatingReference">
        <flux:heading size="lg">World encyclopedia</flux:heading>
        <p role="status" class="development-notice">{{ $referenceNotice }}</p>
        @if($referenceForm->wording)<p class="reference-selection">Selected: <strong>{{ $referenceForm->wording }}</strong></p>
@endif

        @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
        @error('referenceForm.wording')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
        @if($creatingReference)
            <form wire:submit="createReferenceEntry" class="studio-form">
                <flux:input label="Name" wire:model="entryForm.name" />
                <flux:checkbox label="Character" wire:model.live="entryForm.character" />
                @if(!$entryForm->character)<flux:select label="Type" wire:model="entryForm.type_id"><flux:select.option value="0">Choose type</flux:select.option>@foreach($referenceTypes as $type)<flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
@endforeach
</flux:select>
@endif

                <flux:textarea label="Notes · Markdown" wire:model="entryForm.notes_md" rows="4" />
                <div class="studio-form-actions"><flux:button wire:click="$set('creatingReference', false)">Cancel</flux:button><flux:button type="submit" variant="primary">Create and link</flux:button></div>
            </form>
        
@else

            <flux:input label="Search names and aliases" wire:model.live.debounce.250ms="referenceSearch" icon="magnifying-glass" />
            @if($referenceForm->wording)@can('update', $manuscript->world)<flux:button icon="plus" wire:click="beginReferenceEntry">Create from selection</flux:button>
@endcan
 
@endif

            <div class="reference-browser">
                <div class="reference-results">@forelse($referenceMatches as $match)<div class="reference-result" wire:key="reference-{{ $match['key'] }}"><button wire:click="inspectReference('{{ $match['key'] }}')"><strong>{{ $match['name'] }}</strong><span>{{ $match['type'] }}</span></button>@if($referenceForm->wording)@can('update', $manuscript)<flux:button size="sm" icon="link" wire:click="linkReference('{{ $match['key'] }}')">Link</flux:button>@can('update', $manuscript->world)<flux:button size="sm" wire:click="linkReference('{{ $match['key'] }}', true)">Add as alias</flux:button>
@endcan
 
@endcan
 
@endif
</div>
@empty
<p>No matching entries</p>@endforelse</div>
                @if($inspectedTarget)<article class="reference-reading"><h2>{{ $inspectedTarget->name }}</h2>@if($inspectedTarget->trashed())<p class="studio-alert">Archived</p>
@endif
<div class="world-aliases">@foreach($inspectedTarget->aliases as $alias)<span>{{ $alias }}</span>
@endforeach
</div><x-studio.markdown :text="$inspectedTarget->notes_md ?? ''" /></article>
@endif

            </div>
            @if($chapterReferenceCount)<details class="reference-links"><summary>Links in this chapter ({{ $chapterReferenceCount }})</summary><flux:select label="Show links" wire:model.live.boolean="archivedReferences"><flux:select.option value="false">Active</flux:select.option><flux:select.option value="true">Archived</flux:select.option></flux:select>@foreach($chapterReferences as $reference)<div class="reference-result"><button wire:click="inspectReference('{{ $reference->target_kind }}:{{ $reference->target_id }}')">{{ $reference->wording }} → {{ $reference->target_name }}</button><span>{{ $referenceChapter && $reference->isResolved($referenceChapter->content_md) ? 'Linked' : 'Needs review — passage changed' }}</span>@can('update', $manuscript)<flux:button size="sm" :icon="$reference->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveReference({{ $reference->id }}, {{ $reference->trashed() ? 'false' : 'true' }})">{{ $reference->trashed() ? 'Restore' : 'Archive link' }}</flux:button>
@endcan
</div>
@endforeach
</details>
@endif

        
@endif

    </flux:modal>
    <flux:modal wire:model="readingDraft" class="studio-draft-modal">
            @if($selectedDraft)
                <section class="draft-reading" aria-label="Draft comparison">
                    @if($selectedDraft->world_context)<details class="world-draft-context"><summary>World context · {{ $selectedDraft->world_context['world_name'] }}</summary>@if(isset($selectedDraft->world_context['development']))<x-studio.world-development-snapshot :snapshot="$selectedDraft->world_context['development']" :manuscript="$manuscript" :draft="$selectedDraft" />
@endif
@foreach($selectedDraft->world_context['systems'] as $capturedSystem)<p><a href="{{ route('studio.world', ['world' => $selectedDraft->world_context['world_id'], 'system' => $capturedSystem['system_id'], 'version' => $capturedSystem['version_id']]) }}">{{ $capturedSystem['name'] }} · Version {{ $capturedSystem['revision'] }}</a></p>@foreach($capturedSystem['variations'] as $capturedVariation)<div class="world-variation"><h4>{{ $capturedVariation['source_rule']['title'] }} · {{ $capturedVariation['mode']['name'] }}</h4><x-studio.markdown :text="$capturedVariation['text_md']" /><x-studio.markdown :text="$capturedVariation['reason_md']" /></div>
@endforeach
 
@endforeach
</details>
@endif
<div class="draft-reading-heading"><div><flux:heading size="lg">{{ $selectedDraft->name }} compared with Draft {{ $manuscript->working_draft_number }}</flux:heading></div><flux:icon.lock-closed /></div>
                    @foreach($selectedDraft->chapters as $snapshot)
                        @php($current = $currentChapters->get($snapshot->source_chapter_id))
                        <details class="draft-chapter-compare" @if($loop->first) open 
@endif
><summary>{{ $snapshot->title }} <span>{{ ! $current ? 'Not in current manuscript' : (($current->content_md === $snapshot->content_md && $current->summary_md === $snapshot->summary_md && $current->title === $snapshot->title && $current->position === $snapshot->position) ? 'Unchanged' : 'Changed') }}</span></summary>
                            <div class="draft-compare-columns"><article><h4>{{ $selectedDraft->name }} · Locked</h4><div class="studio-prose">{!! Str::markdown($snapshot->content_md, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>@if($snapshot->summary_md)<p class="compare-summary"><strong>Summary</strong><br>{{ $snapshot->summary_md }}</p>
@endif
</article><article><h4>Draft {{ $manuscript->working_draft_number }} · Current</h4>@if($current)<div class="studio-prose">{!! Str::markdown($current->content_md, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>@if($current->summary_md)<p class="compare-summary"><strong>Summary</strong><br>{{ $current->summary_md }}</p>
@endif
 
@else
 <p>This chapter is no longer in the active manuscript.</p>
@endif
</article></div>
                        </details>
                    
@endforeach

                    @foreach($currentChapters->except($selectedDraft->chapters->pluck('source_chapter_id')->all()) as $added)<p class="studio-notice">Added in the current draft: {{ $added->title }}</p>
@endforeach

                </section>
            
@endif

    </flux:modal>
    <flux:modal wire:model="finishing" class="md:w-[32rem]">
        <form wire:submit="finishDraft" class="studio-form"><div><flux:heading size="lg">Finish Draft {{ $finishForm->draft_number }}</flux:heading><flux:text>This saves and locks all server-saved chapters and summaries. Draft {{ $finishForm->draft_number + 1 }} starts with the same writing. Nothing is published.</flux:text></div><flux:input label="Draft name" wire:model="finishForm.name" maxlength="160" /><flux:textarea label="Draft note" badge="Optional" wire:model="finishForm.note" rows="3" /><div class="studio-form-actions"><flux:button wire:click="$set('finishing', false)">Keep writing</flux:button><flux:button type="submit" variant="primary" icon="lock-closed">Finish & start next draft</flux:button></div></form>
    </flux:modal>
    <div class="studio-assist" x-show="assistOpen" x-trap.inert.noscroll="assistOpen" x-cloak role="dialog" aria-modal="true" aria-label="Writing assistance" @keydown.escape.window="assistOpen = false">
        <div class="assist-backdrop" @click="assistOpen = false"></div><section class="assist-drawer"><div class="assist-heading"><div><h2>Writing assistance</h2></div><button class="studio-icon-button" @click="assistOpen = false" aria-label="Close assistance"><flux:icon.x-mark /></button></div><p class="assist-selection" x-text="selection"></p><div class="assist-modes"><button @click="lookup('grammar')">Grammar</button><button @click="lookup('synonyms')">Thesaurus</button><button @click="lookup('spelling')">Spelling</button><button @click="lookup('definitions')">Dictionary</button></div><p class="studio-muted" x-text="assistStatus" role="status"></p><template x-for="suggestion in suggestions" :key="suggestion.id"><div class="assist-result"><span class="assist-result-type" x-show="suggestion.type" x-text="({ synonym: 'Synonym', antonym: 'Antonym', related: 'Related word', spelling: 'Spelling' })[suggestion.type] || suggestion.partOfSpeech || ''"></span><p x-text="suggestion.message || suggestion.definition || suggestion.label"></p><template x-if="suggestion.replacements"><div class="assist-replacements"><template x-for="replacement in suggestion.replacements" :key="replacement.id"><button @click="replaceSuggestion(replacement.replacement, suggestion.offset, suggestion.length)" x-text="replacement.label"></button></template></div></template><template x-if="suggestion.replacement && !suggestion.replacements"><button @click="replaceSuggestion(suggestion.replacement)" x-text="suggestion.label"></button></template></div></template></section>
    </div>
    <div class="studio-local-compare" x-show="showRecovery" x-trap.inert.noscroll="showRecovery" @keydown.escape.window="showRecovery = false" x-cloak role="dialog" aria-modal="true" aria-label="Compare local and server writing"><div class="assist-backdrop" @click="showRecovery = false"></div><section><div class="assist-heading"><h2>Compare copies</h2><button @click="showRecovery = false" aria-label="Close comparison"><flux:icon.x-mark /></button></div><p>Both copies remain available. Download local writing before reloading if you need to combine them.</p><div class="draft-compare-columns"><article><h3>Local writing</h3><pre x-text="recovery?.content_md ?? content"></pre></article><article><h3>Server writing</h3><pre x-text="serverText"></pre></article></div><div class="studio-form-actions"><button @click="downloadLocal()">Download local copy</button><button @click="showRecovery = false">Back to writing</button></div></section></div>
</div>
