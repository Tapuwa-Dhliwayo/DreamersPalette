<?php

namespace App\Livewire\Forms\Worlds;

use App\Services\Worldbuilding\ProposalTypes;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SandboxIdeaForm extends Form
{
    public string $kind = 'thought';

    public string $title = '';

    public string $notes_md = '';

    public string $uncertainty = '';

    public string $alternative = '';

    public string $lane = '';

    public string $position = '0';

    public string $date_label = '';

    public bool $keep_previous_name = false;

    public string $aliases = '';

    public string $roles = '';

    public string $domain = '';

    public ?int $type_id = null;

    public string $measure = 'Pressure';

    public string $source_key = '';

    public string $target_key = '';

    public string $period = '';

    public string $left_label = '';

    public string $right_label = '';

    public string $top_label = '';

    public string $bottom_label = '';

    public string $criteria = '';

    public bool $is_dependency = false;

    public int $revision = 1;

    public array $structured = [];

    public string $completeness = 'partial';

    public string $missing = 'This draft is not yet complete.';

    public function fields(): array
    {
        $fields = $this->validate(['kind' => ['required', Rule::in(ProposalTypes::kinds())], 'title' => 'required|string|max:255', 'notes_md' => 'nullable|string|max:50000', 'uncertainty' => 'nullable|string|max:255', 'alternative' => 'nullable|string|max:255', 'lane' => 'nullable|string|max:255', 'position' => 'required|numeric|between:-99999999999,99999999999', 'date_label' => 'nullable|string|max:255', 'aliases' => 'nullable|string|max:10000', 'roles' => 'nullable|string|max:3000', 'domain' => 'nullable|string|max:12000', 'type_id' => 'nullable|integer|min:1', 'measure' => 'nullable|string|max:255', 'source_key' => 'nullable|string|max:80', 'target_key' => 'nullable|string|max:80', 'period' => 'nullable|string|max:255', 'keep_previous_name' => 'boolean', 'is_dependency' => 'boolean', 'left_label' => 'nullable|string|max:255', 'right_label' => 'nullable|string|max:255', 'top_label' => 'nullable|string|max:255', 'bottom_label' => 'nullable|string|max:255', 'criteria' => 'nullable|string|max:5000']);
        $additional = collect($fields)->only(['domain', 'type_id', 'measure', 'source_key', 'target_key', 'period', 'keep_previous_name', 'is_dependency', 'left_label', 'right_label', 'top_label', 'bottom_label'])->all();
        $additional['aliases'] = array_values(array_filter(array_map('trim', preg_split('/\\r?\\n/', $this->aliases))));
        $additional['roles'] = array_values(array_filter(array_map('trim', explode(',', $this->roles))));
        $fields = collect($fields)->except(['aliases', 'roles', 'domain', 'type_id', 'measure', 'source_key', 'target_key', 'period', 'keep_previous_name', 'is_dependency', 'left_label', 'right_label', 'top_label', 'bottom_label'])->all();
        $additional['criteria'] = array_values(array_filter(array_map('trim', preg_split('/\\r?\\n/', $this->criteria))));
        unset($fields['criteria']);
        if ($this->kind === 'event') {
            $this->structured['position'] = $this->position;
        }
        $fields['fields'] = ProposalTypes::validateFields($this->kind, array_replace(
            collect($this->structured)->only(ProposalTypes::fieldNames($this->kind))->all(),
            collect($additional)->only(ProposalTypes::fieldNames($this->kind))->all(),
        ));
        $structure = $this->validate(['completeness' => 'required|in:complete,partial,unsupported', 'missing' => 'nullable|string|max:15000']);
        $structure['missing'] = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $this->missing))));
        if ($this->completeness !== 'complete' && ! $structure['missing']) {
            ProposalTypes::invalid('Describe the remaining work for this incomplete proposal.');
        }
        if ($this->completeness === 'complete' && $structure['missing']) {
            ProposalTypes::invalid('Clear the missing-work list only when the proposal is complete.');
        }
        $fields['structure'] = $structure + ['version' => 3, 'deliverable' => 'records'];

        return $fields;
    }

    public function addStructureRow(string $collection): void
    {
        $allowed = ['beats', 'rules', 'cases', 'placements', 'subjects', 'knowers', 'pins', 'journey', 'months', 'weekdays', 'evidence'];
        if (! in_array($collection, $allowed, true) || ! in_array($collection, ProposalTypes::fieldNames($this->kind), true)) {
            ProposalTypes::invalid('Unsupported collection for this tool.');
        }
        if (count($this->structured[$collection] ?? []) >= 100) {
            ProposalTypes::invalid('Keep this editing batch to 100 rows.');
        }
        $row = match ($collection) {
            'beats' => ['title' => '', 'position' => (count($this->structured[$collection] ?? []) + 1) * 10, 'intensity' => null, 'notes_md' => ''],
            'rules' => ['title' => '', 'statement_md' => '', 'decision_id' => 1], 'cases' => ['title' => '', 'situation_md' => '', 'rule_ids' => [], 'rule_refs' => []],
            'placements' => ['name' => '', 'x' => 0, 'y' => 0], 'subjects' => $this->kind === 'comparison' ? ['key' => '', 'cells' => array_fill(0, count(array_filter(preg_split('/\r?\n/', $this->criteria))), null)] : ['key' => ''],
            'knowers' => ['key' => '', 'belief' => ''], 'pins' => ['key' => (string) Str::uuid(), 'name' => '', 'x' => 0, 'y' => 0],
            'journey','weekdays' => '', 'months' => ['name' => '', 'days' => 30], 'evidence' => ['text' => '', 'source' => '', 'supports' => true],
        };
        $this->structured[$collection][] = $row;
    }

    public function removeStructureRow(string $collection, int $index): void
    {
        if (! in_array($collection, ProposalTypes::fieldNames($this->kind), true) || ! isset($this->structured[$collection][$index])) {
            ProposalTypes::invalid('That structure row is not available.');
        }
        array_splice($this->structured[$collection], $index, 1);
        if (isset(ProposalTypes::definition($this->kind)['children'][$collection])) {
            $this->structured['children_mode'] = 'replace';
        }
    }
}
