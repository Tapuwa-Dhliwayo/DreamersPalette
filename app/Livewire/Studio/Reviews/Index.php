<?php

namespace App\Livewire\Studio\Reviews;

use App\Models\ReviewGrant;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.studio')]
class Index extends Component
{
    public function render()
    {
        abort_unless(auth()->user()->can('reviews.view'), 403);

        return view('livewire.studio.reviews.index', ['grants' => ReviewGrant::where('reviewer_id', auth()->id())->whereNull('revoked_at')->where('expires_at', '>', now())->whereHas('manuscript', fn ($query) => $query->whereNull('deleted_at'))->whereHas('draft', fn ($query) => $query->whereNull('undone_at'))->with(['manuscript', 'draft'])->get()]);
    }
}
