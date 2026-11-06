<div>
    <x-studio.header active="write">
        <x-slot:context><a href="{{ route('studio.manuscript', $manuscript) }}">← {{ $manuscript->title }}</a></x-slot:context>
    </x-studio.header>
    <main id="studio-main" class="manuscript-library release-page">
        <div class="library-heading">
            <h1 class="studio-page-title">Publishing</h1>
            @if($book && $book->is_published)<a class="release-reader-link" href="{{ url('/books/'.$book->slug) }}">Open reader book ↗</a>@endif
        </div>
        @if($notice)<p class="studio-notice" role="status">{{ $notice }}</p>@endif
        <form wire:submit="publish" class="studio-form release-form">
            <section class="release-source">
                <h2>Choose a finished draft</h2>
                <flux:select label="Finished draft" wire:model.live="release.draft_id">
                    <option value="">Choose a locked draft</option>
                    @foreach($drafts as $draft)<option value="{{ $draft->id }}">{{ $draft->name }}</option>@endforeach
                </flux:select>
                @if($drafts->isEmpty())
                    <x-studio.empty-state icon="document-text" title="No finished drafts">Finish a draft in the writing workspace before publishing.<x-slot:action><flux:button href="{{ route('studio.manuscript', $manuscript) }}" icon="pencil-square">Back to writing</flux:button></x-slot:action></x-studio.empty-state>
                @endif
                @if($source)
                    <div class="release-preview">
                        <h3>{{ $source->manuscript_title }}</h3>
                        <p>{{ $source->chapters->count() }} chapters will be public. Private summaries and worldbuilding are excluded.</p>
                        <ol class="release-chapters">
                            @foreach($source->chapters as $chapter)
                                <li><details><summary>{{ $chapter->title }}</summary><div class="release-prose"><x-studio.markdown :text="$chapter->content_md" /></div></details></li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </section>
            <section class="release-confirm">
                <h2>Review and publish</h2>
                <flux:textarea label="Public synopsis" wire:model="release.public_synopsis" rows="5" />
                @if($book)
                    <p class="release-destination">Reader address: <span>{{ url('/books/'.$book->slug) }}</span>. Existing chapter addresses are retained.</p>
                @else
                    <p>A new reader book will be created when you publish.</p>
                @endif
                <flux:checkbox label="I have reviewed this draft and want these chapters public." wire:model="release.confirmed" />
                <div class="studio-form-actions"><flux:button icon="arrow-up-tray" variant="primary" type="submit" :disabled="$drafts->isEmpty()" wire:loading.attr="disabled" wire:target="publish"><span wire:loading.remove wire:target="publish">Publish finished draft</span><span wire:loading wire:target="publish">Publishing…</span></flux:button></div>
            </section>
        </form>
        <section class="compile-recent release-history">
            <h2>Retained releases</h2>
            @forelse($releases as $published)
                <details><summary>Release {{ $published->number }} · {{ $published->created_at->asDateTime() }}{{ $published->draft_id ? '' : ' · Before studio adoption' }}</summary>
                    <h3>{{ $published->snapshot['book']['title'] }}</h3>
                    @foreach($published->snapshot['chapters'] as $chapter)
                        <details><summary>{{ $chapter['title'] }}{{ $chapter['is_published'] && !$chapter['deleted_at'] ? '' : ' · Not public' }}</summary><div class="release-prose"><x-studio.markdown :text="$chapter['content_md']" /></div></details>
                    @endforeach
                </details>
            @empty<p>No releases yet.</p>@endforelse
        </section>
    </main>
</div>
