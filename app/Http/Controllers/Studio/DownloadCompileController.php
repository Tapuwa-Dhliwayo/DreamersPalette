<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use App\Models\ManuscriptCompile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DownloadCompileController extends Controller
{
    public function __invoke(Manuscript $manuscript, int $compile)
    {
        Gate::authorize('export', $manuscript);
        $export = ManuscriptCompile::where('manuscript_id', $manuscript->id)->where('created_by', auth()->id())->findOrFail($compile);
        abort_unless($export->status === 'ready' && $export->expires_at->isFuture(), 410, 'This export expired. Compile a new copy.');
        abort_unless($export->path && Storage::disk('local')->exists($export->path), 410, 'This export is no longer available. Compile a new copy.');

        return Storage::disk('local')->download($export->path, $export->filename, ['Content-Type' => match ($export->format) {
            'pdf' => 'application/pdf', 'review' => 'application/zip', default => 'text/markdown; charset=UTF-8'
        }, 'X-Content-Type-Options' => 'nosniff']);
    }
}
