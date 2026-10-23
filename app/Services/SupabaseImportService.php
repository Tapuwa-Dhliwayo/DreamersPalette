<?php

namespace App\Services;

use App\Support\SupabaseSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;

class SupabaseImportService
{
    private function targetTable(string $source): string
    {
        return $source === 'profiles' ? 'publishing_profiles' : $source;
    }

    public function preview(SupabaseSnapshot $snapshot): array
    {
        $this->checkTarget($snapshot);
        $this->checkDestinations($snapshot);
        $maps = $this->existingIds($snapshot);
        $this->checkUniqueTargets($snapshot, $maps);
        $tables = [];
        foreach ($snapshot->records as $table => $records) {
            $existing = count($maps[$table]);
            $tables[$table] = ['source' => count($records), 'existing' => $existing, 'new' => count($records) - $existing];
        }

        return ['tables' => $tables, 'files' => count($snapshot->files), 'bytes' => array_sum(array_column($snapshot->files, 'bytes'))];
    }

    public function import(SupabaseSnapshot $snapshot): array
    {
        $preview = $this->preview($snapshot);
        foreach ($snapshot->files as $file) {
            $this->copyFile($file);
        }

        DB::transaction(function () use ($snapshot): void {
            $maps = $this->existingIds($snapshot);
            $this->checkUniqueTargets($snapshot, $maps);
            foreach ($snapshot->records['users'] as $source) {
                // A family account is matched by email, never replaced by its Supabase identity.
                $user = DB::table('users')->where('email', $source['email'])->lockForUpdate()->first();
                if ($user) {
                    $maps['users'][$source['id']] = $user->id;

                    continue;
                }
                $profile = collect($snapshot->records['profiles'])->firstWhere('id', $source['id']);
                unset($source['id']);
                $source['name'] = $profile['display_name'];
                $id = DB::table('users')->insertGetId($source);
                $maps['users'][$profile['id']] = $id;
            }
            foreach ($snapshot->records as $table => $records) {
                if ($table === 'users') {
                    continue;
                }
                foreach ($records as $source) {
                    $mapped = $this->mapRecord($table, $source, $maps);
                    $query = DB::table($this->targetTable($table));
                    $id = $maps[$table][$source['id']] ?? null;
                    if ($id !== null) {
                        $query->where('id', $id)->update($mapped);
                    } else {
                        $id = $query->insertGetId($mapped);
                    }
                    $maps[$table][$source['id']] = $id;
                }
            }
            if (! $this->verify($snapshot)['valid']) {
                throw new RuntimeException('Imported publishing records did not match the snapshot; database changes were rolled back.');
            }
        });

        return $preview;
    }

    public function verify(SupabaseSnapshot $snapshot): array
    {
        $this->checkTarget($snapshot);
        $maps = $this->existingIds($snapshot);
        $mismatches = [];
        $tables = [];
        foreach ($snapshot->records as $table => $records) {
            $ids = array_values($maps[$table]);
            $stored = DB::table($this->targetTable($table))->whereIn('id', $ids)->get()->keyBy('id');
            $tables[$table] = ['source' => count($records), 'matched' => 0, 'extra' => DB::table($this->targetTable($table))->whereNotIn('id', $ids)->count()];
            foreach ($records as $source) {
                $actual = $stored->get($maps[$table][$source['id']] ?? null);
                if ($actual === null) {
                    $mismatches[] = "{$table}: a source record has no Laravel mapping.";

                    continue;
                }
                if ($table === 'users') {
                    // Shared names, password, verification and timestamps are owned by the family account.
                    if (strcasecmp($actual->email, $source['email']) !== 0 || password_get_info($actual->password)['algoName'] === 'unknown') {
                        $mismatches[] = 'users: shared account identity or password format is invalid.';

                        continue;
                    }
                    $tables[$table]['matched']++;

                    continue;
                }
                try {
                    $expected = $this->mapRecord($table, $source, $maps);
                } catch (InvalidArgumentException) {
                    $mismatches[] = "{$table}: a parent or author mapping is missing.";

                    continue;
                }
                $matches = true;
                foreach ($expected as $column => $value) {
                    if ($this->normalize($column, $actual->{$column}) !== $this->normalize($column, $value)) {
                        $mismatches[] = "{$table}: {$column} differs from the snapshot mapping.";
                        $matches = false;
                    }
                }
                if ($matches) {
                    $tables[$table]['matched']++;
                }
            }
        }
        $matchedFiles = 0;
        foreach ($snapshot->files as $file) {
            if ($this->fileChecksum($file) !== $file['sha256']) {
                $mismatches[] = 'A Laravel storage file is missing or has a different checksum.';

                continue;
            }
            $matchedFiles++;
        }

        return ['valid' => $mismatches === [], 'tables' => $tables, 'files' => $matchedFiles, 'mismatches' => array_count_values($mismatches)];
    }

