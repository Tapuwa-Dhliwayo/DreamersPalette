<?php

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ImageGenerationService
{
    public function generate(string $prompt, string $type): string
    {
        abort_unless(config('services.huggingface.token'), 503, 'Image generation is not configured.');
        $words = preg_split('/\s+/', preg_replace('/[.,;:!?\'"()\[\]{}]/u', ' ', $prompt));
        $literary = explode(' ', 'poem poems poetry verse verses stanza stanzas story stories novel chapter chapters book books writing written writes write wrote describing described describes describe description about titled called named words word text texts letter letters saying says said tells telling told reading read reads four three two five six seven eight nine ten collection collections anthology');
        $visual = trim(implode(' ', array_filter($words, fn ($word) => ! in_array(strtolower($word), $literary)))) ?: 'abstract atmospheric landscape';
        $style = $type === 'background'
            ? 'abstract art, atmospheric scene, cinematic lighting, soft color gradients, painterly brushstrokes, dreamy, ethereal, uncluttered, no people'
            : 'epic fantasy book cover art, cinematic lighting, painterly, elegant composition, sharp and detailed';
        $process = new Process([config('services.huggingface.node_binary'), base_path('scripts/generate-image.mjs')], base_path());
        $process->setTimeout(120);
        $process->setInput(json_encode([
            'token' => config('services.huggingface.token'),
            'model' => config('services.huggingface.model'),
            'prompt' => "painting of {$visual}, {$style}, purely visual, completely textless, no text, no words, no letters, no typography, no watermark, no signature, no logo",
        ], JSON_THROW_ON_ERROR));
        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            abort(504, 'Image generation timed out. Please retry.');
        }
        abort_unless($process->isSuccessful(), 502, 'Image generation is unavailable right now.');
        $bytes = $process->getOutput();
        abort_if(strlen($bytes) > 10 * 1024 * 1024, 502, 'The provider returned an oversized image.');
        $image = @getimagesizefromstring($bytes);
        abort_unless($image && in_array($image['mime'], ['image/jpeg', 'image/png', 'image/webp']), 502, 'The provider did not return a supported image.');

        return $bytes;
    }
}
