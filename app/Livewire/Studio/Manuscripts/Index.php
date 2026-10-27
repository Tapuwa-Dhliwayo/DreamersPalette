<?php

namespace App\Livewire\Studio\Manuscripts;

use App\Livewire\Forms\Studio\AdoptBookForm;
use App\Livewire\Forms\Studio\ManuscriptForm;
use App\Models\Book;
use App\Models\Manuscript;
use App\Services\ManuscriptPublishing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.studio')]
class Index extends Component
{
    public ManuscriptForm $form;

    public AdoptBookForm $adoption;

    public bool $adopting = false;

    public function adoptBook(ManuscriptPublishing $publishing): void
    {
        $fields = $this->adoption->validate();
        $book = Book::where('author_id', Auth::id())->findOrFail($fields['book_id']);
        $manuscript = $publishing->adopt($book, Auth::user());
        $this->redirectRoute('studio.manuscript', $manuscript);
    }

    public bool $creating = false;

    public bool $archived = false;

    public function createManuscript(): void
    {
        $this->authorize('create', Manuscript::class);
        $this->form->title = trim($this->form->title);
        $fields = $this->form->validate();
        $manuscript = DB::transaction(function () use ($fields): Manuscript {
            $manuscript = new Manuscript($fields);
            $manuscript->author_id = Auth::id();
            $manuscript->save();
            $manuscript->addChapter(Auth::user(), 1);

            return $manuscript;
        });
        $this->redirectRoute('studio.manuscript', $manuscript);
    }

    public function archiveRecord(int $id, bool $archive): void
    {
        $record = Manuscript::withTrashed()->where('author_id', Auth::id())->findOrFail($id);
        $this->authorize($archive ? 'archive' : 'restore', $record);
        $record->setArchived($archive, Auth::id());
    }

    public function render()
    {
        $this->authorize('viewAny', Manuscript::class);

        return view('livewire.studio.manuscripts.index', [
            'adoptableBooks' => $this->adopting ? Book::where('author_id', Auth::id())->whereDoesntHave('manuscript')->withCount(['chapters', 'chapters as archived_chapters_count' => fn ($chapters) => $chapters->onlyTrashed()])->get() : collect(),
            'manuscripts' => Manuscript::where('author_id', Auth::id())->when($this->archived, fn ($query) => $query->onlyTrashed())->withCount(['chapters', 'drafts'])->latest('updated_at')->get(),
        ]);
    }
}
