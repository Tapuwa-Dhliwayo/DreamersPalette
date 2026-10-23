<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\GenerateAssetRequest;
use App\Http\Requests\Content\UploadImageRequest;
use App\Models\GeneratedAsset;
use App\Services\ImageGenerationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function upload(UploadImageRequest $request)
    {
        $path = $request->file('image')->store('uploads/'.$request->user()->id, 'public');
        abort_unless($path, 500, 'The image could not be stored.');

        return response()->json(['url' => '/storage/'.$path], 201);
    }

    public function generate(GenerateAssetRequest $request, ImageGenerationService $images)
    {
        Gate::authorize('create', GeneratedAsset::class);
        $fields = $request->validated();
        $bytes = $images->generate($fields['prompt'], $fields['type']);
        $mime = getimagesizefromstring($bytes)['mime'];
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
        $path = 'generated/'.$request->user()->id.'/'.Str::uuid().'.'.$extension;
        abort_unless(Storage::disk('public')->put($path, $bytes), 500, 'The generated image could not be stored.');
        try {
            $asset = new GeneratedAsset($fields + ['image_url' => '/storage/'.$path]);
            $asset->author_id = $request->user()->id;
            $asset->save();
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return response()->json(['provider' => 'huggingface', 'imageUrl' => $asset->image_url, 'asset' => $asset], 201);
    }
}
