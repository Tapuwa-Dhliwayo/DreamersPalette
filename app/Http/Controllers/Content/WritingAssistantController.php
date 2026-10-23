<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\WritingAssistantRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WritingAssistantController extends Controller
{
    public function __invoke(WritingAssistantRequest $request)
    {
        $sentence = $request->validated('sentence');
        $settings = config('services.languagetool');
        abort_if((bool) $settings['username'] !== (bool) $settings['key'], 503, 'Writing assistance is not configured.');
        $form = ['text' => $sentence, 'language' => $settings['language']];
        if ($settings['username']) {
            $form += ['username' => $settings['username'], 'apiKey' => $settings['key']];
        }
        try {
            $response = Http::asForm()->timeout(20)->post($settings['url'], $form);
        } catch (ConnectionException) {
            abort(502, 'Grammar suggestions are unavailable right now.');
        }
        abort_unless($response->successful(), 502, 'Grammar suggestions are unavailable right now.');
        // LanguageTool offsets use UTF-16 code units, as does the React editor.
        $utf16 = mb_convert_encoding($sentence, 'UTF-16LE', 'UTF-8');
        $suggestions = [];
        foreach ($response->json('matches', []) as $index => $match) {
            $offset = $match['offset'] ?? -1;
            $length = $match['length'] ?? -1;
            if (! is_int($offset) || ! is_int($length) || $offset < 0 || $length < 0 || ($offset + $length) * 2 > strlen($utf16)) {
                continue;
            }
            $rule = $match['rule']['id'] ?? 'grammar';
            $replacements = [];
            foreach (array_slice($match['replacements'] ?? [], 0, 8) as $replacementIndex => $replacement) {
                if (trim($replacement['value'] ?? '') !== '') {
                    $replacements[] = ['id' => "{$rule}:{$offset}:{$replacementIndex}", 'type' => 'grammar', 'label' => $replacement['value'], 'replacement' => $replacement['value']];
                }
            }
            $suggestions[] = [
                'id' => "{$rule}:{$offset}:{$index}", 'type' => 'grammar', 'message' => $match['message'] ?? 'Review this wording.',
                'category' => $match['rule']['category']['name'] ?? 'Grammar', 'ruleId' => $rule,
                'offset' => $offset, 'length' => $length, 'original' => mb_convert_encoding(substr($utf16, $offset * 2, $length * 2), 'UTF-8', 'UTF-16LE'), 'replacements' => $replacements,
            ];
        }

        return response()->json(['provider' => 'languagetool', 'suggestions' => $suggestions]);
    }
}
