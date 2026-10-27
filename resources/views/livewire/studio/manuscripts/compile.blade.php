<div>
    <header class="studio-header"><a href="{{ route('studio.index') }}" class="studio-brand">Dreamer’s Palette</a><nav class="studio-header-links" aria-label="Studio"><a href="{{ route('studio.manuscript', $manuscript) }}">Back to writing</a><a href="{{ route('studio.worlds') }}">Worlds</a><a href="/dashboard">Dashboard</a></nav></header>
    <div class="compile-heading"><div><h1>Compile</h1><p>{{ $manuscript->title }}</p></div><span>{{ count($form->chapters) }} of {{ $chapters->count() }} chapters</span></div>
    <form wire:submit="prepare" class="compile-workspace">
        <aside class="compile-options" aria-label="Compile options">
            <flux:select label="Source" wire:model.live="form.source"><option value="working">Working draft {{ $manuscript->working_draft_number }}</option>@foreach($drafts as $draft)<option value="draft:{{ $draft->id }}">{{ $draft->name }} · Finished</option>
@endforeach
</flux:select>
            @if($form->source === 'working')<p class="compile-source-note">Uses writing saved to the server.</p><flux:button size="sm" icon="arrow-path" wire:click="refreshSource">Refresh source</flux:button>
@endif

            <flux:error name="form.source" />
            @error('source')<p class="studio-alert" role="alert">{{ $message }}</p>
@enderror

            <flux:select label="Format" wire:model.live="form.format"><option value="markdown">Markdown</option><option value="pdf">PDF</option><option value="review">Review packet (.zip)</option></flux:select>
            <flux:error name="form.format" />
            <fieldset class="compile-inclusions"><legend>Include</legend><flux:checkbox label="Writing" wire:model.live="form.prose" /><flux:checkbox label="Chapter summaries" wire:model.live="form.summaries" />
@if($form->format === 'review')<flux:checkbox label="Captured world and maps" wire:model.live="form.world" />
@endif</fieldset>
            <flux:error name="form.prose" /><flux:error name="form.summaries" /><flux:error name="form.world" />
            <section class="compile-chapters"><div class="compile-section-heading"><h2>Chapters</h2><div><button type="button" wire:click="selectAll(true)">All</button><button type="button" wire:click="selectAll(false)">None</button></div></div>
                <p class="compile-source-note">Exports follow manuscript order.</p>
                @forelse($chapters as $chapter)<div class="compile-chapter" wire:key="compile-{{ $form->source }}-chapter-{{ $chapter->id }}"><flux:checkbox aria-label="Include {{ $chapter->title }}" wire:model.live="form.chapters" value="{{ $chapter->id }}" /><button type="button" wire:click="$set('previewChapter', {{ $chapter->id }})" @class(['active' => $previewChapter === $chapter->id])>{{ $chapter->title }}</button></div>
@empty
<p>No chapters in this source.</p>
@endforelse

                <flux:error name="form.chapters" /><flux:error name="form.chapters.*" />
            </section>
            <flux:button type="submit" variant="primary" icon="document-arrow-down" wire:loading.attr="disabled" wire:target="prepare"><span wire:loading.remove wire:target="prepare">Prepare export</span><span wire:loading wire:target="prepare">Preparing export…</span></flux:button>
            @error('export')<p class="studio-alert" role="alert">{{ $message }}</p>
@enderror

            @if($readyId && isset($links[$readyId]))<div class="compile-ready" role="status"><strong>Export ready</strong><flux:button icon="arrow-down-tray" href="{{ $links[$readyId] }}">Download {{ match($form->format) { 'pdf' => 'PDF', 'review' => 'review packet', default => 'Markdown' } }}</flux:button><p>This copy stays available here for 24 hours.</p></div>
@endif

        </aside>
        <main id="studio-main" class="compile-preview">
            @if($notice)<p role="status">{{ $notice }}</p>
            @endif
            @if($assistantConfig)
                <section class="compile-ready"><h2>Assistant connection</h2><p>This secret gives read-only access to packet {{ $assistantPacketId }} until its export expires. It works with a local MCP client on this computer. Copy it into your client’s MCP settings; no model API key is needed.</p>
                    <details open><summary>Codex</summary><flux:textarea label="Codex MCP configuration (secret)" readonly rows="9">{{ $assistantCodexConfig }}</flux:textarea></details>
                    <details><summary>Claude</summary><flux:textarea label="Claude MCP configuration (secret)" readonly rows="12">{{ $assistantConfig }}</flux:textarea></details>
                    <p>Shown only for this page session. Enabling again replaces the previous secret.</p>
                </section>
            @endif
            <div class="compile-section-heading"><h2>Chapter preview</h2><span>{{ $form->format === 'pdf' ? 'PDF starts each chapter on a new page' : 'Markdown source is preserved' }}</span></div>
            @if($preview)<article class="compile-page"><h1>{{ $preview->title }}</h1>
@if(!in_array($preview->id, $form->chapters))
<p class="compile-source-note">Not included in this export.</p>
@endif
@if($form->prose)<x-studio.markdown :text="$preview->content_md" />
@endif
 @if($form->summaries)<section class="compile-summary"><h2>Chapter summary</h2><x-studio.markdown :text="$preview->summary_md ?: '_No summary written._'" /></section>
@endif
</article>
@else
<p class="studio-empty-inline">Choose a chapter to preview.</p>
@endif

            @if($form->format === 'pdf')<p class="compile-source-note">PDF exports include text and links. Images are omitted.</p>
@endif

            @if($form->format === 'review')<p class="compile-source-note">A fixed packet for your chosen Codex or Claude account. Choose a finished draft. World data comes from that draft’s captured context; it does not include later changes.</p>
@endif
            @if($recent->isNotEmpty())<section class="compile-recent"><h2>Recent exports</h2>@foreach($recent as $compile)<div class="compile-export-row"><div><strong>{{ $compile->manifest['source_label'] }}</strong><p>{{ count($compile->manifest['chapters']) }} {{ Str::plural('chapter', count($compile->manifest['chapters'])) }} · {{ $compile->created_at->asDateTime() }}@if($compile->manifest['summaries']) · With summaries
@endif
</p></div><flux:button size="sm" icon="arrow-down-tray" href="{{ $links[$compile->id] }}">{{ match($compile->format) { 'pdf' => 'PDF', 'review' => 'Review packet', default => 'Markdown' } }}</flux:button>
@if($compile->format === 'review')
@if($compile->assistant_token_hash && !$compile->assistant_revoked_at)
<flux:button size="sm" icon="lock-closed" wire:click="revokeAssistant({{ $compile->id }})">Revoke assistant access</flux:button>
@elseif(config('studio.local_mcp_enabled'))
<flux:button size="sm" icon="link" wire:click="enableAssistant({{ $compile->id }})">Enable assistant access</flux:button>
@endif
@endif
</div>
@endforeach
</section>
@endif

        </main>
    </form>
</div>
