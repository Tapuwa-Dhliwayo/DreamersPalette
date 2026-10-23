<?php

namespace App\Console\Commands;

use App\Services\SupabaseImportService;
use App\Support\SupabaseSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\QueryException;
use Throwable;

class ImportSupabase extends Command
{
    use ConfirmableTrait;

    protected $signature = 'dreamers:import-supabase
        {--source= : Directory containing the snapshot, schema, manifest, and files}
        {--dry-run : Validate and show the import without changing records or copying files}
        {--verify : Compare existing records and files without importing}
        {--force : Allow an explicitly approved production import}';

    protected $description = 'Import a verified Supabase snapshot into MySQL and Laravel storage';

    public function handle(SupabaseImportService $importer): int
    {
        if ($this->option('dry-run') && $this->option('verify')) {
            $this->error('Choose either --dry-run or --verify.');

            return self::INVALID;
        }
        if (! $this->option('dry-run') && ! $this->option('verify') && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }
        $lock = fopen(storage_path('framework/cache/supabase-import.lock'), 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            $this->error('Another snapshot operation is already running.');

            return self::FAILURE;
        }
        try {
            if (config('database.default') !== 'mysql') {
                $this->error('Configure a MySQL target before importing.');

                return self::INVALID;
            }
            $source = $this->option('source') ?: config('supabase-migration.source_path');
            $snapshot = new SupabaseSnapshot($source, (string) config('supabase-migration.source_url'));
            $this->line('Target: '.config('database.connections.mysql.database'));
            $this->line('Snapshot captured: '.CarbonImmutable::parse($snapshot->capturedAt, 'UTC')->asDateTime().' UTC');
            $this->line('Snapshot SHA-256: '.$snapshot->fingerprint);
            if ($this->option('verify')) {
                $verification = $importer->verify($snapshot);
                $this->table(['Table', 'Source', 'Matching', 'Extra retained'], array_map(
                    fn ($table, $counts) => [$table, $counts['source'], $counts['matched'], $counts['extra']],
                    array_keys($verification['tables']), $verification['tables'],
                ));
                $this->line('Files matching SHA-256: '.$verification['files'].'/'.count($snapshot->files));
                foreach ($verification['mismatches'] as $message => $count) {
                    $this->error($message.' ('.$count.')');
                }

                return $verification['valid'] ? self::SUCCESS : self::FAILURE;
            }
            $summary = $this->option('dry-run') ? $importer->preview($snapshot) : $importer->import($snapshot);
            $this->table(['Table', 'Source', 'Mapped records', 'New records'], array_map(
                fn ($table, $counts) => [$table, $counts['source'], $counts['existing'], $counts['new']],
                array_keys($summary['tables']), $summary['tables'],
            ));
            $this->line('Files: '.$summary['files'].'; bytes: '.$summary['bytes']);
            $this->info($this->option('dry-run') ? 'Validation complete. No records or asset files changed.' : 'Import verified. Shared user accounts and unrelated blog records were preserved.');

            return self::SUCCESS;
        } catch (QueryException $exception) {
            $this->error('Database operation failed (SQLSTATE '.$exception->getCode().'). Check connectivity, migrations, or uniqueness conflicts. SQL and record values are withheld.');

            return self::FAILURE;
        } catch (Throwable $exception) {
            // Source data and password hashes must never appear in operational output.
            $this->error($exception instanceof \InvalidArgumentException || $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'Snapshot operation failed; check the snapshot format and filesystem permissions.');

            return self::FAILURE;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
