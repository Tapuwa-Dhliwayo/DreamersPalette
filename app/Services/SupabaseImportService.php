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
    public function preview(SupabaseSnapshot $snapshot): array
    {
        $this->checkTarget($snapshot);
        $this->checkDestinations($snapshot);
        $this->checkUniqueTargets($snapshot);
        $tables = [];
        foreach ($snapshot->records as $table => $records) {
            $existing = DB::table($table)->whereIn('id', array_column($records, 'id'))->count();
            $tables[$table] = ['source' => count($records), 'existing' => $existing, 'new' => count($records) - $existing];
        }

        return [
            'tables' => $tables,
            'files' => count($snapshot->files),
            'bytes' => array_sum(array_column($snapshot->files, 'bytes')),
        ];
    }

    public function import(SupabaseSnapshot $snapshot): array
    {
        $preview = $this->preview($snapshot);
        // Files are verified first. If SQL fails, only unreferenced copies can remain; reruns reuse them.
        foreach ($snapshot->files as $file) {
            $this->copyFile($file);
        }

        DB::transaction(function () use ($snapshot): void {
            foreach ($snapshot->records as $table => $records) {
                foreach ($records as $record) {
                    $id = $record['id'];
                    $existing = DB::table($table)->where('id', $id)->lockForUpdate()->exists();
                    if (! $existing) {
                        DB::table($table)->insert($record);

                        continue;
                    }
                    unset($record['id']);
                    // An import must never undo a password change made after the first import.
                    if ($table === 'users') {
                        unset($record['password']);
                    }
                    DB::table($table)->where('id', $id)->update($record);
                }
            }
            $verification = $this->verify($snapshot);
            if (! $verification['valid']) {
                throw new RuntimeException('Imported records did not match the source; all database changes were rolled back.');
            }
        });

        return $preview;
    }

    public function verify(SupabaseSnapshot $snapshot): array
    {
        $this->checkTarget($snapshot);
        $mismatches = [];
        $tables = [];
        foreach ($snapshot->records as $table => $records) {
            $ids = array_column($records, 'id');
            $stored = DB::table($table)->whereIn('id', $ids)->get()->keyBy('id');
            $tables[$table] = ['source' => count($records), 'matched' => 0, 'extra' => DB::table($table)->whereNotIn('id', $ids)->count()];
            foreach ($records as $expected) {
                $actual = $stored->get($expected['id']);
                if ($actual === null) {
                    $mismatches[] = "{$table}: a source UUID is missing.";

                    continue;
                }
                $matches = true;
                if ($table === 'users' && password_get_info($actual->password)['algoName'] === 'unknown') {
                    $mismatches[] = 'users: the target password hash is unsupported.';
                    $matches = false;
                }
                foreach ($expected as $column => $value) {
                    if ($table === 'users' && $column === 'password') {
                        continue;
                    }
                    if ($this->normalize($column, $actual->{$column}) !== $this->normalize($column, $value)) {
                        $mismatches[] = "{$table}: {$column} differs from the source mapping.";
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

    private function checkTarget(SupabaseSnapshot $snapshot): void
    {
        foreach ($snapshot->records as $table => $records) {
            if (! Schema::hasTable($table)) {
                throw new InvalidArgumentException('The target is missing the Laravel content schema. Run its migrations first.');
            }
            if (Schema::getColumnType($table, 'id', true) !== 'char(36)') {
                throw new InvalidArgumentException('The target UUID schema does not match this application. Check the selected database.');
            }
            $expected = $table === 'users'
                ? ['id', 'email', 'password', 'email_verified_at', 'created_at', 'updated_at']
                : SupabaseSnapshot::COLUMNS[$table];
            if (array_diff($expected, Schema::getColumnListing($table))) {
                throw new InvalidArgumentException("{$table} is missing source columns. Review the target migrations.");
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

    private function checkUniqueTargets(SupabaseSnapshot $snapshot): void
    {
        foreach ($snapshot->records as $table => $records) {
            foreach ($records as $record) {
                $unique = match ($table) {
                    'users' => ['email' => $record['email']],
                    'poetry_collections', 'poems', 'books' => ['slug' => $record['slug']],
                    'chapters' => ['book_id' => $record['book_id'], 'chapter_number' => $record['chapter_number']],
                    default => null,
                };
                if ($unique && DB::table($table)->where($unique)->where('id', '!=', $record['id'])->exists()) {
                    throw new InvalidArgumentException("{$table} has a unique value already owned by another target UUID. No records were imported.");
                }
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
        if ($column === 'chapter_number') {
            return (int) $value;
        }
        if ($column === 'theme_overlay_opacity') {
            return number_format((float) $value, 3, '.', '');
        }

        return $value;
    }
}
