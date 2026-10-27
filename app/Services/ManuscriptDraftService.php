<?php

namespace App\Services;

use App\Models\Manuscript;
use App\Models\ManuscriptDraft;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManuscriptDraftService
{
    public function finish(Manuscript $manuscript, User $author, array $details, bool $allowEmpty = false): ManuscriptDraft
    {
        return $manuscript->getConnection()->transaction(function () use ($manuscript, $author, $details, $allowEmpty): ManuscriptDraft {
            $manuscript = Manuscript::whereKey($manuscript->id)->lockForUpdate()->firstOrFail();
            $existing = ManuscriptDraft::where('manuscript_id', $manuscript->id)->where('request_uuid', $details['request_uuid'])->first();
            if ($existing) {
                if ($existing->undone_at) {
                    throw ValidationException::withMessages(['finishForm.name' => 'That completion was undone. Close this confirmation and finish the current draft again.']);
                }

                return $existing;
            }
            $manuscript->requireActiveDraft($details['draft_number']);
            if ($manuscript->revision !== $details['revision']) {
                throw ValidationException::withMessages(['finishForm.name' => 'The manuscript changed after you opened this confirmation. Close it, save your writing, and try again.']);
            }
            $chapters = $manuscript->chapters()->get();
            if ($chapters->isEmpty() && ! $allowEmpty) {
                throw ValidationException::withMessages(['finishForm.name' => 'Add a chapter before finishing this draft.']);
            }
            $draft = $manuscript->drafts()->create([
                'restored_from_draft_id' => $details['restored_from_draft_id'] ?? null,
                'number' => $manuscript->working_draft_number, 'name' => $details['name'],
                'note' => $details['note'], 'manuscript_title' => $manuscript->title,
                'source_revision' => $manuscript->revision, 'created_by' => $author->id,
                'request_uuid' => $details['request_uuid'], 'created_at' => now(),
                'world_context' => $manuscript->world?->captureDraftContext($manuscript),
            ]);
            foreach ($chapters as $chapter) {
                $draft->chapters()->create([
                    'source_chapter_id' => $chapter->id, 'position' => $chapter->position,
                    'title' => $chapter->title, 'content_md' => $chapter->content_md,
                    'summary_md' => $chapter->summary_md, 'source_revision' => $chapter->revision,
                ]);
            }
            // Stable working chapter IDs keep references intact; the locked rows are independent copies.
            $manuscript->forceFill([
                'working_draft_number' => $manuscript->working_draft_number + 1,
                'based_on_draft_id' => $draft->id, 'revision' => $manuscript->revision + 1,
            ])->save();

            return $draft;
        });
    }

    public function restore(Manuscript $manuscript, User $author, int $draftId, array $expected, string $requestUuid): void
    {
        $manuscript->getConnection()->transaction(function () use ($manuscript, $author, $draftId, $expected, $requestUuid): void {
            $manuscript = Manuscript::whereKey($manuscript->id)->lockForUpdate()->firstOrFail();
            $source = $manuscript->drafts()->with('chapters')->findOrFail($draftId);
            $checkpoint = $manuscript->drafts()->where('request_uuid', $requestUuid)->first();
            if ($checkpoint) {
                abort_unless((int) $checkpoint->restored_from_draft_id === $source->id, 409);

                return;
            }
            $checkpoint = $this->finish($manuscript, $author, [
                ...$expected, 'request_uuid' => $requestUuid,
                'name' => 'Before restoring '.mb_substr($source->name, 0, 130),
                'note' => 'Recovery copy saved before restoring '.$source->name.'.',
                'restored_from_draft_id' => $source->id,
            ], true);
            $manuscript->refresh();
            // Invalidate every old writing tab, including identical-request retries.
            $manuscript->chapters()->withTrashed()->update(['revision' => DB::raw('revision + 1'), 'last_save_uuid' => null]);
            $retained = [];
            foreach ($source->chapters as $snapshot) {
                $chapter = $manuscript->chapters()->withTrashed()->find($snapshot->source_chapter_id);
                $chapter ??= $manuscript->chapters()->make();
                $chapter->forceFill(['title' => $snapshot->title, 'content_md' => $snapshot->content_md,
                    'summary_md' => $snapshot->summary_md, 'position' => $snapshot->position,
                    'updated_by' => $author->id, 'deleted_at' => null, 'archived_by' => null, 'last_save_uuid' => null])->save();
                $retained[] = $chapter->id;
            }
            $manuscript->chapters()->whereNotIn('id', $retained)->update(['deleted_at' => now(), 'archived_by' => $author->id]);
            $manuscript->forceFill(['title' => $source->manuscript_title, 'based_on_draft_id' => $source->id, 'revision' => $manuscript->revision + 1])->save();
        });
    }

    public function undoFinish(Manuscript $manuscript, User $author, int $draftId, int $expectedRevision): void
    {
        $manuscript->getConnection()->transaction(function () use ($manuscript, $author, $draftId, $expectedRevision): void {
            $manuscript = Manuscript::whereKey($manuscript->id)->lockForUpdate()->firstOrFail();
            $draft = ManuscriptDraft::where('manuscript_id', $manuscript->id)->whereKey($draftId)->firstOrFail();
            if ($draft->undone_at) {
                return;
            }
            if ($manuscript->revision !== $expectedRevision || ! $manuscript->canUndoFinish($draft)) {
                throw ValidationException::withMessages(['undo' => 'This finish cannot be undone because the next draft has changed. Your writing is unchanged.']);
            }
            $draft->recordUndo($author);
            // Reused draft numbers must not make an older browser save valid again.
            $manuscript->chapters()->increment('revision', 1, ['last_save_uuid' => null]);
            $previous = $manuscript->drafts()->where('number', '<', $draft->number)->first();
            $manuscript->forceFill([
                'working_draft_number' => $draft->number,
                'based_on_draft_id' => $previous?->id,
                'revision' => $manuscript->revision + 1,
            ])->save();
        });
    }
}
