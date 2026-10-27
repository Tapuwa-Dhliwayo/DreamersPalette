<?php

namespace App\Models\Concerns;

use Closure;
use Illuminate\Validation\ValidationException;

trait RevisesWorldRecord
{
    public function revise(array $fields, int $expectedRevision, ?Closure $afterSave = null): void
    {
        $this->getConnection()->transaction(function () use ($fields, $expectedRevision, $afterSave): void {
            $record = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($record->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['conflict' => 'This record changed in another window. Copy your writing before reopening the latest version.']);
            }
            $record->fill($fields);
            $record->revision++;
            $record->save();
            $afterSave?->__invoke($record);
        });
    }
}
