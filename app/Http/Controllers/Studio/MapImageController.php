<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\World;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MapImageController extends Controller
{
    public function __invoke(Request $request, World $world, int $map)
    {
        Gate::authorize('view', $world);
        $record = $world->maps()->withTrashed()->findOrFail($map);
        $path = $request->integer('version') ? $record->versions()->findOrFail($request->integer('version'))->snapshot['image_path'] : $record->image_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
