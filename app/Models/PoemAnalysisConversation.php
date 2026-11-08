<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoemAnalysisConversation extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'connection_id'];

    protected function casts(): array
    {
        return ['author_id' => 'integer', 'poem_id' => 'integer', 'connection_id' => 'integer'];
    }

    public function poem(): BelongsTo
    {
        return $this->belongsTo(Poem::class)->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'poem_conversation_id')->orderBy('id');
    }

    public function turns(): HasMany
    {
        return $this->hasMany(AssistantTurn::class, 'poem_conversation_id')->orderBy('id');
    }

    public function archive(): void
    {
        $this->turns()->active()->get()->each->stop();
        $this->delete();
    }
}
