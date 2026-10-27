<div>
    <header class="studio-header"><a class="studio-brand" href="{{ route('studio.index') }}">Dreamer’s Palette</a><a href="{{ route('studio.manuscript', $manuscript) }}">Back to writing</a></header>
    <main id="studio-main" class="manuscript-library">
        <h1>Restore {{ $draft->name }}</h1>
        <form wire:submit="restore" class="studio-form manuscript-details-form">
            <p>Your current working draft will first be saved as a locked recovery copy. Draft {{ $form->draft_number + 1 }} will then start from {{ $draft->name }}.</p>
            <p>This restores the manuscript title, chapter order, writing and summaries. World records stay as they are.</p>
            <dl class="restore-summary"><dt>Title</dt><dd>{{ $draft->manuscript_title }}</dd><dt>Chapters</dt><dd>{{ $draft->chapters->count() }}</dd><dt>Finished</dt><dd>{{ $draft->created_at->asDateTime() }}</dd></dl>
            @error('finishForm.name')<p class="studio-alert" role="alert">{{ $message }}</p>
            @enderror
            <div class="studio-form-actions"><flux:button href="{{ route('studio.manuscript', $manuscript) }}">Keep current writing</flux:button><flux:button type="submit" variant="primary" icon="arrow-path" wire:loading.attr="disabled">Save recovery copy & restore</flux:button></div>
        </form>
    </main>
</div>
