<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagicRule extends Model
{
    use ArchivesStudioRecords;

    protected $table = 'studio_magic_rules';

    protected $fillable = ['title', 'statement_md', 'conditions_md', 'consequences_md', 'exceptions_md', 'reasoning_md', 'decision_id'];

    protected function casts(): array
    {
        return ['system_id' => 'integer', 'revision' => 'integer'];
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(MagicDecision::class, 'decision_id');
    }

    public function referenceSnapshot(): array
    {
        return [...$this->only(['id', 'title', 'statement_md', 'conditions_md', 'consequences_md', 'exceptions_md', 'reasoning_md', 'decision_id', 'revision']), 'decision_name' => $this->decision->name];
    }
}
