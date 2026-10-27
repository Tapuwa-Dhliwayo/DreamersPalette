<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DraftMapImageController extends Controller
{
    public function __invoke(Manuscript $manuscript, int $draft, int $map)
    {
        Gate::authorize('view', $manuscript);
        $snapshot = $manuscript->drafts()->findOrFail($draft);
        $captured = collect($snapshot->world_context['development']['maps'] ?? [])->firstWhere('id', $map);
        $path = $captured['image_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
