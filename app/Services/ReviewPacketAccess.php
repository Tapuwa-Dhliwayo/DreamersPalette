<?php

namespace App\Services;

use App\Models\Manuscript;
use App\Models\ManuscriptCompile;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class ReviewPacketAccess
{
    public function authorisedCompile(): ManuscriptCompile
    {
        abort_unless(config('studio.local_mcp_enabled'), 403, 'Local review access is disabled.');
        $packetId = getenv('DREAMERS_REVIEW_ID');
        $token = getenv('DREAMERS_REVIEW_TOKEN');
        abort_unless(is_string($packetId) && ctype_digit($packetId) && is_string($token) && strlen($token) === 64, 403, 'Review access is unavailable.');
        $compile = ManuscriptCompile::find($packetId);
        abort_unless($compile && $compile->format === 'review' && $compile->status === 'ready' && $compile->expires_at->isFuture() && ! $compile->assistant_revoked_at && $compile->assistant_token_hash && hash_equals($compile->assistant_token_hash, hash('sha256', $token)), 403, 'Review access expired or was revoked.');
        // STDIO stays alive across calls; reload Spatie’s in-process permission map.
        app(PermissionRegistrar::class)->clearPermissionsCollection();
        $author = User::find($compile->created_by);
        $manuscript = Manuscript::find($compile->manuscript_id);
        abort_unless($author && $author->hasVerifiedEmail() && $manuscript && Gate::forUser($author)->allows('export', $manuscript), 403, 'Review access is unavailable.');
        if ($compile->manifest['world_context']) {
            abort_unless(Gate::forUser($author)->allows('viewAny', World::class), 403, 'World review access is unavailable.');
        }

        return $compile;
    }

    public function read(ManuscriptCompile $compile, string $section, ?int $id, int $offset, int $limit): array
    {
        $manifest = $compile->manifest;
        if ($section === 'manifest') {
            $groups = $this->groups($manifest);
            $catalogue = collect($manifest['chapters'])->map(fn ($chapter) => ['section' => 'chapter', 'id' => $chapter['id'], 'name' => $chapter['title']]);
            foreach ($groups as $group => $records) {
                foreach ($records as $record) {
                    $catalogue->push(['section' => $group, 'id' => $record['id'] ?? $record['system_id'], 'name' => $record['name'] ?? $record['title'] ?? $record['label'] ?? $record['wording'] ?? 'Reference']);
                }
            }

            return ['title' => $manifest['title'], 'source' => $manifest['source_label'], 'captured_at' => $manifest['captured_at'], 'expires_at' => $compile->expires_at->toIso8601String(),
                'coverage' => ['prose' => $manifest['prose'], 'summaries' => $manifest['summaries'], 'world' => $manifest['world_context'] !== null],
                'chapter_count' => count($manifest['chapters']), 'world_counts' => array_map('count', $groups),
                'offset_unit' => 'catalogue records', 'offset' => $offset, 'next_offset' => $offset + 60 < $catalogue->count() ? $offset + 60 : null, 'records' => $catalogue->slice($offset, 60)->values()->all()];
        }
        $record = $section === 'chapter' ? collect($manifest['chapters'])->firstWhere('id', $id) : collect($this->groups($manifest)[$section] ?? [])->first(fn ($record) => (int) ($record['id'] ?? $record['system_id']) === $id);
        if (! $record) {
            throw ValidationException::withMessages(['id' => 'That record is not included in this packet. Read the manifest for available IDs.']);
        }
        // Local storage paths are never part of assistant-visible metadata.
        unset($record['image_path']);
        $text = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $length = mb_strlen($text);

        return ['section' => $section, 'id' => $id, 'offset' => $offset, 'total_characters' => $length, 'next_offset' => $offset + $limit < $length ? $offset + $limit : null, 'text' => mb_substr($text, $offset, $limit)];
    }

    public function mapImage(ManuscriptCompile $compile, int $id): array
    {
        $map = collect($compile->manifest['world_context']['development']['maps'] ?? [])->firstWhere('id', $id);
        $path = $map['image_path'] ?? null;
        $disk = Storage::disk('local');
        if (! $path || ! $disk->exists($path) || $disk->size($path) > 12582912) {
            throw ValidationException::withMessages(['id' => 'This captured map has no available image within the 12 MiB image limit. Its coordinates remain readable.']);
        }
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true), 422);

        return [$disk->get($path), $mime];
    }

    private function groups(array $manifest): array
    {
        return ['references' => $manifest['world_context']['references'] ?? [], 'systems' => $manifest['world_context']['systems'] ?? [], ...($manifest['world_context']['development'] ?? [])];
    }
}
