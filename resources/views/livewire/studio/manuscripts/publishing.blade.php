<div>
    <header class="studio-header"><a class="studio-brand" href="{{ route('studio.index') }}">Dreamer’s Palette</a><a href="{{ route('studio.manuscript', $manuscript) }}">Back to writing</a></header>
    <main id="studio-main" class="world-document development-document"><h1>Publishing</h1><p role="status">{{ $notice }}</p>
        <form wire:submit="publish" class="studio-form manuscript-details-form">
            <flux:select label="Finished draft" wire:model.live="release.draft_id"><option value="">Choose a locked draft</option>@foreach($drafts as $draft)<option value="{{ $draft->id }}">{{ $draft->name }}</option>
            @endforeach</flux:select>
            @if($source)<section><h2>{{ $source->manuscript_title }}</h2><p>{{ $source->chapters->count() }} chapters will be public. Private summaries and worldbuilding are excluded.</p><ol>@foreach($source->chapters as $chapter)<li><details><summary>{{ $chapter->title }}</summary><x-studio.markdown :text="$chapter->content_md" /></details></li>
            @endforeach</ol></section>
            @endif
            @if($book)<p>Reader address: /books/{{ $book->slug }}. Existing chapter addresses are retained.</p>
            @else<p>A new reader book will be created when you publish.</p>
            @endif
            <flux:textarea label="Public synopsis" wire:model="release.public_synopsis" rows="5" />
            <flux:checkbox label="I have reviewed this draft and want these chapters public." wire:model="release.confirmed" />
            <div class="studio-form-actions"><flux:button icon="arrow-up-tray" variant="primary" type="submit" wire:loading.attr="disabled">Publish finished draft</flux:button></div>
        </form>
        <section class="compile-recent"><h2>Retained releases</h2>@forelse($releases as $published)<details><summary>Release {{ $published->number }} · {{ $published->created_at->asDateTime() }}{{ $published->draft_id ? '' : ' · Before studio adoption' }}</summary><h3>{{ $published->snapshot['book']['title'] }}</h3>@foreach($published->snapshot['chapters'] as $chapter)<details><summary>{{ $chapter['title'] }}{{ $chapter['is_published'] && !$chapter['deleted_at'] ? '' : ' · Not public' }}</summary><x-studio.markdown :text="$chapter['content_md']" /></details>
        @endforeach</details>
        @empty<p>No releases yet.</p>
        @endforelse</section>
    </main>
</div>
