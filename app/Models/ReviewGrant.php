<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewGrant extends Model
{
    protected $table = 'studio_review_grants';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['can_suggest' => 'boolean', 'reviewer_id' => 'integer', 'created_by' => 'integer', 'expires_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class)->withTrashed();
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(ManuscriptDraft::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isActive(): bool
    {
        return ! $this->revoked_at && $this->expires_at->isFuture() && ! $this->manuscript->trashed() && ! $this->draft->undone_at;
    }
}
