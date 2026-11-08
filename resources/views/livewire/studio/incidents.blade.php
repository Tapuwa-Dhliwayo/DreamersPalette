<div>
    <x-studio.header active="incidents" />
    <main id="studio-main" class="assistant-settings">
        <header class="sandbox-heading"><div><h1>Incidents</h1></div></header>
        @if($notice)<p role="status" class="sandbox-notice">{{ $notice }}</p>@endif
        @unless($alertsConfigured)
            <flux:callout variant="warning" icon="exclamation-triangle" class="my-3">
                <flux:callout.text>Email alerts are not configured. Configure INCIDENT_ALERT_TO and a delivering INCIDENT_ALERT_MAILER.</flux:callout.text>
            </flux:callout>
        @endunless

        <section aria-label="Open incidents" class="mt-4">
            <h2 class="text-sm font-semibold mb-2">Open</h2>
            @forelse($open as $incident)
                @php
                    $state = $monitor->state($incident);
                    $diagnostics = $incident['pause_cause'] ?? $incident;
                @endphp
                <article wire:key="incident-{{ $incident['id'] }}" class="grid gap-x-6 gap-y-2 border-t border-[var(--studio-line)] py-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge size="sm" :color="$state === 'paused' ? 'red' : 'amber'">{{ $state === 'paused' ? 'Paused' : 'Watching' }}</flux:badge>
                            <span class="font-semibold">{{ $incident['feature'] }}</span>
                            <code class="text-xs">{{ $incident['id'] }}</code>
                        </div>
                        <p class="mt-1 break-words text-sm">{{ $diagnostics['exception'] }} · <code class="text-xs">{{ $diagnostics['location'] }}</code></p>
                        <p class="mt-1 text-xs opacity-80">
                            {{ $incident['count'] }} {{ \Illuminate\Support\Str::plural('occurrence', $incident['count']) }}
                            · first {{ \Carbon\CarbonImmutable::parse($incident['first_seen_at'])->asDateTime() }}
                            · last {{ \Carbon\CarbonImmutable::parse($incident['last_seen_at'])->asDateTime() }}
                            · email {{ $incident['notified_at'] ? 'delivered' : 'not delivered' }}
                        </p>
                    </div>
                    <div class="sandbox-actions">
                        @if($state === 'watching')<flux:button size="sm" icon="pause" wire:click="pause('{{ $incident['id'] }}')" wire:confirm="Pause this feature for all users now?">Pause now</flux:button>@endif
                        <flux:button size="sm" variant="primary" icon="check" wire:click="resolve('{{ $incident['id'] }}')" wire:confirm="Resume this feature for all users? Only do this after the fix is released.">Resolve</flux:button>
                    </div>
                </article>
            @empty
                <p class="text-sm">No open incidents.</p>
            @endforelse
        </section>

        @if($resolved->isNotEmpty())
            <details class="mt-6">
                <summary class="cursor-pointer text-sm font-semibold">Recently resolved ({{ $resolved->count() }})</summary>
                @foreach($resolved as $incident)
                    <article wire:key="resolved-{{ $incident['id'] }}" class="border-t border-[var(--studio-line)] py-2 text-sm">
                        <div class="flex flex-wrap items-center gap-2"><flux:badge size="sm" color="zinc">Resolved</flux:badge><span class="font-semibold">{{ $incident['feature'] }}</span><code class="text-xs">{{ $incident['id'] }}</code></div>
                        <p class="mt-1 break-words text-xs opacity-80">{{ $incident['exception'] }} · {{ $incident['location'] }} · {{ $incident['count'] }} {{ \Illuminate\Support\Str::plural('occurrence', $incident['count']) }} · resolved {{ \Carbon\CarbonImmutable::parse($incident['resolved_at'])->asDateTime() }}</p>
                    </article>
                @endforeach
            </details>
        @endif
    </main>
</div>
