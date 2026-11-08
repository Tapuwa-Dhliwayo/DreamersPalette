<?php

namespace App\Services\Worldbuilding;

use App\Models\MagicDecision;
use App\Models\ManuscriptChapter;
use App\Models\World;
use App\Models\WorldEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProposalTypes
{
    public const PROFILE = ['summary', 'appearance', 'background', 'personality', 'motivations', 'strengths', 'vulnerabilities'];

    public static function definitions(): array
    {
        $text = 'nullable|string|max:30000';
        $id = 'nullable|integer|min:1';
        $names = ['aliases' => 'array|list|max:100', 'aliases.*' => 'string|max:255'];
        $subject = 'string|max:80';

        return [
            'person' => ['relation' => 'people', 'title' => 'name', 'rules' => $names + [
                'roles' => 'array|list|max:30', 'roles.*' => 'string|max:255', 'domain' => 'nullable|string|max:12000',
                'keep_previous_name' => 'boolean', 'profile' => 'array:'.implode(',', self::PROFILE),
                ...array_combine(array_map(fn ($name) => 'profile.'.$name, self::PROFILE), array_fill(0, count(self::PROFILE), $text)),
            ]],
            'event' => ['relation' => 'events', 'title' => 'title', 'rules' => [
                'position' => 'numeric|between:-99999999999,99999999999', 'date_label' => 'nullable|string|max:255',
                'calendar_id' => $id, 'calendar_day' => 'nullable|integer|min:0', 'person_ids' => 'array|list|max:200', 'person_ids.*' => 'integer|min:1|distinct',
            ]],
            'arc' => ['relation' => 'arcs', 'title' => 'title', 'children' => ['beats' => 'beats'], 'rules' => [
                'person_id' => $id, 'manuscript_id' => $id, 'measure' => 'string|max:255', 'children_mode' => 'in:merge,replace',
                'beats' => 'array|list|max:100', 'beats.*' => 'array:id,revision,title,event_id,event_ref,position,intensity,notes_md',
                'beats.*.id' => $id, 'beats.*.revision' => 'required_with:beats.*.id|integer|min:1', 'beats.*.title' => 'required|string|max:255',
                'beats.*.event_id' => $id, 'beats.*.event_ref' => 'nullable|string|max:80',
                'beats.*.position' => 'required|numeric|between:-99999999999,99999999999', 'beats.*.intensity' => 'nullable|integer|between:0,100', 'beats.*.notes_md' => $text,
            ]],
            'entry' => ['relation' => 'entries', 'title' => 'name', 'rules' => $names + ['type_id' => 'integer|min:1']],
            'relationship' => ['relation' => 'relationships', 'title' => 'label', 'rules' => [
                'source_key' => $subject, 'target_key' => $subject, 'period' => 'nullable|string|max:255', 'is_dependency' => 'boolean', 'is_mutual' => 'boolean',
            ]],
            'system' => ['relation' => 'systems', 'title' => 'name', 'children' => ['rules' => 'rules', 'cases' => 'cases'], 'rules' => $names + [
                'children_mode' => 'in:merge,replace', 'rules' => 'array|list|max:100',
                'rules.*' => 'array:id,revision,title,statement_md,conditions_md,consequences_md,exceptions_md,reasoning_md,decision_id',
                'rules.*.id' => $id, 'rules.*.revision' => 'required_with:rules.*.id|integer|min:1', 'rules.*.title' => 'required|string|max:255',
                'rules.*.statement_md' => 'required|string|max:30000', 'rules.*.conditions_md' => $text, 'rules.*.consequences_md' => $text,
                'rules.*.exceptions_md' => $text, 'rules.*.reasoning_md' => $text, 'rules.*.decision_id' => 'integer|min:1',
                'cases' => 'array|list|max:100', 'cases.*' => 'array:id,revision,title,situation_md,outcome_md,reasoning_md,rule_ids,rule_refs',
                'cases.*.id' => $id, 'cases.*.revision' => 'required_with:cases.*.id|integer|min:1', 'cases.*.title' => 'required|string|max:255',
                'cases.*.situation_md' => 'required|string|max:30000', 'cases.*.outcome_md' => $text, 'cases.*.reasoning_md' => $text,
                'cases.*.rule_ids' => 'array|list|max:100', 'cases.*.rule_ids.*' => 'integer|min:1|distinct',
                'cases.*.rule_refs' => 'array|list|max:100', 'cases.*.rule_refs.*' => 'integer|min:0|distinct',
            ]],
            'knowledge' => ['relation' => 'knowledge', 'title' => 'title', 'rules' => [
                'truth_md' => $text, 'manuscript_id' => $id, 'reveal_chapter_id' => $id, 'knowers' => 'array|list|max:100',
                'knowers.*' => 'array:key,name,belief,event_id,event_title', 'knowers.*.key' => 'required|distinct|'.$subject,
                'knowers.*.name' => 'nullable|string|max:255', 'knowers.*.belief' => 'required|string|max:10000',
                'knowers.*.event_id' => $id, 'knowers.*.event_title' => 'nullable|string|max:255',
            ]],
            'investigation' => ['relation' => 'investigations', 'title' => 'title', 'rules' => [
                'question_md' => $text, 'open_questions_md' => $text, 'conclusion_md' => $text, 'is_resolved' => 'boolean',
                'subjects' => 'array|list|max:40', 'subjects.*' => 'array:key,name', 'subjects.*.key' => 'required|distinct|'.$subject, 'subjects.*.name' => 'nullable|string|max:255',
                'evidence' => 'array|list|max:100', 'evidence.*' => 'array:text,source,supports',
                'evidence.*.text' => 'required|string|max:10000', 'evidence.*.source' => 'required|string|max:1000', 'evidence.*.supports' => 'required|boolean',
            ]],
            'map' => ['relation' => 'maps', 'title' => 'title', 'rules' => [
                'width_px' => 'integer|between:100,20000', 'height_px' => 'integer|between:100,20000', 'width_units' => 'nullable|numeric|min:0.001|max:1000000000',
                'unit' => 'string|max:40', 'pins' => 'array|list|max:300', 'pins.*' => 'array:key,name,entry_id,x,y',
                'pins.*.key' => 'required|uuid|distinct', 'pins.*.name' => 'required|string|max:255', 'pins.*.entry_id' => $id,
                'pins.*.x' => 'required|numeric|between:0,100', 'pins.*.y' => 'required|numeric|between:0,100',
                'journey' => 'array|list|max:500', 'journey.*' => 'uuid',
            ]],
            'alignment' => ['relation' => 'alignmentCharts', 'title' => 'title', 'children' => ['placements' => 'placements'], 'rules' => [
                'left_label' => 'string|max:255', 'right_label' => 'string|max:255', 'top_label' => 'string|max:255', 'bottom_label' => 'string|max:255',
                'children_mode' => 'in:merge,replace', 'placements' => 'array|list|max:100',
                'placements.*' => 'array:id,revision,name,person_id,system_id,x,y,reason_md',
                'placements.*.id' => $id, 'placements.*.revision' => 'required_with:placements.*.id|integer|min:1', 'placements.*.name' => 'required|string|max:255',
                'placements.*.person_id' => $id, 'placements.*.system_id' => $id, 'placements.*.x' => 'required|integer|between:-100,100',
                'placements.*.y' => 'required|integer|between:-100,100', 'placements.*.reason_md' => $text,
            ]],
            'comparison' => ['relation' => 'comparisons', 'title' => 'title', 'rules' => [
                'criteria' => 'array|list|min:1|max:20', 'criteria.*' => 'required|string|max:120|distinct:ignore_case',
                'subjects' => 'array|list|max:40', 'subjects.*' => 'array:key,name,cells', 'subjects.*.key' => 'required|distinct|'.$subject,
                'subjects.*.name' => 'nullable|string|max:255', 'subjects.*.cells' => 'present|array|list|max:20', 'subjects.*.cells.*' => 'nullable|string|max:10000',
            ]],
            'calendar' => ['relation' => 'calendars', 'title' => 'name', 'rules' => [
                'months' => 'array|list|min:1|max:60', 'months.*' => 'array:name,days', 'months.*.name' => 'required|string|max:100|distinct', 'months.*.days' => 'required|integer|between:1,1000',
                'weekdays' => 'array|list|max:30', 'weekdays.*' => 'string|max:100|distinct', 'leap_every' => 'nullable|integer|between:1,10000',
                'leap_month' => 'nullable|integer|min:1|max:60', 'leap_days' => 'integer|between:1,1000',
            ]],
            'thought' => ['rules' => []], 'question' => ['rules' => []], 'name' => ['rules' => []],
        ];
    }

    public static function kinds(): array
    {
        return array_keys(self::definitions());
    }

    public static function definition(string $kind): array
    {
        return self::definitions()[$kind] ?? throw ValidationException::withMessages(['structure' => 'Unknown proposal kind.']);
    }

    public static function fieldNames(string $kind): array
    {
        return array_values(array_filter(array_keys(self::definition($kind)['rules']), fn ($field) => ! str_contains($field, '.')));
    }

    public static function validateFields(string $kind, array $fields): array
    {
        $rules = self::definition($kind)['rules'];
        foreach (Arr::dot($fields) as $path => $content) {
            $rulePath = preg_replace('/\.\d+(?=\.|$)/', '.*', $path);
            $rule = $rules[$rulePath] ?? '';
            if ($content === '' && str_contains($rule, 'nullable') && (str_contains($rule, 'integer') || str_contains($rule, 'numeric'))) {
                data_set($fields, $path, null);
            }
        }
        $rules = array_map(fn ($rule) => 'sometimes|'.$rule, $rules);
        // Nested required rules apply whenever their parent collection is supplied.
        foreach (self::definition($kind)['rules'] as $key => $rule) {
            if (str_contains($key, '.') && str_contains($rule, 'required')) {
                $rules[$key] = str_replace('required_with:', 'required_with:fields.', $rule);
            }
        }

        $validated = Validator::make(['fields' => $fields], ['fields' => 'array:'.implode(',', self::fieldNames($kind)), ...collect($rules)->mapWithKeys(fn ($rule, $field) => ['fields.'.$field => $rule])->all()])->validate()['fields'];
        foreach (Arr::dot($validated) as $path => $content) {
            $rulePath = preg_replace('/\.\d+(?=\.|$)/', '.*', $path);
            $rule = self::definition($kind)['rules'][$rulePath] ?? '';
            if ($content !== null && str_contains($rule, 'integer')) {
                data_set($validated, $path, (int) $content);
            } elseif ($content !== null && str_contains($rule, 'boolean')) {
                data_set($validated, $path, (bool) $content);
            }
        }

        return $validated;
    }

    public static function validateScope(World $world, string $kind, array $fields, ?Model $target = null): void
    {
        $references = [
            'person_id' => 'people', 'person_ids.*' => 'people', 'manuscript_id' => 'manuscripts', 'calendar_id' => 'calendars',
            'type_id' => null, 'beats.*.event_id' => 'events', 'placements.*.person_id' => 'people', 'placements.*.system_id' => 'systems',
            'knowers.*.event_id' => 'events', 'pins.*.entry_id' => 'entries',
        ];
        foreach ($references as $path => $relation) {
            foreach (Arr::flatten([data_get($fields, $path)]) as $id) {
                if ($id === null) {
                    continue;
                }
                $exists = $relation ? $world->{$relation}()->withTrashed()->whereKey($id)->exists() : WorldEntryType::whereKey($id)->exists();
                if (! $exists) {
                    self::invalid('A '.$path.' reference is not available in this world.');
                }
            }
        }
        foreach (['source_key', 'target_key', 'subjects.*.key', 'knowers.*.key'] as $path) {
            foreach (Arr::flatten([data_get($fields, $path)]) as $key) {
                if ($key === null) {
                    continue;
                }
                if (! preg_match('/^(person|entry|system):[1-9][0-9]*$/', $key)) {
                    self::invalid('Choose a supported world subject for '.$path.'.');
                }
                app(WorldContext::class)->target($world, $key, true);
            }
        }
        foreach (self::definition($kind)['children'] ?? [] as $collection => $relation) {
            $seen = [];
            foreach ($fields[$collection] ?? [] as $child) {
                if (! empty($child['id'])) {
                    if (! $target || in_array($child['id'], $seen, true)) {
                        self::invalid('Child records must belong to this parent and appear once.');
                    }
                    $current = $target->{$relation}()->findOrFail($child['id']);
                    if ((int) $current->revision !== (int) ($child['revision'] ?? 0)) {
                        self::invalid('A child record changed. Refresh it before proposing changes.');
                    }
                    $seen[] = $child['id'];
                }
            }
        }
        foreach ($fields['beats'] ?? [] as $beat) {
            if (filled($beat['event_ref'] ?? null) && ! preg_match('/^(event|idea|board):[0-9]+$/', $beat['event_ref'])) {
                self::invalid('Invalid beat event reference.');
            }
            if (str_starts_with($beat['event_ref'] ?? '', 'event:')) {
                app(WorldContext::class)->target($world, $beat['event_ref'], true);
            }
        }
        foreach ($fields['placements'] ?? [] as $placement) {
            if (! empty($placement['person_id']) && ! empty($placement['system_id'])) {
                self::invalid('A placement cannot represent both a character and a system.');
            }
        }
        foreach ($fields['rules'] ?? [] as $rule) {
            if (isset($rule['decision_id']) && ! MagicDecision::whereKey($rule['decision_id'])->exists()) {
                self::invalid('Choose an existing magic decision.');
            }
        }
        foreach ($fields['cases'] ?? [] as $case) {
            foreach ($case['rule_ids'] ?? [] as $id) {
                if (! $target || ! $target->rules()->withTrashed()->whereKey($id)->exists()) {
                    self::invalid('A case rule must belong to its system.');
                }
            }
            foreach ($case['rule_refs'] ?? [] as $index) {
                if (! isset($fields['rules'][$index])) {
                    self::invalid('A case references a missing proposed rule.');
                }
            }
        }
        $combined = array_replace($target?->attributesToArray() ?? [], $fields);
        if ($kind === 'comparison') {
            foreach ($combined['subjects'] ?? [] as $subject) {
                if (count($subject['cells'] ?? []) !== count($combined['criteria'] ?? [])) {
                    self::invalid('Every comparison row must have one cell per criterion.');
                }
            }
        }
        if ($kind === 'map') {
            foreach ($combined['journey'] ?? [] as $pin) {
                if (! in_array($pin, array_column($combined['pins'] ?? [], 'key'), true)) {
                    self::invalid('The journey references a missing map pin.');
                }
            }
        }
        if ($kind === 'knowledge' && ! empty($combined['manuscript_id']) && empty($combined['reveal_chapter_id'])) {
            self::invalid('Choose a reveal chapter when assigning its manuscript.');
        }
        if ($kind === 'investigation' && ($combined['is_resolved'] ?? false) && ! filled($combined['conclusion_md'] ?? null)) {
            self::invalid('A resolved investigation needs its conclusion.');
        }
        if (isset($fields['reveal_chapter_id'])) {
            $chapter = ManuscriptChapter::whereKey($fields['reveal_chapter_id'])->whereHas('manuscript', fn ($query) => $query->where('world_id', $world->id)->where('author_id', $world->author_id))->first();
            if (! $chapter || (isset($combined['manuscript_id']) && $chapter->manuscript_id !== (int) $combined['manuscript_id'])) {
                self::invalid('The reveal chapter must belong to the selected manuscript in this world.');
            }
        }
        if ($kind === 'event' && isset($combined['calendar_day'])) {
            if (empty($combined['calendar_id'])) {
                self::invalid('A calendar day requires a calendar.');
            }
            $calendar = $world->calendars()->withTrashed()->findOrFail($combined['calendar_id']);
            if ($calendar->daysBeforeYear(1000001) <= $combined['calendar_day']) {
                self::invalid('The event date exceeds this calendar’s supported range.');
            }
        }
        if ($kind === 'calendar' && ! empty($combined['leap_every']) && (empty($combined['leap_month']) || $combined['leap_month'] > count($combined['months'] ?? []))) {
            self::invalid('Choose a leap month in this calendar.');
        }
    }

    public static function structureProblems(string $kind, array $fields, ?Model $target = null): array
    {
        $problems = [];
        $collections = match ($kind) {
            'arc' => ['beats'], 'comparison' => ['subjects'], 'alignment' => ['placements'], 'system' => ['rules'], 'calendar' => ['months'], 'map' => ['pins'],
            default => [],
        };
        foreach ($collections as $collection) {
            $existing = $target ? (isset(self::definition($kind)['children'][$collection]) ? $target->{$collection}()->count() : count($target->{$collection} ?? [])) : 0;
            $replacesCollection = array_key_exists($collection, $fields)
                && (! isset(self::definition($kind)['children'][$collection]) || ($fields['children_mode'] ?? 'merge') === 'replace');
            if ($replacesCollection) {
                $existing = 0;
            }
            if (empty($fields[$collection]) && $existing === 0) {
                $problems[] = 'No '.$collection.' supplied.';
            }
        }

        foreach (match ($kind) {
            'knowledge' => ['truth_md'],'investigation' => ['question_md'],default => []
        } as $field) {
            if (! filled(array_key_exists($field, $fields) ? $fields[$field] : $target?->{$field})) {
                $problems[] = 'No '.str_replace('_', ' ', $field).' supplied.';
            }
        }
        if ($kind === 'person' && ! $target && ! array_filter($fields['profile'] ?? [], fn ($section) => filled($section)) && empty($fields['roles']) && ! filled($fields['domain'] ?? null)) {
            $problems[] = 'No profile or character attributes supplied.';
        }

        return $problems;
    }

    public static function validateLinkKinds(string $kind, array $kinds): void
    {
        $valid = match ($kind) {
            'participant' => count(array_diff(['event', 'person'], $kinds)) === 0,
            'before' => $kinds === ['event', 'event'],
            'relationship','depends' => count(array_diff($kinds, ['person', 'entry', 'system'])) === 0,
            'connection' => true,
            default => false,
        };
        if (! $valid) {
            self::invalid('The connection kind does not match its endpoints.');
        }
    }

    public static function invalid(string $message): never
    {
        throw ValidationException::withMessages(['structure' => $message]);
    }

    public static function referenceFields(string $kind, Model $record): array
    {
        $snapshot = app(WorldContext::class)->snapshot($record);
        $fields = collect($snapshot)->only(self::fieldNames($kind))->all();
        if ($kind === 'event') {
            $fields['person_ids'] = $snapshot['participants'] ?? [];
        }
        foreach (self::definition($kind)['children'] ?? [] as $field => $relation) {
            $fields[$field] = collect($snapshot[$field] ?? [])->map(fn ($child) => collect($child)->only(self::childFields($kind, $field))->all())->all();
        }

        return $fields;
    }

    public static function childFields(string $kind, string $collection): array
    {
        $prefix = $collection.'.*.';

        return array_values(array_unique(array_map(fn ($key) => substr($key, strlen($prefix)), array_filter(array_keys(self::definition($kind)['rules']), fn ($key) => str_starts_with($key, $prefix) && ! str_contains(substr($key, strlen($prefix)), '.')))));
    }

    public static function examples(): array
    {
        return [
            'person' => ['aliases' => [], 'roles' => [], 'domain' => null, 'profile' => array_fill_keys(self::PROFILE, null)],
            'event' => ['position' => 10, 'date_label' => null, 'calendar_id' => null, 'calendar_day' => null, 'person_ids' => []],
            'arc' => ['person_id' => null, 'manuscript_id' => null, 'measure' => 'Trust', 'children_mode' => 'merge', 'beats' => [['title' => 'Replace with a supported turning point', 'position' => 10, 'event_id' => null, 'intensity' => null, 'notes_md' => 'Supporting observation and citation']]],
            'entry' => ['aliases' => [], 'type_id' => 'USE_SUPPLIED_ENTRY_TYPE_ID'],
            'relationship' => ['source_key' => 'person:ID_FROM_CATALOGUE', 'target_key' => 'person:OTHER_ID_FROM_CATALOGUE', 'is_mutual' => true, 'is_dependency' => false, 'period' => null],
            'system' => ['aliases' => [], 'children_mode' => 'merge', 'rules' => [['title' => 'Replace with rule title', 'statement_md' => 'Supported rule statement', 'conditions_md' => '', 'consequences_md' => '', 'exceptions_md' => '', 'reasoning_md' => '', 'decision_id' => 'USE_SUPPLIED_DECISION_ID']], 'cases' => [['title' => 'Replace with case title', 'situation_md' => 'Supported situation', 'outcome_md' => 'Supported outcome', 'reasoning_md' => '', 'rule_refs' => [0]]]],
            'knowledge' => ['truth_md' => 'Supported established truth', 'knowers' => [['key' => 'person:ID_FROM_CATALOGUE', 'belief' => 'Supported knowledge or belief', 'event_id' => null]], 'manuscript_id' => null, 'reveal_chapter_id' => null],
            'investigation' => ['question_md' => 'The actual question', 'subjects' => [['key' => 'person:ID_FROM_CATALOGUE']], 'evidence' => [['text' => 'Evidence statement', 'source' => 'Author message or supplied citation', 'supports' => true]], 'open_questions_md' => '', 'conclusion_md' => '', 'is_resolved' => false],
            'map' => ['width_px' => 1200, 'height_px' => 800, 'width_units' => null, 'unit' => 'km', 'pins' => [['key' => 'VALID_UUID_FOR_PIN', 'name' => 'Supported place', 'entry_id' => null, 'x' => 25, 'y' => 40]], 'journey' => ['VALID_UUID_FOR_PIN']],
            'alignment' => ['left_label' => 'Replace axis label', 'right_label' => 'Replace axis label', 'top_label' => 'Replace axis label', 'bottom_label' => 'Replace axis label', 'children_mode' => 'merge', 'placements' => [['name' => 'Subject from catalogue', 'person_id' => 'EXISTING_PERSON_ID', 'x' => 0, 'y' => 0, 'reason_md' => 'Explicitly qualified interpretation']]],
            'comparison' => ['criteria' => ['First criterion', 'Second criterion'], 'subjects' => [['key' => 'person:ID_FROM_CATALOGUE', 'cells' => ['Supported comparison', 'Supported comparison']]]],
            'calendar' => ['months' => [['name' => 'Author-approved month', 'days' => 30]], 'weekdays' => [], 'leap_every' => null, 'leap_month' => null, 'leap_days' => 1],
            'thought' => [], 'question' => [], 'name' => [],
        ];
    }

    public static function prompt(): array
    {
        return collect(self::definitions())->map(fn ($definition, $kind) => [
            'fields' => $definition['rules'], 'children' => array_keys($definition['children'] ?? []),
            'supports_canon' => isset($definition['relation']),
            'example_fields' => self::examples()[$kind],
        ])->all();
    }

    public static function summary(string $kind, array $fields): array
    {
        $counts = [];
        foreach (['beats', 'rules', 'cases', 'placements', 'subjects', 'knowers', 'pins', 'journey', 'person_ids', 'months'] as $field) {
            if (array_key_exists($field, $fields)) {
                $counts[$field] = count($fields[$field] ?? []);
            }
        }

        return $counts;
    }
}
