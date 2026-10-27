<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ExportDraftController extends Controller
{
    public function __invoke(Manuscript $manuscript, int $draft)
    {
        Gate::authorize('export', $manuscript);
        $snapshot = $manuscript->drafts()->with('chapters')->whereKey($draft)->firstOrFail();
        $filename = (Str::slug($snapshot->manuscript_title) ?: 'manuscript').'-draft-'.$snapshot->number.'.md';

        return response()->streamDownload(function () use ($snapshot): void {
            echo '# '.$snapshot->manuscript_title."\n\n";
            foreach ($snapshot->chapters as $chapter) {
                echo '## '.$chapter->title."\n\n".$chapter->content_md."\n\n";
            }
        }, $filename, ['Content-Type' => 'text/markdown; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
