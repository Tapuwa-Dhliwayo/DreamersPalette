<?php

namespace App\Livewire\Forms\Account;

use App\Models\User;
use Livewire\Form;

class ExternalAccessForm extends Form
{
    public array $worlds = [];

    public array $worldWorkspace = [];

    public array $manuscripts = [];

    public array $collections = [];

    public array $collectionWorkspace = [];

    public array $poems = [];

    public function rules(): array
    {
        $ids = ['array', 'max:5000'];

        return array_fill_keys(['worlds', 'worldWorkspace', 'manuscripts', 'collections', 'collectionWorkspace', 'poems'], $ids)
            + array_fill_keys(['worlds.*', 'worldWorkspace.*', 'manuscripts.*', 'collections.*', 'collectionWorkspace.*', 'poems.*'], ['integer', 'min:1']);
    }

    public function load(User $user): void
    {
        $grants = $user->externalGrants()->get()->groupBy('resource_type');
        $ids = fn (string $type, bool $workspaceOnly = false): array => ($grants[$type] ?? collect())
            ->when($workspaceOnly, fn ($rows) => $rows->where('includes_workspace', true))
            ->pluck('resource_id')->map(fn ($id) => (string) $id)->values()->all();
        $this->worlds = $ids('world');
        $this->worldWorkspace = $ids('world', true);
        $this->manuscripts = $ids('manuscript');
        $this->collections = $ids('collection');
        $this->collectionWorkspace = $ids('collection', true);
        $this->poems = $ids('poem');
    }

    /**
     * Grant rows for the selection. `$selectable` lists what this author may actually share, so a
     * tampered request naming someone else's record is refused rather than silently stored.
     */
    public function grants(array $selectable): array
    {
        $this->validate();
        $rows = [];
        foreach (['world' => ['worlds', 'worldWorkspace'], 'manuscript' => ['manuscripts', null], 'collection' => ['collections', 'collectionWorkspace'], 'poem' => ['poems', null]] as $type => [$selected, $workspace]) {
            foreach (array_unique(array_map('intval', $this->{$selected})) as $id) {
                abort_unless(isset($selectable[$type][$id]), 403);
                $rows[] = [
                    'resource_type' => $type,
                    'resource_id' => $id,
                    'includes_workspace' => $workspace !== null && in_array($id, array_map('intval', $this->{$workspace}), true),
                ];
            }
        }

        return $rows;
    }
}
