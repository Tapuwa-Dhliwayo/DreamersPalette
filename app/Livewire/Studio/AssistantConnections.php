<?php

namespace App\Livewire\Studio;

use App\Livewire\Forms\Studio\AssistantConnectionForm;
use App\Models\AssistantConnection;
use App\Models\AssistantTurn;
use App\Services\Worldbuilding\AssistantProviders;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.studio')]
class AssistantConnections extends Component
{
    public AssistantConnectionForm $form;

    #[Locked]
    public ?int $editingId = null;

    public string $notice = '';

    public array $statuses = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('worlds.update'), 403);
    }

    public function edit(?int $id = null): void
    {
        $this->mount();
        $this->form->reset();
        $this->editingId = $id;
        if ($id) {
            $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($id);
            $this->form->fill($connection->only(['name', 'provider', 'model', 'enabled', 'revision']));
        }
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->mount();
        $fields = $this->form->fields();
        if (in_array($fields['provider'], ['codex', 'claude'], true) && (! config('worldbuilding.local_connections') || auth()->id() !== (int) config('worldbuilding.local_user_id'))) {
            throw ValidationException::withMessages(['form.provider' => 'Local subscription connections are only enabled for the configured local author.']);
        }
        if (! in_array($fields['provider'], ['codex', 'claude'], true) && $fields['model'] === 'default') {
            throw ValidationException::withMessages(['form.model' => 'Enter the exact model ID for this API provider.']);
        }
        DB::transaction(function () use ($fields): void {
            $connection = $this->editingId ? AssistantConnection::where('user_id', auth()->id())->lockForUpdate()->findOrFail($this->editingId) : new AssistantConnection;
            if ($connection->exists) {
                $this->authorize('update', $connection);
                if ($connection->revision !== $this->form->revision) {
                    throw ValidationException::withMessages(['form.name' => 'This connection changed. Reopen it before saving.']);
                }
                if (AssistantTurn::where('connection_id', $connection->id)->whereIn('status', ['queued', 'running'])->exists()) {
                    throw ValidationException::withMessages(['form.name' => 'Stop its active responses before changing this connection.']);
                }
                if ($connection->provider !== $fields['provider']) {
                    throw ValidationException::withMessages(['form.provider' => 'Create a separate connection when changing providers.']);
                }
            }
            $connection->user_id = auth()->id();
            if ($fields['credential'] === '') {
                unset($fields['credential']);
            }
            $connection->fill($fields);
            if (! $connection->isLocal() && ! $connection->credential) {
                throw ValidationException::withMessages(['form.credential' => 'Enter an API key for this connection.']);
            }
            $connection->revision = ($connection->revision ?? 0) + 1;
            $connection->save();
        });
        $this->edit();
        $this->notice = 'Connection saved. Subscription and API billing remain separate.';
    }

    public function check(int $id, AssistantProviders $providers): void
    {
        $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $connection);
        $this->statuses[$id] = $providers->status($connection);
    }

    public function disconnect(int $id): void
    {
        $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $connection);
        $connection->update(['enabled' => false, 'credential' => null, 'revision' => $connection->revision + 1]);
        AssistantTurn::where('connection_id', $id)->whereIn('status', ['queued', 'running'])->update(['status' => 'cancelled', 'finished_at' => now()]);
        $this->notice = 'Disconnected. Conversation history is retained. Local provider login is unchanged.';
    }

    public function render()
    {
        $this->mount();

        return view('livewire.studio.assistant-connections', ['connections' => AssistantConnection::where('user_id', auth()->id())->get(), 'providers' => AssistantProviders::PROVIDERS]);
    }
}
