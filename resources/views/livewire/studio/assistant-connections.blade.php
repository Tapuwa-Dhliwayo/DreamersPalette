<div class="assistant-settings">
    <header class="sandbox-heading"><div><h1>Assistant connections</h1><p>Choose which account powers your worldbuilding conversations.</p></div><flux:button :href="route('studio.worlds')" icon="globe-alt">Worlds</flux:button></header>
    @if($notice)<p role="status" class="sandbox-notice">{{ $notice }}</p>@endif
    <div class="assistant-settings-columns">
        <section aria-label="Saved connections">
            @forelse($connections as $connection)
                <article class="assistant-connection" wire:key="connection-{{ $connection->id }}"><h2>{{ $connection->name }}</h2><p>{{ $providers[$connection->provider] }} · {{ $connection->model }}</p><p role="status">{{ $statuses[$connection->id] ?? ($connection->enabled ? 'Enabled · not checked yet' : 'Disconnected') }}</p><div class="sandbox-actions"><flux:button size="sm" icon="pencil-square" wire:click="edit({{ $connection->id }})">Edit</flux:button><flux:button size="sm" icon="signal" wire:click="check({{ $connection->id }})">Check login</flux:button>@if($connection->enabled)<flux:button size="sm" icon="link-slash" wire:click="disconnect({{ $connection->id }})" wire:confirm="Disconnect this connection and stop its active responses?">Disconnect</flux:button>@endif</div></article>
            @empty<p>No connections yet. Add your local subscription login or an API connection.</p>@endforelse
            <p class="sandbox-help">Local subscriptions use the account already signed in on this computer. Initial setup: <code>codex login</code> or <code>claude auth login</code>. Check login does not generate a response or charge an API request.</p>
        </section>
        <form wire:submit="save" class="sandbox-form" autocomplete="off"><h2>{{ $editingId ? 'Edit connection' : 'Add connection' }}</h2>
            <flux:input label="Connection name" wire:model="form.name" placeholder="My ChatGPT" />
            <flux:select label="Provider and billing" wire:model.live="form.provider">@foreach($providers as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</flux:select>
            <flux:input label="Model" wire:model="form.model" />
            <p class="sandbox-help">Use <code>default</code> for the local provider’s default, or enter a model ID. API connections require an exact model ID.</p>
            @if(!in_array($form->provider, ['codex','claude']))<flux:input type="password" label="API key" wire:model="form.credential" autocomplete="new-password" /><p class="sandbox-help">API billing is separate. An empty key preserves the saved key when editing.</p>@endif
            <flux:checkbox label="Enabled" wire:model="form.enabled" />
            <div class="sandbox-actions"><flux:button type="submit" variant="primary" icon="check">Save connection</flux:button>@if($editingId)<flux:button wire:click="edit" icon="plus">New connection</flux:button>@endif</div>
        </form>
    </div>
</div>
