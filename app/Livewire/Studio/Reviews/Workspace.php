<?php

namespace App\Livewire\Studio\Reviews;

use App\Livewire\Forms\Studio\ReviewCommentForm;
use App\Models\Manuscript;
use App\Models\ReviewComment;
use App\Models\ReviewGrant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.studio')]
class Workspace extends Component
{
    public ReviewGrant $grant;

    public ReviewCommentForm $form;

    public ?int $chapterId = null;

    public string $notice = '';

    #[Locked]
    public string $requestUuid;

    #[Locked]
    public int $workingRevision;

    public function mount(ReviewGrant $grant): void
    {
        $this->authorize('view', $grant);
        $this->grant = $grant;
        $this->chapterId = $grant->draft->chapters()->value('id');
        $this->requestUuid = (string) Str::uuid();
        $this->workingRevision = $grant->manuscript->revision;
    }

    public function selectChapter(int $id): void
    {
        $this->authorize('view', $this->grant);
        $this->grant->draft->chapters()->findOrFail($id);
        $this->chapterId = $id;
        $this->form->reset();
        $this->requestUuid = (string) Str::uuid();
    }

    public function beginSuggestion(): void
    {
        $this->authorize('suggest', $this->grant);
        $chapter = $this->grant->draft->chapters()->findOrFail($this->chapterId);
        $this->form->suggesting = true;
        $this->form->suggested_md = $chapter->content_md;
    }

    public function saveComment(): void
    {
        $this->authorize($this->form->suggesting ? 'suggest' : 'comment', $this->grant);
        $fields = $this->form->validate();
        DB::transaction(function () use ($fields): void {
            $grant = ReviewGrant::whereKey($this->grant->id)->lockForUpdate()->firstOrFail();
            $this->authorize($this->form->suggesting ? 'suggest' : 'comment', $grant);
            $grant->draft->chapters()->findOrFail($this->chapterId);
            $saved = ReviewComment::firstOrCreate(['request_uuid' => $this->requestUuid], ['grant_id' => $grant->id, 'chapter_id' => $this->chapterId, 'created_by' => auth()->id(), 'body_md' => $fields['body_md'], 'suggested_md' => $fields['suggesting'] ? $fields['suggested_md'] : null]);
            if ((int) $saved->grant_id !== $grant->id || (int) $saved->chapter_id !== $this->chapterId || (int) $saved->created_by !== auth()->id() || $saved->body_md !== $fields['body_md'] || $saved->suggested_md !== ($fields['suggesting'] ? $fields['suggested_md'] : null)) {
                throw ValidationException::withMessages(['form.body_md' => 'The previous note was already saved. Copy these changes before reopening the review.']);
            }
        });
        $this->form->reset();
        $this->requestUuid = (string) Str::uuid();
        $this->notice = 'Review saved';
    }

    public function accept(int $id): void
    {
        $this->authorize('manage', $this->grant);
        $comment = ReviewComment::where('grant_id', $this->grant->id)->findOrFail($id);
        $comment->accept($this->grant, auth()->user(), $this->workingRevision);
        $this->workingRevision = Manuscript::findOrFail($this->grant->manuscript_id)->revision;
        $this->notice = 'Suggestion applied to current writing. The reviewed draft remains locked.';
    }

    public function dismiss(int $id): void
    {
        $this->authorize('manage', $this->grant);
        ReviewComment::where('grant_id', $this->grant->id)->findOrFail($id)->dismiss();
        $this->notice = 'Suggestion dismissed';
    }

    public function render()
    {
        $this->grant->refresh();
        $this->authorize('view', $this->grant);

        return view('livewire.studio.reviews.workspace', ['chapters' => $this->grant->draft->chapters, 'chapter' => $this->chapterId ? $this->grant->draft->chapters()->findOrFail($this->chapterId) : null,
            'comments' => ReviewComment::where('grant_id', $this->grant->id)->where('chapter_id', $this->chapterId)->get()]);
    }
}