    private function existingIds(SupabaseSnapshot $snapshot): array
    {
        $maps = [];
        foreach ($snapshot->records as $table => $records) {
            if ($table === 'users') {
                $maps[$table] = [];
                foreach ($records as $source) {
                    $user = DB::table('users')->where('email', $source['email'])->first();
                    if ($user) {
                        $maps[$table][$source['id']] = $user->id;
                    }
                }

                continue;
            }
            $maps[$table] = DB::table($this->targetTable($table))->whereIn('source_uuid', array_column($records, 'id'))->pluck('id', 'source_uuid')->all();
            if ($table !== 'profiles') {
                continue;
            }
            foreach ($records as $source) {
                $userId = $maps['users'][$source['id']] ?? null;
                $profile = $userId ? DB::table('publishing_profiles')->where('user_id', $userId)->first() : null;
                if ($profile && $profile->source_uuid !== null && $profile->source_uuid !== $source['id']) {
                    throw new InvalidArgumentException('A shared user is already linked to another publishing source identity.');
                }
                if (isset($maps[$table][$source['id']])) {
                    $linked = DB::table('publishing_profiles')->find($maps[$table][$source['id']]);
                    if (! $userId || (int) $linked->user_id !== (int) $userId) {
                        throw new InvalidArgumentException('A source author mapping conflicts with the shared user email.');
                    }
                }
                if ($profile) {
                    $maps[$table][$source['id']] = $profile->id;
                }
            }
        }

        return $maps;
    }

    private function mapRecord(string $table, array $source, array $maps): array
    {
        $uuid = $source['id'];
        unset($source['id']);
        $source['source_uuid'] = $uuid;
        if ($table === 'profiles') {
            $source['user_id'] = $maps['users'][$uuid] ?? throw new InvalidArgumentException('The source author has no Laravel user mapping.');
        }
        if (isset($source['author_id'])) {
            $source['author_id'] = $maps['users'][$source['author_id']] ?? throw new InvalidArgumentException('The source author has no Laravel user mapping.');
        }
        foreach (['collection_id' => 'poetry_collections', 'book_id' => 'books'] as $column => $parent) {
            if (isset($source[$column])) {
                $source[$column] = $maps[$parent][$source[$column]] ?? throw new InvalidArgumentException('The source parent has no Laravel mapping.');
            }
        }

        return $source;
    }

    private function checkTarget(SupabaseSnapshot $snapshot): void
    {
        foreach ($snapshot->records as $source => $records) {
            $table = $this->targetTable($source);
            if (! Schema::hasTable($table) || Schema::getColumnType($table, 'id') !== 'bigint') {
                throw new InvalidArgumentException('The target is missing the numeric Laravel publishing schema. Review the selected database and migrations.');
            }
            $expected = $source === 'users'
                ? ['id', 'name', 'email', 'password', 'email_verified_at', 'created_at', 'updated_at']
                : array_merge(array_diff(SupabaseSnapshot::COLUMNS[$source], ['id']), ['source_uuid']);
            if ($source === 'profiles') {
                $expected[] = 'user_id';
            }
            if (array_diff($expected, Schema::getColumnListing($table))) {
                throw new InvalidArgumentException("{$table} is missing publishing columns. Review the target migrations.");
            }
        }
    }

