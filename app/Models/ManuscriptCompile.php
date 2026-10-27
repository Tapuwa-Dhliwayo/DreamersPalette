<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManuscriptCompile extends Model
{
    protected $table = 'studio_compiles';

    protected $guarded = ['id'];

    protected $hidden = ['assistant_token_hash'];

    protected function casts(): array
    {
        return ['manuscript_id' => 'integer', 'created_by' => 'integer', 'manifest' => 'array', 'expires_at' => 'immutable_datetime', 'assistant_revoked_at' => 'immutable_datetime'];
    }

    public static function capture(Manuscript $manuscript, array $options, int $authorId, string $requestUuid): self
    {
        return DB::transaction(function () use ($manuscript, $options, $authorId, $requestUuid): self {
            $sourceManuscript = Manuscript::whereKey($manuscript->id)->lockForUpdate()->firstOrFail();
            $existing = static::where('request_uuid', $requestUuid)->first();
            if ($existing) {
                abort_unless($existing->manuscript_id === $manuscript->id && $existing->created_by === $authorId, 403);

                if ($existing->expires_at->isPast()) {
                    throw ValidationException::withMessages(['source' => 'This export request expired. Refresh the source before compiling again.']);
                }

                return $existing;
            }
            if (! $options['prose'] && ! $options['summaries'] && ! ($options['format'] === 'review' && ($options['world'] ?? false))) {
                throw ValidationException::withMessages(['form.prose' => 'Include the writing, chapter summaries, or both.']);
            }
            $draft = $options['source'] === 'working' ? null : $sourceManuscript->drafts()->findOrFail((int) substr($options['source'], 6));
            if (! $draft && $sourceManuscript->revision !== (int) $options['revision']) {
                throw ValidationException::withMessages(['source' => 'The saved manuscript changed. Refresh the source before compiling.']);
            }
            if ($options['format'] === 'review' && ! $draft) {
                throw ValidationException::withMessages(['form.source' => 'Choose a finished draft for a fixed review packet.']);
            }
            $worldContext = null;
            if ($options['format'] === 'review' && ($options['world'] ?? false)) {
                Gate::authorize('viewAny', World::class);
                $worldContext = $draft->world_context;
                if ($worldContext) {
                    foreach ($worldContext['systems'] ?? [] as $index => $system) {
                        $version = MagicVersion::whereKey($system['version_id'])->where('system_id', $system['system_id'])->first();
                        if (! $version) {
                            throw ValidationException::withMessages(['form.world' => 'A captured magic system version is unavailable. Leave the world out until it is repaired.']);
                        }
                        $worldContext['systems'][$index]['snapshot'] = $version->snapshot;
                    }
                }
                if (! $worldContext) {
                    throw ValidationException::withMessages(['form.world' => 'This draft has no captured world. Finish a new draft after linking a world, or leave the world out.']);
                }
            }
            $chapters = ($draft ? $draft->chapters() : $sourceManuscript->chapters())->get();
            $selectedIds = array_map('intval', $options['chapters']);
            $selected = $chapters->whereIn('id', $selectedIds)->values();
            if ($selected->count() !== count($selectedIds)) {
                throw ValidationException::withMessages(['form.chapters' => 'A selected chapter no longer belongs to this source. Refresh the source.']);
            }
            $title = $draft?->manuscript_title ?? $sourceManuscript->title;
            $sourceLabel = $draft?->name ?? 'Working draft '.$sourceManuscript->working_draft_number;
            $manifest = ['title' => $title, 'author' => $sourceManuscript->author->name, 'source' => $options['source'], 'source_label' => $sourceLabel,
                'world_context' => $worldContext,
                'source_revision' => $draft?->source_revision ?? $sourceManuscript->revision,
                'captured_at' => now()->toIso8601String(), 'prose' => $options['prose'], 'summaries' => $options['summaries'],
                'chapters' => $selected->map(fn ($chapter) => ['id' => $chapter->id, 'source_id' => $chapter->source_chapter_id ?? $chapter->id, 'title' => $chapter->title, 'position' => $chapter->position,
                    'revision' => $chapter->revision ?? $chapter->source_revision,
                    'content_md' => $options['prose'] ? $chapter->content_md : '', 'summary_md' => $options['summaries'] ? ($chapter->summary_md ?? '') : ''])->all()];
            if ($worldContext) {
                $workingIds = array_column($manifest['chapters'], 'source_id');
                $manifest['world_context']['references'] = $options['prose'] ? array_values(array_filter($worldContext['references'] ?? [], fn ($reference) => in_array((int) $reference['chapter_id'], $workingIds))) : [];
            }
            $bytes = strlen(json_encode($manifest, JSON_THROW_ON_ERROR));
            if ($bytes > ($options['format'] === 'pdf' ? 750000 : 12000000)) {
                throw ValidationException::withMessages(['form.chapters' => $options['format'] === 'pdf' ? 'This selection is too large for one PDF. Compile fewer chapters, or download Markdown.' : ($options['format'] === 'review' ? 'This packet is too large. Select fewer chapters or leave the captured world out.' : 'This selection is too large. Compile it in smaller chapter groups.')]);
            }

            $sourceManuscript->forceFill(['compile_preferences' => ['format' => $options['format'], 'prose' => $options['prose'], 'summaries' => $options['summaries'], 'world' => $options['world'] ?? false]])->save();

            return static::create(['manuscript_id' => $manuscript->id, 'created_by' => $authorId, 'request_uuid' => $requestUuid,
                'format' => $options['format'], 'manifest' => $manifest, 'filename' => (Str::slug($title) ?: 'manuscript').'-'.Str::slug($sourceLabel).'.'.match ($options['format']) {
                    'pdf' => 'pdf', 'review' => 'zip', default => 'md'
                },
                'expires_at' => now()->addDay()]);
        });
    }

    public function enableAssistantAccess(): string
    {
        abort_unless($this->format === 'review' && $this->status === 'ready' && $this->expires_at->isFuture(), 422);
        $token = bin2hex(random_bytes(32));
        $this->update(['assistant_token_hash' => hash('sha256', $token), 'assistant_revoked_at' => null]);

        return $token;
    }

    public function revokeAssistantAccess(): void
    {
        $this->update(['assistant_token_hash' => null, 'assistant_revoked_at' => now()]);
    }

    public function markdown(): string
    {
        $manifest = $this->manifest;
        $writing = '# '.$manifest['title']."\n\n";
        foreach ($manifest['chapters'] as $chapter) {
            $writing .= '## '.$chapter['title']."\n\n";
            if ($manifest['prose']) {
                $writing .= $chapter['content_md']."\n\n";
            }
            if ($manifest['summaries']) {
                $writing .= "### Chapter summary\n\n".($chapter['summary_md'] ?: '_No summary written._')."\n\n";
            }
        }

        return $writing;
    }
}
