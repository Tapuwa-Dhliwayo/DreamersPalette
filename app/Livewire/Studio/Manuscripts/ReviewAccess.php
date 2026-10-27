<?php

namespace App\Livewire\Studio\Manuscripts;

use App\Livewire\Forms\Studio\ReviewGrantForm;
use App\Models\Manuscript;
use App\Models\ReviewGrant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.studio')]
class ReviewAccess extends Component
{
    public Manuscript $manuscript;

    public ReviewGrantForm $form;

    public string $notice = '';

    public function mount(Manuscript $manuscript): void
    {
        $this->authorize('update', $manuscript);
        $this->manuscript = $manuscript;
    }

    public function grant(): void
    {
        $this->authorize('update', $this->manuscript);
        $fields = $this->form->validate();
        $reviewer = User::where('email', trim($fields['email']))->whereNotNull('email_verified_at')->first();
        if (! $reviewer || ! $reviewer->can('reviews.view') || $reviewer->id === auth()->id()) {
            throw ValidationException::withMessages(['form.email' => 'Choose another verified account enabled for editorial review.']);
        }
        DB::transaction(function () use ($fields, $reviewer): void {
            $manuscript = Manuscript::whereKey($this->manuscript->id)->lockForUpdate()->firstOrFail();
            $draft = $manuscript->drafts()->findOrFail($fields['draft_id']);
            $active = ReviewGrant::where('manuscript_id', $manuscript->id)->where('draft_id', $draft->id)->where('reviewer_id', $reviewer->id)->whereNull('revoked_at')->where('expires_at', '>', now())->lockForUpdate()->first();
            $access = ['can_suggest' => $fields['can_suggest'], 'expires_at' => now()->addDays($fields['days'])];
            if ($active) {
                $active->update($access);

                return;
            }
            ReviewGrant::create([...$access, 'manuscript_id' => $manuscript->id, 'draft_id' => $draft->id, 'reviewer_id' => $reviewer->id, 'created_by' => auth()->id()]);
        });
        $this->form->reset();
        $this->notice = 'Access granted. No invitation message was sent.';
    }

    public function revoke(int $id): void
    {
        $grant = ReviewGrant::where('manuscript_id', $this->manuscript->id)->findOrFail($id);
        $this->authorize('manage', $grant);
        $grant->update(['revoked_at' => now()]);
        $this->notice = 'Access revoked';
    }

    public function render()
    {
        $this->authorize('update', $this->manuscript);

        return view('livewire.studio.manuscripts.review-access', ['drafts' => $this->manuscript->drafts()->get(), 'grants' => ReviewGrant::where('manuscript_id', $this->manuscript->id)->with(['reviewer', 'draft'])->latest('id')->get()]);
    }
}
