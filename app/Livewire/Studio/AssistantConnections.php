<?php

namespace App\Livewire\Studio;

use App\Livewire\Forms\Studio\AssistantConnectionForm;
use App\Models\AssistantConnection;
use App\Models\AssistantTurn;
use App\Services\Worldbuilding\AssistantModels;
use App\Services\Worldbuilding\AssistantProviders;
use App\Services\Worldbuilding\AssistantUsage;
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

    #[Locked]
    public array $modelCatalogue = [
        'models' => [],
        'status' => 'needs_key',
        'message' => 'Add your API key to load this account’s models.',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAny(['worlds.update', 'poems.update']), 403);
    }

    public function edit(?int $id = null): void
    {
        $this->mount();
        $this->form->reset();
        $this->editingId = $id;
        if ($id) {
            $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($id);
            $this->form->fill($connection->only([
                'name',
                'provider',
                'model',
                'enabled',
                'revision',
                'daily_request_limit',
                'turn_request_limit',
                'input_rate_usd',
                'output_rate_usd',
            ]));
        }
        $this->resetValidation();
        $this->loadModels();
    }

    public function updatedFormProvider(): void
    {
        $this->form->model = '';
        $this->form->credential = '';
        $this->modelCatalogue = ['models' => [], 'status' => 'loading', 'message' => 'Loading models…'];
        $this->loadModels();
    }

    public function updatedFormCredential(): void
    {
        $this->loadModels();
    }

    private function modelConnection(): AssistantConnection
    {
        $this->mount();
        $this->form->discoveryFields();
        $connection = $this->editingId ? AssistantConnection::where('user_id', auth()->id())->findOrFail($this->editingId) : new AssistantConnection;
        if ($connection->exists && $connection->provider !== $this->form->provider) {
            throw ValidationException::withMessages(['form.provider' => 'Create a new connection to change provider.']);
        }
        $connection->user_id = auth()->id();
        $connection->enabled = true;
        $connection->provider = $this->form->provider;
        if ($this->form->credential !== '') {
            $connection->credential = $this->form->credential;
        }

        return $connection;
    }

    public function loadModels(bool $refresh = false): void
    {
        $this->modelCatalogue = app(AssistantModels::class)->catalogue($this->modelConnection(), $refresh);
    }

    public function save(): void
    {
        $this->mount();
        $fields = $this->form->fields();
        app(AssistantModels::class)->assertSelection($this->modelConnection(), $fields['model'], field: 'form.model');
        if (in_array($fields['provider'], ['codex', 'claude'], true) && ! AssistantProviders::allowsLocal(auth()->id())) {
            throw ValidationException::withMessages(['form.provider' => 'Local subscription connections are only enabled for the configured local author.']);
        }
        if (! in_array($fields['provider'], ['codex', 'claude'], true) && $fields['model'] === 'default') {
            throw ValidationException::withMessages(['form.model' => 'Choose a model from this API connection’s list.']);
        }
        DB::transaction(function () use ($fields): void {
            $connection = $this->editingId ? AssistantConnection::where('user_id', auth()->id())->lockForUpdate()->findOrFail($this->editingId) : new AssistantConnection;
            if ($connection->exists) {
                $this->authorize('update', $connection);
                if ($connection->revision !== $this->form->revision) {
                    throw ValidationException::withMessages(['form.name' => 'This connection changed. Reopen it before saving.']);
                }
                if (AssistantTurn::where('connection_id', $connection->id)->active()->exists()) {
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
            $connection->forceFill(['allowance' => null, 'allowance_checked_at' => null]);
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
        AssistantTurn::where('connection_id', $id)->active()->update(['status' => 'cancelled', 'finished_at' => now()]);
        $this->notice = 'Disconnected. Conversation history is retained. Local provider login is unchanged.';
    }

    public function refreshAllowance(int $id, AssistantProviders $providers): void
    {
        $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $connection);
        try {
            $providers->refreshAllowance($connection);
            $this->notice = $connection->provider === 'openrouter'
                ? 'Allowance check completed.'
                : 'This connection does not expose account allowance here. Use its provider usage page.';
        } catch (\Throwable) {
            $connection->forceFill(['allowance' => ['status' => 'failed'], 'allowance_checked_at' => now()])->save();
            $this->notice = 'Account allowance could not be checked. Use the provider usage page.';
        }
    }

    public function render()
    {
        $this->mount();

        $connections = AssistantConnection::where('user_id', auth()->id())->get();
        $usage = $connections->mapWithKeys(fn ($connection) => [
            $connection->id => app(AssistantUsage::class)->summary(auth()->id(), connectionId: $connection->id),
        ])->all();

        return view('livewire.studio.assistant-connections', [
            'connections' => $connections,
            'usage' => $usage,
            'providers' => app(AssistantProviders::class)->availableProviders(auth()->id()),
        ]);
    }
}
