<div>
    <x-studio.header active="account" />
    <main id="studio-main" class="manuscript-library account-settings">
        <h1 class="studio-page-title">Account</h1>
        <x-studio.account-nav current="connections" />
        @if(!$enabled)
            <div class="ext-notice"><flux:callout icon="information-circle">External access is not enabled on this site.</flux:callout></div>
        @else
            @if($notice)<div class="ext-notice" wire:dirty.remove.flex><flux:callout variant="success" icon="check-circle" role="status">{{ $notice }}</flux:callout></div>@endif
            <section class="ext-section" aria-labelledby="ext-connect">
                <flux:heading size="lg" id="ext-connect">Connect a client</flux:heading>
                <div class="ext-url" x-data="{ copied: false }">
                    <input type="text" readonly x-ref="address" value="{{ url('/mcp/account') }}" aria-label="Connection URL" class="ext-url-field" @focus="$el.select()">
                    <flux:button size="sm" icon="clipboard" x-on:click="navigator.clipboard.writeText($refs.address.value); copied = true; setTimeout(() => copied = false, 1600)"><span x-text="copied ? 'Copied' : 'Copy'">Copy</span></flux:button>
                </div>
                <flux:text size="sm">Hosted clients reach only the public HTTPS site, not a local address. Clients sign in with your account and can read only what you select below.</flux:text>
                <div class="ext-howto">
                    <details><summary>Claude</summary><p>Settings, Connectors, Add custom connector. Paste the URL, then sign in when asked.</p></details>
                    <details><summary>ChatGPT</summary><p>Settings, Connectors (or Apps), turn on developer mode, create a custom connector with this URL and OAuth, then sign in.</p></details>
                    <details><summary>Codex and Claude Code</summary><p>Run in a terminal, then complete the sign-in in the browser:</p><code>codex mcp add dreamerspalette --url {{ url('/mcp/account') }}</code><code>claude mcp add --transport http dreamerspalette {{ url('/mcp/account') }}</code></details>
                </div>
            </section>

            <form wire:submit="save" class="ext-section" aria-labelledby="ext-read">
                <flux:heading size="lg" id="ext-read">What connected clients can read</flux:heading>
                <flux:text size="sm">Nothing is readable until you select it. This applies to every connected client and changes take effect on their next request.</flux:text>
                @php($groups = [
                    ['world', 'Worlds', 'worlds', 'worldWorkspace', 'Include sandbox exercises'],
                    ['manuscript', 'Manuscripts', 'manuscripts', null, null],
                    ['collection', 'Poetry collections', 'collections', 'collectionWorkspace', 'Include private analyses and notes'],
                    ['poem', 'Uncollected poems', 'poems', null, null],
                ])
                @foreach($groups as [$type, $label, $field, $workspaceField, $workspaceLabel])
                    <fieldset class="ext-group" wire:key="group-{{ $type }}">
                        <legend>{{ $label }}</legend>
                        @forelse($selectable[$type] as $id => $name)
                            <div class="ext-row" wire:key="{{ $type }}-{{ $id }}">
                                <label class="ext-pick"><input type="checkbox" wire:model="form.{{ $field }}" value="{{ $id }}"> <span>{{ $name }}</span></label>
                                @if($workspaceField)<label class="ext-extra"><input type="checkbox" wire:model="form.{{ $workspaceField }}" value="{{ $id }}" x-bind:disabled="!$wire.form.{{ $field }}.includes('{{ $id }}')"> <span>{{ $workspaceLabel }}</span></label>@endif
                            </div>
                        @empty
                            <p class="ext-none">None yet.</p>
                        @endforelse
                    </fieldset>
                @endforeach
                @error('form.*')<flux:text class="text-red-600">{{ $message }}</flux:text>@enderror
                <div class="studio-form-actions">
                    <flux:text wire:dirty wire:loading.remove wire:target="save">Unsaved changes</flux:text>
                    <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">Save access</flux:button>
                </div>
            </form>

            <section class="ext-section" aria-labelledby="ext-clients">
                <flux:heading size="lg" id="ext-clients">Connected clients</flux:heading>
                @forelse($clients as $token)
                    <div class="ext-client" wire:key="client-{{ $token->client_id }}">
                        <strong>{{ $token->client?->name ?? 'Unknown client' }}</strong>
                        <span>Connected {{ $token->created_at->asDate() }}</span>
                        <span>Access expires {{ $token->expires_at->asDate() }}</span>
                        <flux:button size="sm" icon="link-slash" wire:click="revoke('{{ $token->client_id }}')" wire:confirm="Disconnect this client? It loses access on its next request.">Revoke</flux:button>
                    </div>
                @empty
                    <p class="ext-none">No clients connected.</p>
                @endforelse
            </section>
        @endif
    </main>
</div>
