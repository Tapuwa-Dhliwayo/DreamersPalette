<div>
    <x-studio.header active="worlds" />
    <main id="studio-main" class="assistant-settings">
    <header class="sandbox-heading"><div><h1>Assistant connections</h1><p>Choose which account powers your worldbuilding conversations.</p></div><flux:button :href="route('studio.worlds')" icon="globe-alt">Worlds</flux:button></header>
    @if($notice)<p role="status" class="sandbox-notice">{{ $notice }}</p>@endif
    <div class="assistant-settings-columns">
        <section aria-label="Saved connections">
            @forelse($connections as $connection)
                <article class="assistant-connection" wire:key="connection-{{ $connection->id }}"><h2>{{ $connection->name }}</h2><p>{{ \App\Services\Worldbuilding\AssistantProviders::PROVIDERS[$connection->provider] }} · {{ $connection->model }}</p><p role="status">{{ $statuses[$connection->id] ?? ($connection->enabled ? 'Enabled · not checked yet' : 'Disconnected') }}</p>@include('livewire.studio.assistant-usage', ['totals' => $usage[$connection->id], 'usageConnection' => $connection])
                    <div class="sandbox-actions"><flux:button size="sm" icon="pencil-square" wire:click="edit({{ $connection->id }})">Edit</flux:button><flux:button size="sm" icon="signal" wire:click="check({{ $connection->id }})">Check connection</flux:button>@if($connection->enabled)<flux:button size="sm" icon="link-slash" wire:click="disconnect({{ $connection->id }})" wire:confirm="Disconnect this connection and stop its active responses?">Disconnect</flux:button>@endif</div></article>
            @empty<p>No connections yet. Add your own provider API connection. API usage is billed by your provider.</p>@endforelse
            <p class="sandbox-help">Connections and their usage are private to your account. API billing is separate from ChatGPT and Claude subscriptions. Hosted subscription sign-in is not available.</p>
            @if(\App\Services\Worldbuilding\AssistantProviders::allowsLocal(auth()->id()))<p class="sandbox-help">Local subscription connections use this computer’s existing login and are available only to its configured author. They are never shared with hosted users.</p>@endif
        </section>
        <form wire:submit="save" class="sandbox-form" autocomplete="off"><h2>{{ $editingId ? 'Edit connection' : 'Add connection' }}</h2>
            <flux:input label="Connection name" wire:model="form.name" placeholder="My ChatGPT" />
            <flux:select label="Provider and billing" wire:model.live="form.provider" :disabled="(bool) $editingId">@foreach($providers as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</flux:select>
            @if(!in_array($form->provider, ['codex','claude']))<flux:input type="password" label="API key" wire:model.blur="form.credential" autocomplete="new-password" /><p class="sandbox-help">API billing is separate. Leave blank to keep the saved key.</p>@endif
            <div wire:key="connection-model-{{ $editingId ?? 'new' }}-{{ $form->provider }}-{{ $modelCatalogue['checked_at'] ?? $modelCatalogue['status'] }}"><flux:select variant="listbox" searchable options:class="assistant-model-options" label="Model" wire:model="form.model" placeholder="Choose a model" :disabled="empty($modelCatalogue['models']) && !$form->model" wire:loading.attr="disabled" wire:target="loadModels,edit,form.provider,form.credential">
                <x-slot name="search"><flux:select.search placeholder="Search models" aria-label="Search models" /></x-slot>
                @if($form->model && !collect($modelCatalogue['models'])->contains('id', $form->model))<flux:select.option :value="$form->model">{{ $form->model }} · saved selection, not refreshed</flux:select.option>@endif
                @foreach($modelCatalogue['models'] as $model)<flux:select.option :value="$model['id']">{{ $model['name'] }}</flux:select.option>@endforeach
            </flux:select></div>
            <p class="sandbox-help" role="status" wire:loading.remove wire:target="loadModels,edit,form.provider,form.credential">{{ $modelCatalogue['message'] }}</p>
            <p class="sandbox-help" role="status" wire:loading wire:target="loadModels,edit,form.provider,form.credential">Loading models…</p>
            <flux:button size="sm" icon="arrow-path" wire:click="loadModels(true)" wire:loading.attr="disabled" wire:target="loadModels,edit,form.provider,form.credential">Refresh models</flux:button>
            <flux:input type="number" min="1" max="10000" label="Daily request budget" wire:model="form.daily_request_limit" />
            <flux:input type="number" min="1" max="1000" label="Requests per saved question" wire:model="form.turn_request_limit" />
            <p class="sandbox-help">Reading and final answers each use requests. The daily budget resets at 00:00 UTC. The question budget includes retries; raising it allows saved reading to continue. These limits apply inside DreamersPalette, not to other apps using your account.</p>
            @if(!in_array($form->provider, ['codex','claude']))
                <details><summary>Optional cost estimates</summary><flux:input type="number" min="0" step="0.000001" label="Input price per million tokens (USD)" wire:model="form.input_rate_usd" /><flux:input type="number" min="0" step="0.000001" label="Output price per million tokens (USD)" wire:model="form.output_rate_usd" /><p class="sandbox-help">Enter your provider’s rates for the exact connection model. Estimates use these rates for all reported tokens, excluding cache discounts, cache-write premiums and other fees. Model overrides need their own connection for estimates. Leave blank if unknown.</p></details>
            @endif
            <label class="sandbox-inline-check"><input type="checkbox" wire:model="form.enabled"> Enabled</label>
            <div class="sandbox-actions"><flux:button type="submit" variant="primary" icon="check">Save connection</flux:button>@if($editingId)<flux:button wire:click="edit" icon="plus">New connection</flux:button>@endif</div>
        </form>
    </div>
    </main>
</div>
