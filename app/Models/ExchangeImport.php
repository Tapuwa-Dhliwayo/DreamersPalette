<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ExchangeImport extends Model
{
    public const PREVIEW_LIFETIME_HOURS = 24;

    protected $table = 'studio_exchange_imports';

    protected $fillable = [
        'author_id', 'package_id', 'parent_package_id', 'destination_type', 'world_id', 'exercise_id', 'poem_id',
        'poem_conversation_id', 'snapshot_id', 'format_version', 'title', 'status', 'raw_path', 'raw_hash',
        'canonical_hash', 'body_md', 'payload', 'receipt', 'external_origin', 'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'author_id' => 'integer',
            'world_id' => 'integer',
            'exercise_id' => 'integer',
            'poem_id' => 'integer',
            'poem_conversation_id' => 'integer',
            'snapshot_id' => 'integer',
            'format_version' => 'integer',
            'payload' => 'array',
            'receipt' => 'array',
            'external_origin' => 'array',
            'saved_at' => 'datetime',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(WorldExercise::class, 'exercise_id');
    }

    public function poemConversation(): BelongsTo
    {
        return $this->belongsTo(PoemAnalysisConversation::class, 'poem_conversation_id');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(PoemAnalysisSnapshot::class, 'snapshot_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'import_id')->orderBy('id');
    }

    // Call as ExchangeImport::query()->saved(): a bare static saved() is Eloquent's model-event registration.
    public function scopeSaved(Builder $query): Builder
    {
        return $query->where('status', 'saved');
    }

    public function isSaved(): bool
    {
        return $this->status === 'saved';
    }

    public function markSaved(array $receipt): void
    {
        $this->update([
            'status' => 'saved',
            'saved_at' => now(),
            'receipt' => array_replace($this->receipt ?? [], $receipt),
        ]);
    }

    public function rawMarkdown(): string
    {
        return Storage::disk('local')->get($this->raw_path);
    }

    public function discard(): void
    {
        Storage::disk('local')->delete($this->raw_path);
        $this->delete();
    }

    /** Previews are not reservations; an unsaved upload is dropped with its private copy after a day. */
    public static function prunePreviews(): int
    {
        $removed = 0;
        self::where('status', 'previewed')
            ->where('created_at', '<', now()->subHours(self::PREVIEW_LIFETIME_HOURS))
            ->orderBy('id')
            ->each(function (self $import) use (&$removed): void {
                $import->discard();
                $removed++;
            });

        return $removed;
    }
}
