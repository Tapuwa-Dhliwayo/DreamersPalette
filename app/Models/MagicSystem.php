<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class MagicSystem extends Model
{
    use ArchivesStudioRecords;

    protected $table = 'studio_magic_systems';

    protected $fillable = ['name', 'notes_md', 'aliases'];

    protected function casts(): array
    {
        return ['world_id' => 'integer', 'revision' => 'integer', 'aliases' => 'array'];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(MagicRule::class, 'system_id')->orderBy('id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(MagicCase::class, 'system_id')->latest('updated_at');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(MagicVariation::class, 'system_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MagicVersion::class, 'system_id')->orderByDesc('revision');
    }

    public static function start(World $world, array $fields, User $author): self
    {
        return $world->getConnection()->transaction(function () use ($world, $fields, $author): self {
            $system = $world->systems()->create($fields)->refresh();
            $system->captureVersion($author);

            return $system;
        });
    }

    public function saveNotes(array $fields, int $expectedRevision, User $author): void
    {
        $this->reviseWorld($expectedRevision, $author, fn ($system) => $system->fill($fields)->save());
    }

    public function saveRule(?int $id, array $fields, int $expectedRevision, User $author): void
    {
        $this->reviseWorld($expectedRevision, $author, function ($system) use ($id, $fields): void {
            $rule = $id ? $system->rules()->whereKey($id)->firstOrFail() : $system->rules()->make();
            $rule->fill($fields);
            $rule->revision = $rule->exists ? $rule->revision + 1 : 1;
            $rule->save();
        });
    }

    public function saveCase(?int $id, array $fields, int $expectedRevision, User $author): void
    {
        $this->reviseWorld($expectedRevision, $author, function ($system) use ($id, $fields): void {
            $case = $id ? $system->cases()->whereKey($id)->firstOrFail() : $system->cases()->make();
            $case->fill(collect($fields)->except('rule_ids')->all())->save();
            $case->rules()->sync($fields['rule_ids']);
        });
    }

    protected function afterArchiveChange(int $authorId): void
    {
        $this->captureVersion(User::findOrFail($authorId));
    }

    public function archivePart(string $kind, int $id, bool $archive, int $revision, User $author): void
    {
        $this->reviseWorld($revision, $author, function ($system) use ($kind, $id, $archive, $author): void {
            $parts = match ($kind) {
                'rule' => $system->rules(), 'case' => $system->cases(), default => abort(404)
            };
            $parts->withTrashed()->whereKey($id)->firstOrFail()->setArchived($archive, $author->id);
        });
    }

    private function reviseWorld(int $expectedRevision, User $author, Closure $write): void
    {
        $this->getConnection()->transaction(function () use ($expectedRevision, $author, $write): void {
            $system = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $system->requireRevision($expectedRevision);
            $write($system);
            $system->increment('revision');
            $system->captureVersion($author);
        });
    }

    public function saveVariation(Manuscript $manuscript, int $ruleId, array $fields): void
    {
        $this->getConnection()->transaction(function () use ($manuscript, $ruleId, $fields): void {
            $manuscript = Manuscript::whereKey($manuscript->id)->lockForUpdate()->firstOrFail();
            $manuscript->requireActiveDraft($fields['draft_number']);
            if ($manuscript->revision !== $fields['manuscript_revision']) {
                throw ValidationException::withMessages(['variationForm.reason_md' => 'The manuscript changed. Close and reopen this adjustment before saving.']);
            }
            $system = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            abort_unless($manuscript->world_id === $system->world_id, 422);
            $system->requireRevision($fields['system_revision']);
            $rule = $system->rules()->whereKey($ruleId)->firstOrFail();
            $variation = $system->variations()->where('manuscript_id', $manuscript->id)->where('rule_id', $ruleId)->first();
            if (($variation?->revision ?? 0) !== $fields['revision']) {
                throw ValidationException::withMessages(['variationForm.reason_md' => 'This manuscript adjustment has newer writing. Close and reopen it before saving.']);
            }
            if ($fields['mode_id'] === 0) {
                $variation?->delete();
                $manuscript->increment('revision');

                return;
            }
            if (! $variation) {
                $variation = $system->variations()->make();
                $variation->manuscript_id = $manuscript->id;
                $variation->rule_id = $ruleId;
                $variation->source_revision = $rule->revision;
                $variation->source_rule = $rule->referenceSnapshot();
                $variation->revision = 0;
            }
            $variation->fill(collect($fields)->only(['mode_id', 'text_md', 'reason_md'])->all());
            $variation->revision++;
            $variation->save();
            $manuscript->increment('revision');
        });
    }

    public function reviewSource(Manuscript $manuscript, int $ruleId, int $variationRevision, int $ruleRevision): void
    {
        $this->getConnection()->transaction(function () use ($manuscript, $ruleId, $variationRevision, $ruleRevision): void {
            $manuscript = Manuscript::whereKey($manuscript->id)->lockForUpdate()->firstOrFail();
            $system = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            abort_unless($manuscript->world_id === $system->world_id, 422);
            $rule = $system->rules()->whereKey($ruleId)->firstOrFail();
            $variation = $system->variations()->where('manuscript_id', $manuscript->id)->where('rule_id', $ruleId)->firstOrFail();
            if ($variation->revision !== $variationRevision || $rule->revision !== $ruleRevision) {
                throw ValidationException::withMessages(['source' => 'The rule or adjustment changed. Reopen the comparison before confirming.']);
            }
            $variation->forceFill(['source_rule' => $rule->referenceSnapshot(), 'source_revision' => $rule->revision, 'revision' => $variation->revision + 1])->save();
            $manuscript->increment('revision');
        });
    }

    private function requireRevision(int $expectedRevision): void
    {
        if ($this->revision !== $expectedRevision) {
            throw ValidationException::withMessages(['conflict' => 'This system has newer changes. Your form is retained; copy your writing, then reopen it against the latest version.']);
        }
    }

    private function captureVersion(User $author): void
    {
        $this->versions()->create([
            'revision' => $this->revision, 'created_by' => $author->id, 'created_at' => now(),
            'snapshot' => ['archived' => $this->trashed(), 'name' => $this->name, 'notes_md' => $this->notes_md, 'aliases' => $this->aliases,
                'rules' => $this->rules()->with('decision')->get()->toArray(),
                'cases' => $this->cases()->with('rules:id,title')->get()->toArray()],
        ]);
    }
}