    private function checkUniqueTargets(SupabaseSnapshot $snapshot, array $maps): void
    {
        foreach ($snapshot->records as $table => $records) {
            foreach ($records as $record) {
                $unique = match ($table) {
                    'poetry_collections', 'poems', 'books' => ['slug' => $record['slug']],
                    'chapters' => isset($maps['books'][$record['book_id']]) ? ['book_id' => $maps['books'][$record['book_id']], 'chapter_number' => $record['chapter_number']] : null,
                    default => null,
                };
                if ($unique && DB::table($this->targetTable($table))->where($unique)->when(isset($maps[$table][$record['id']]), fn ($query) => $query->where('id', '!=', $maps[$table][$record['id']]))->exists()) {
                    throw new InvalidArgumentException("{$table} has a unique value owned by another publishing record. No records were imported.");
                }
            }
        }
    }

    private function copyFile(array $file): void
    {
        $disk = Storage::disk($file['disk']);
        if ($disk->exists($file['destination'])) {
            return;
        }
        $temporary = $file['destination'].'.import-'.bin2hex(random_bytes(12));
        $stream = fopen($file['source'], 'rb');
        if ($stream === false) {
            throw new RuntimeException('A verified source file could not be opened.');
        }
        try {
            if (! $disk->put($temporary, $stream, ['visibility' => $file['disk'] === 'public' ? 'public' : 'private'])) {
                throw new RuntimeException('A source file could not be copied to Laravel storage.');
            }
            $temporaryFile = array_replace($file, ['destination' => $temporary]);
            if ($this->fileChecksum($temporaryFile) !== $file['sha256'] || ! $disk->move($temporary, $file['destination'])) {
                throw new RuntimeException('A copied file could not be verified and finalized; database records were not imported.');
            }
        } finally {
            fclose($stream);
            if ($disk->exists($temporary)) {
                $disk->delete($temporary);
            }
        }
    }

    private function checkDestinations(SupabaseSnapshot $snapshot): void
    {
        foreach ($snapshot->files as $file) {
            $disk = Storage::disk($file['disk']);
            if ($disk->getAdapter()::class !== LocalFilesystemAdapter::class) {
                throw new InvalidArgumentException('This importer requires local public and private filesystem disks.');
            }
            $directory = $disk->path('');
            foreach (explode('/', $file['destination']) as $segment) {
                $directory = rtrim($directory, '/').'/'.$segment;
                if (is_link($directory)) {
                    throw new InvalidArgumentException('An import destination contains a symbolic link.');
                }
            }
            if ($disk->exists($file['destination']) && $this->fileChecksum($file) !== $file['sha256']) {
                throw new InvalidArgumentException('An existing destination file differs from the snapshot. Review it before importing.');
            }
        }
    }

    private function fileChecksum(array $file): ?string
    {
        $disk = Storage::disk($file['disk']);
        if (! $disk->exists($file['destination'])) {
            return null;
        }
        $stream = $disk->readStream($file['destination']);
        if ($stream === null || $stream === false) {
            return null;
        }
        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }

    private function normalize(string $column, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (str_ends_with($column, '_at')) {
            return CarbonImmutable::parse($value, 'UTC')->utc()->format('Y-m-d H:i:s.u');
        }
        if (in_array($column, ['is_published', 'is_preview'], true)) {
            return (bool) $value;
        }
        if ($column === 'chapter_number' || str_ends_with($column, '_id')) {
            return (int) $value;
        }
        if ($column === 'theme_overlay_opacity') {
            return number_format((float) $value, 3, '.', '');
        }

        return $value;
    }
}
