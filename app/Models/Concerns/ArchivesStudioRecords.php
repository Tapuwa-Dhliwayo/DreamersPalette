<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

trait ArchivesStudioRecords
{
    use SoftDeletes;

    public function setArchived(bool $archived, int $authorId, ?int $expectedRevision = null): void
    {
        $this->getConnection()->transaction(function () use ($archived, $authorId, $expectedRevision): void {
            $record = static::withTrashed()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($expectedRevision !== null && (int) $record->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['conflict' => 'This record changed. Refresh before archiving or restoring it.']);
            }
            if ($record->trashed() === $archived) {
                return;
            }
            $record->archived_by = $archived ? $authorId : null;
            if ($record->getRawOriginal('revision') !== null) {
                $record->revision++;
            }
            $record->deleted_at = $archived ? now() : null;
            $record->save();
            if (method_exists($record, 'afterArchiveChange')) {
                $record->afterArchiveChange($authorId);
            }
        });
    }
}
