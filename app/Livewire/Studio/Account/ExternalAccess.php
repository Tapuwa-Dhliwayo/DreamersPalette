<?php

namespace App\Livewire\Studio\Account;

use App\Livewire\Forms\Account\ExternalAccessForm;
use App\Services\Exchange\AccountAccess;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.studio')]
#[Title('External access')]
class ExternalAccess extends Component
{
    public ExternalAccessForm $form;

    public string $notice = '';

    public function mount(): void
    {
        $this->authorize('account.external-access');
        $this->form->load(auth()->user());
    }

    public function save(AccountAccess $access): void
    {
        $this->authorize('account.external-access');
        $user = auth()->user();
        $user->replaceExternalGrants($this->form->grants($access->selectable($user)));
        $this->form->load($user);
        $this->notice = 'Access saved. Connected clients see this from their next request.';
    }

    public function revoke(string $clientId): void
    {
        $this->authorize('account.external-access');
        auth()->user()->revokeConnectedClient($clientId);
        $this->notice = 'Client disconnected.';
    }

    public function render(AccountAccess $access)
    {
        $this->authorize('account.external-access');
        $user = auth()->user();

        return view('livewire.studio.account.external-access', [
            'enabled' => config('studio.account_mcp_enabled'),
            'selectable' => $access->selectable($user),
            'clients' => config('studio.account_mcp_enabled') ? $user->connectedClients() : collect(),
        ]);
    }
}
