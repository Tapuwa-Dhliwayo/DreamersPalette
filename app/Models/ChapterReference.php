<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ChapterReference extends Model
{
    use ArchivesStudioRecords;

    protected $table = 'studio_chapter_references';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['source_revision' => 'integer', 'chapter_id' => 'integer', 'target_id' => 'integer'];
    }

    public function isResolved(string $content): bool
    {
        $anchor = $this->prefix.$this->wording.$this->suffix;

        return $anchor !== '' && substr_count($content, $anchor) === 1;
    }

    public static function attach(Manuscript $manuscript, array $fields, User $author): self
    {
        return $manuscript->getConnection()->transaction(function () use ($manuscript, $fields, $author): self {
            $manuscript = Manuscript::whereKey($manuscript->id)->lockForUpdate()->firstOrFail();
            $manuscript->requireActiveDraft($fields['draft_number']);
            $chapter = $manuscript->chapters()->whereKey($fields['chapter_id'])->lockForUpdate()->firstOrFail();
            if ($chapter->revision !== $fields['source_revision'] || substr_count($chapter->content_md, $fields['prefix'].$fields['wording'].$fields['suffix']) !== 1) {
                throw ValidationException::withMessages(['referenceForm.wording' => 'The passage changed or is ambiguous. Close this window and select it again.']);
            }
            $target = $manuscript->world->referenceTarget($fields['target_kind'], $fields['target_id']);
            $reference = static::withTrashed()->firstOrCreate([
                'chapter_id' => $chapter->id, 'target_kind' => $fields['target_kind'], 'target_id' => $target->id,
                'anchor_hash' => hash('sha256', $fields['prefix']."\0".$fields['wording']."\0".$fields['suffix']),
            ], [
                'manuscript_id' => $manuscript->id, 'world_id' => $manuscript->world_id,
                'target_name' => $target->name, 'wording' => $fields['wording'], 'prefix' => $fields['prefix'], 'suffix' => $fields['suffix'],
                'source_revision' => $chapter->revision, 'created_by' => $author->id,
            ]);
            $restored = $reference->trashed();
            if ($restored) {
                $reference->setArchived(false, $author->id);
            }
            if ($reference->wasRecentlyCreated || $restored) {
                $manuscript->increment('revision');
            }

            return $reference;
        });
    }
}
