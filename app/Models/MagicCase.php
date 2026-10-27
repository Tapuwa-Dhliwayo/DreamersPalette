<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MagicCase extends Model
{
    use ArchivesStudioRecords;

    protected $table = 'studio_magic_cases';

    protected $fillable = ['title', 'situation_md', 'outcome_md', 'reasoning_md'];

    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(MagicRule::class, 'studio_magic_case_rules', 'case_id', 'rule_id')->withTrashed();
    }
}
