<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;

class SupabaseSnapshot
{
    public const COLUMNS = [
        'profiles' => ['id', 'display_name', 'bio', 'avatar_url', 'role', 'created_at'],
        'poetry_collections' => ['id', 'author_id', 'title', 'slug', 'description', 'theme_background_url', 'theme_overlay_opacity', 'accent_color', 'is_published', 'created_at', 'updated_at', 'theme_text_mode', 'deleted_at', 'theme_text_color', 'theme_heading_color', 'theme_muted_color'],
        'poems' => ['id', 'collection_id', 'author_id', 'title', 'slug', 'content_md', 'excerpt', 'is_published', 'created_at', 'updated_at', 'deleted_at', 'published_at'],
        'books' => ['id', 'author_id', 'title', 'slug', 'synopsis', 'cover_image_url', 'ai_prompt', 'is_published', 'created_at', 'updated_at', 'theme_background_url', 'theme_overlay_opacity', 'accent_color', 'theme_text_mode', 'deleted_at'],
        'chapters' => ['id', 'book_id', 'author_id', 'title', 'chapter_number', 'content_md', 'is_preview', 'is_published', 'created_at', 'updated_at', 'deleted_at'],
        'generated_assets' => ['id', 'author_id', 'type', 'prompt', 'image_url', 'created_at'],
    ];

    public readonly array $records;

    public readonly array $files;

    public readonly string $fingerprint;

    public readonly string $capturedAt;

    public function __construct(public readonly string $directory, string $sourceUrl)
    {
        $snapshot = $this->readJson('snapshot.json');
        $manifest = $this->readJson('files-manifest.json');
        $schema = $this->readJson('schema.json');
        $this->fingerprint = hash_file('sha256', $directory.'/snapshot.json');
        $this->capturedAt = $this->timestamp($snapshot['captured_at'] ?? null)
            ?? throw new InvalidArgumentException('The snapshot capture date is missing.');

        if (! filter_var($sourceUrl, FILTER_VALIDATE_URL) || parse_url($sourceUrl, PHP_URL_SCHEME) !== 'https') {
            throw new InvalidArgumentException('Configure the original HTTPS Supabase source URL.');
        }

        $tables = $snapshot['tables'] ?? [];
        if (! is_array($tables) || array_diff(array_keys(self::COLUMNS), array_keys($tables)) || array_diff(array_keys($tables), array_keys(self::COLUMNS))) {
            throw new InvalidArgumentException('The snapshot must contain exactly the six supported content tables.');
        }

        $this->files = $this->prepareFiles($snapshot['storage_objects'] ?? [], $manifest, $schema['buckets'] ?? []);
        $urlMap = [];
        foreach ($this->files as $file) {
            if ($file['disk'] === 'public') {
                $original = rtrim($sourceUrl, '/').'/storage/v1/object/public/'.$file['bucket'].'/'.$file['name'];
                $urlMap[$original] = '/storage/'.$file['destination'];
                $encoded = rtrim($sourceUrl, '/').'/storage/v1/object/public/'.$this->encodePath($file['bucket'].'/'.$file['name']);
                $urlMap[$encoded] = '/storage/'.$this->encodePath($file['destination']);
            }
        }

        $records = ['users' => $this->prepareUsers($snapshot['administrators'] ?? [])];
        foreach (self::COLUMNS as $table => $columns) {
            if (! is_array($tables[$table]) || ! array_is_list($tables[$table])) {
                throw new InvalidArgumentException("{$table} must be a list of records.");
            }
            $records[$table] = [];
            foreach ($tables[$table] as $row) {
                if (! is_array($row) || array_diff($columns, array_keys($row)) || array_diff(array_keys($row), $columns)) {
                    throw new InvalidArgumentException("Snapshot columns do not match {$table}; review the source schema before importing.");
                }
                foreach ($row as $column => &$value) {
                    if (str_ends_with($column, '_at')) {
                        $value = $this->timestamp($value);
                    }
                    if (str_ends_with($column, '_id') || $column === 'id') {
                        $this->requireUuid($value, $table);
                    }
                    if (in_array($column, ['is_published', 'is_preview'], true) && ! is_bool($value)) {
                        throw new InvalidArgumentException("{$table} has a missing or invalid boolean.");
                    }
                    if ($column === 'theme_overlay_opacity' && $value !== null) {
                        if (! is_numeric($value) || (float) $value < 0 || (float) $value > 1 || abs((float) $value - round((float) $value, 3)) > 0.0000001) {
                            throw new InvalidArgumentException("{$table} has an overlay opacity that cannot be preserved.");
                        }
                        $value = number_format((float) $value, 3, '.', '');
                    }
                    if (is_string($value) && (str_ends_with($column, '_url') || in_array($column, ['content_md', 'description', 'synopsis', 'bio'], true))) {
                        $value = strtr($value, $urlMap);
                        if (str_contains($value, rtrim($sourceUrl, '/').'/storage/v1/')) {
                            throw new InvalidArgumentException("{$table} references a source file without a supported local mapping.");
                        }
                    }
                }
                unset($value);
                $records[$table][] = $row;
            }
        }
        $this->validateContent($records);
        $this->validateRelationships($records);
        $this->records = $records;
    }

