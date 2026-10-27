<?php

use App\Models\ManuscriptCompile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('dreamers:prune-compiles', function (): void {
    $removed = 0;
    ManuscriptCompile::where('expires_at', '<=', now())->orderBy('id')->chunkById(100, function ($compiles) use (&$removed): void {
        foreach ($compiles as $compile) {
            if ($compile->path && ! Storage::disk('local')->delete($compile->path)) {
                $this->warn('Could not remove expired export '.$compile->id.'. It will be retried.');

                continue;
            }
            $compile->delete();
            $removed++;
        }
    });
    $this->info($removed.' expired export copies removed. Manuscripts and finished drafts were retained.');
})->purpose('Remove expired temporary private manuscript export files and manifests');

Schedule::command('dreamers:prune-compiles')->daily()->withoutOverlapping();
