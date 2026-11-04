<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantTurn;
use App\Models\WorldExercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SandboxProposals
{
    public const KINDS = ['thought', 'question', 'person', 'event', 'relationship', 'entry', 'system', 'arc', 'knowledge', 'investigation', 'name'];

    public function decode(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($content));
        try {
            $proposal = json_decode($content, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['proposals' => 'The response was not valid proposal JSON. The full response is retained; ask the assistant to try again.']);
        }
        $validated = Validator::make(is_array($proposal) ? $proposal : [], [
            'reply' => ['required', 'string', 'max:30000'], 'ideas' => ['present', 'array', 'max:20'],
            'ideas.*.kind' => ['required', Rule::in(self::KINDS)], 'ideas.*.title' => ['required', 'string', 'max:255'],
            'ideas.*.notes_md' => ['nullable', 'string', 'max:30000'], 'ideas.*.uncertainty' => ['nullable', 'string', 'max:255'],
            'ideas.*.alternative' => ['nullable', 'string', 'max:255'], 'ideas.*.lane' => ['nullable', 'string', 'max:255'],
            'ideas.*.date_label' => ['nullable', 'string', 'max:255'], 'ideas.*.fields' => ['nullable', 'array'],
            'links' => ['sometimes', 'array', 'max:40'], 'links.*.source' => ['required', 'integer', 'min:0'], 'links.*.target' => ['required', 'integer', 'min:0'],
            'links.*.kind' => ['required', Rule::in(['connection', 'before', 'participant', 'relationship', 'depends'])], 'links.*.label' => ['required', 'string', 'max:255'],
        ])->validate();
        foreach ($validated['links'] ?? [] as $link) {
            if ($link['source'] === $link['target'] || ! isset($validated['ideas'][$link['source']], $validated['ideas'][$link['target']])) {
                throw ValidationException::withMessages(['proposals' => 'A proposed connection had an invalid endpoint. The original response is retained.']);
            }
        }

        return $validated;
    }

    public function collect(AssistantTurn $turn): void
    {
        DB::transaction(function () use ($turn): void {
            $exercise = WorldExercise::lockForUpdate()->findOrFail($turn->exercise_id);
            abort_unless($exercise->world && ! $exercise->world->trashed(), 409);
            $ideas = [];
            foreach ($turn->proposals['ideas'] ?? [] as $index => $fields) {
                $ideas[$index] = $exercise->ideas()->firstOrCreate(['source_token' => 'turn:'.$turn->id.':'.$index], array_merge(collect($fields)->only(['kind', 'title', 'notes_md', 'uncertainty', 'alternative', 'lane', 'date_label'])->all(), ['fields' => [], 'origin' => 'assistant', 'source_message_id' => $turn->response_id, 'x' => 40 + ($index % 3) * 260, 'y' => 40 + intdiv($index, 3) * 170, 'position' => $index]));
            }
            foreach ($turn->proposals['links'] ?? [] as $link) {
                $exercise->links()->firstOrCreate(['source_id' => $ideas[$link['source']]->id, 'target_id' => $ideas[$link['target']]->id, 'kind' => $link['kind'], 'label' => $link['label']]);
            }
        });
    }
}
