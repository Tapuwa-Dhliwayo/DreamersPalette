<div>
    <header class="studio-header">
        <a href="{{ route('studio.index') }}" class="studio-brand">Dreamer’s Palette</a>
        <nav class="studio-header-links" aria-label="Application">@can('reviews.view')<a href="{{ route('studio.reviews') }}">Editorial reviews</a>
@endcan
@can('worlds.view')<a href="{{ route('studio.worlds') }}">Worlds</a>@endcan<a href="/dashboard/books">Publishing</a><a href="/dashboard">Poetry & dashboard</a><span>{{ auth()->user()->name }}</span></nav>
    </header>
    <main id="studio-main" class="manuscript-library">
        <div class="library-heading"><div><h1>Manuscripts</h1></div>
@can('create', \App\Models\Manuscript::class)<flux:button icon="book-open" wire:click="$set('adopting', true)">Bring in existing novel</flux:button>
@endcan
            @can('create', \App\Models\Manuscript::class)<flux:button variant="primary" icon="plus" wire:click="$set('creating', true)">New manuscript</flux:button>@endcan
        </div>
        <p class="studio-notice"><flux:icon.lock-closed variant="micro" /> Your studio writing is private. Saving or finishing a draft does not change your published novels.</p>
        <flux:select class="archive-filter" label="Show" wire:model.live.boolean="archived"><flux:select.option value="false">Active</flux:select.option><flux:select.option value="true">Archived</flux:select.option></flux:select>
        <div class="manuscript-shelf">
            @forelse($manuscripts as $manuscript)
                <div class="studio-library-record"><a class="manuscript-book" @if(!$archived) href="{{ route('studio.manuscript', $manuscript) }}" @endif wire:key="manuscript-{{ $manuscript->id }}">
                    <div class="book-spine"><flux:icon.book-open /></div>
                    <div><span class="draft-label">Draft {{ $manuscript->working_draft_number }} · In progress</span><h2>{{ $manuscript->title }}</h2>
                        @if($manuscript->description)<p>{{ $manuscript->description }}</p>@endif
                        <div class="manuscript-meta">{{ $manuscript->chapters_count }} {{ Str::plural('chapter', $manuscript->chapters_count) }} <span>·</span> {{ $manuscript->drafts_count }} locked {{ Str::plural('draft', $manuscript->drafts_count) }} <span>·</span> {{ $manuscript->updated_at->asDate() }}</div>
                    </div><flux:icon.arrow-up-right class="book-open-arrow" />
                </a>@can($archived ? 'restore' : 'update', $manuscript)<flux:button size="sm" :icon="$archived ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $manuscript->id }}, {{ $archived ? 'false' : 'true' }})">{{ $archived ? 'Restore' : 'Archive' }}</flux:button>@endcan</div>
            @empty
                <div class="studio-empty"><flux:icon.book-open /><h2>No manuscripts</h2><p>Create a manuscript, add a chapter, and begin in Markdown. Summaries and planning can come later.</p></div>
            @endforelse
        </div>
    </main>
    <flux:modal wire:model="creating" class="md:w-[32rem]">
        <form wire:submit="createManuscript" class="studio-form">
            <div><flux:heading size="lg">A new manuscript</flux:heading></div>
            <flux:input label="Working title" wire:model="form.title" maxlength="255" placeholder="Give your story a name" autofocus />
            <flux:textarea label="Description" badge="Optional" wire:model="form.description" rows="3" />
            <div class="studio-form-actions"><flux:button wire:click="$set('creating', false)">Cancel</flux:button><flux:button type="submit" variant="primary" icon="arrow-right">Start writing</flux:button></div>
        </form>
    </flux:modal>
    <flux:modal wire:model="adopting" class="md:w-[32rem]">
        <form wire:submit="adoptBook" class="studio-form"><h2>Bring in an existing novel</h2><p>This copies writing into a private manuscript. Public pages and chapter addresses stay unchanged. Further chapter editing then belongs to the studio.</p>
            <flux:select label="Novel" wire:model="adoption.book_id"><option value="">Choose your novel</option>@foreach($adoptableBooks as $book)<option value="{{ $book->id }}">{{ $book->title }} · {{ $book->chapters_count }} active, {{ $book->archived_chapters_count }} archived chapters</option>
            @endforeach</flux:select><div class="studio-form-actions"><flux:button wire:click="$set('adopting', false)">Cancel</flux:button><flux:button type="submit" variant="primary" icon="arrow-right">Create private manuscript</flux:button></div>
        </form>
    </flux:modal>
</div>