    private function readJson(string $filename): array
    {
        $path = $this->directory.'/'.$filename;
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("Missing readable source file: {$filename}.");
        }
        try {
            $decoded = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException("Invalid JSON in {$filename}.");
        }
        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Expected a JSON object or array in {$filename}.");
        }

        return $decoded;
    }

    private function prepareUsers(array $administrators): array
    {
        if (count($administrators) !== 1) {
            throw new InvalidArgumentException('This migration expects exactly one existing user.');
        }
        $administrator = $administrators[0];
        $this->requireUuid($administrator['id'] ?? null, 'users');
        if (! filter_var($administrator['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The existing user email is missing or invalid.');
        }
        $password = $administrator['encrypted_password'] ?? '';
        if (! preg_match('/^\$2[aby]\$\d{2}\$[.\/A-Za-z0-9]{53}$/', $password)) {
            throw new InvalidArgumentException('The existing user does not have a supported bcrypt password hash.');
        }

        return [[
            'id' => $administrator['id'],
            'email' => $administrator['email'],
            // PHP/Laravel identifies bcrypt by the 2y prefix; the underlying bcrypt hash is unchanged.
            'password' => '$2y$'.substr($password, 4),
            'email_verified_at' => $this->timestamp($administrator['email_confirmed_at'] ?? null),
            'created_at' => $this->timestamp($administrator['created_at'] ?? null),
            'updated_at' => $this->timestamp($administrator['updated_at'] ?? null),
        ]];
    }

    private function prepareFiles(array $objects, array $manifest, array $buckets): array
    {
        $files = [];
        $indexedManifest = [];
        foreach ($manifest as $entry) {
            $key = ($entry['bucket'] ?? '').'/'.($entry['name'] ?? '');
            if (isset($indexedManifest[$key])) {
                throw new InvalidArgumentException('The file manifest contains duplicate paths.');
            }
            $indexedManifest[$key] = $entry;
        }
        $publicBuckets = [];
        foreach ($buckets as $bucket) {
            $publicBuckets[$bucket['id']] = $bucket['public'];
        }
        $seen = [];
        foreach ($objects as $object) {
            $bucket = $object['bucket_id'] ?? '';
            $name = $object['name'] ?? '';
            $key = $bucket.'/'.$name;
            if (! $this->safePath($bucket) || str_contains($bucket, '/') || ! $this->safePath($name) || isset($seen[$key])) {
                throw new InvalidArgumentException('Storage metadata contains an unsafe or duplicated path.');
            }
            $seen[$key] = true;
            $entry = $indexedManifest[$key] ?? [];
            $source = realpath($this->directory.'/files/'.$key);
            $sourceRoot = realpath($this->directory.'/files');
            if (! $sourceRoot || ! $source || ! str_starts_with($source, $sourceRoot.DIRECTORY_SEPARATOR) || ! is_file($source)) {
                throw new InvalidArgumentException('A source storage file is missing or outside the source directory.');
            }
            if (($entry['status'] ?? '') !== 'ok' || ! preg_match('/^[a-f0-9]{64}$/', $entry['sha256'] ?? '')) {
                throw new InvalidArgumentException('A storage file has no successful checksum manifest entry.');
            }
            $bytes = filesize($source);
            if ($bytes !== ($entry['bytes'] ?? null) || (isset($object['metadata']['size']) && $bytes !== (int) $object['metadata']['size']) || hash_file('sha256', $source) !== $entry['sha256']) {
                throw new InvalidArgumentException('A source file failed size or checksum verification.');
            }
            if (! array_key_exists($bucket, $publicBuckets) || ! is_bool($publicBuckets[$bucket])) {
                throw new InvalidArgumentException('A storage bucket is missing its visibility definition.');
            }
            $files[] = [
                'bucket' => $bucket, 'name' => $name, 'source' => $source,
                'disk' => $publicBuckets[$bucket] ? 'public' : 'local',
                'destination' => 'imports/supabase/'.$key,
                'bytes' => $bytes, 'sha256' => $entry['sha256'],
            ];
        }
        if (count($manifest) !== count($objects)) {
            throw new InvalidArgumentException('The source object list and file manifest differ.');
        }

        return $files;
    }

    private function validateContent(array $records): void
    {
        $required = [
            'profiles' => ['display_name', 'role'],
            'poetry_collections' => ['title', 'slug'],
            'poems' => ['title', 'slug', 'content_md'],
            'books' => ['title', 'slug'],
            'chapters' => ['title', 'content_md'],
            'generated_assets' => ['type', 'prompt', 'image_url'],
        ];
        foreach ($required as $table => $columns) {
            $unique = [];
            foreach ($records[$table] as $row) {
                foreach ($columns as $column) {
                    if (! is_string($row[$column])) {
                        throw new InvalidArgumentException("{$table} has a missing required text field.");
                    }
                }
                foreach ($row as $column => $value) {
                    if (in_array($column, ['display_name', 'role', 'title', 'slug', 'type', 'theme_text_mode'], true) && $value !== null && (! is_string($value) || mb_strlen($value) > 255)) {
                        throw new InvalidArgumentException("{$table} has text that exceeds its target column.");
                    }
                }
                if ($table === 'chapters' && ! is_int($row['chapter_number'])) {
                    throw new InvalidArgumentException('A chapter number is not an integer.');
                }
                $key = $row['slug'] ?? ($table === 'chapters' ? $row['book_id'].':'.$row['chapter_number'] : null);
                if ($key !== null && isset($unique[$key])) {
                    throw new InvalidArgumentException("{$table} has a duplicate slug or chapter number.");
                }
                if ($key !== null) {
                    $unique[$key] = true;
                }
            }
        }
    }

    private function validateRelationships(array $records): void
    {
        $indexed = [];
        foreach ($records as $table => $rows) {
            $indexed[$table] = array_column($rows, null, 'id');
            if (count($indexed[$table]) !== count($rows)) {
                throw new InvalidArgumentException("{$table} contains duplicate UUIDs.");
            }
        }
        foreach ($records['profiles'] as $profile) {
            if (! isset($indexed['users'][$profile['id']])) {
                throw new InvalidArgumentException('A profile has no matching user.');
            }
        }
        foreach ($records['users'] as $user) {
            if (! isset($indexed['profiles'][$user['id']])) {
                throw new InvalidArgumentException('A user has no matching profile.');
            }
        }
        foreach (['poetry_collections', 'poems', 'books', 'chapters', 'generated_assets'] as $table) {
            foreach ($records[$table] as $row) {
                if (! isset($indexed['profiles'][$row['author_id']])) {
                    throw new InvalidArgumentException("{$table} contains an unknown owner.");
                }
                $parent = match ($table) {
                    'poems' => $indexed['poetry_collections'][$row['collection_id']] ?? null,
                    'chapters' => $indexed['books'][$row['book_id']] ?? null,
                    default => $row,
                };
                if (! $parent || $parent['author_id'] !== $row['author_id']) {
                    throw new InvalidArgumentException("{$table} contains a missing parent or inconsistent ownership.");
                }
            }
        }
    }

    private function timestamp(?string $timestamp): ?string
    {
        if ($timestamp === null) {
            return null;
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', $timestamp)) {
            throw new InvalidArgumentException('A source timestamp has an unsupported format.');
        }
        try {
            return CarbonImmutable::parse($timestamp, 'UTC')->utc()->format('Y-m-d H:i:s.u');
        } catch (\Exception) {
            throw new InvalidArgumentException('A source timestamp could not be parsed.');
        }
    }

    private function requireUuid(mixed $identifier, string $table): void
    {
        if (! is_string($identifier) || ! Str::isUuid($identifier)) {
            throw new InvalidArgumentException("{$table} contains a missing or invalid UUID.");
        }
    }

    private function safePath(string $path): bool
    {
        return $path !== '' && ! preg_match('/[\\\\\x00-\x1f\x7f]/', $path)
            && ! array_intersect(explode('/', $path), ['', '.', '..']);
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }
}
