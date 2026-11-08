<details wire:ignore.self class="sandbox-usage">
    <summary>{{ ($discussion ?? false) ? 'Discussion usage' : 'Connection usage' }} · {{ $totals['requests'] }} requests</summary>
    <dl class="sandbox-usage-totals">
        <dt>Reading requests</dt><dd>{{ $totals['reading_requests'] }}</dd>
        <dt>Failed or stopped</dt><dd>{{ $totals['failed_requests'] }}</dd>
        <dt>Input tokens</dt><dd>{{ $totals['input_reported'] ? number_format($totals['input_tokens']) : 'Unavailable' }} @if($totals['input_reported'])<span>({{ $totals['input_reported'] }}/{{ $totals['requests'] }} requests reported)</span>@endif</dd>
        <dt>Output tokens</dt><dd>{{ $totals['output_reported'] ? number_format($totals['output_tokens']) : 'Unavailable' }} @if($totals['output_reported'])<span>({{ $totals['output_reported'] }}/{{ $totals['requests'] }} requests reported)</span>@endif</dd>
        <dt>Reported cache reads</dt><dd>{{ $totals['cache_reported'] ? number_format($totals['cache_read_tokens']).' tokens' : 'Unavailable' }}</dd>
        <dt>Estimated token cost</dt><dd>{{ $totals['estimated_reported'] ? '$'.number_format($totals['estimated_cost_usd'], 6).' USD ('.$totals['estimated_reported'].'/'.$totals['requests'].' requests)' : 'Unavailable — rates or complete token counts missing' }}</dd>
        <dt>Provider-reported API cost</dt><dd>{{ $totals['cost_reported'] ? '$'.number_format($totals['cost_usd'], 6).' USD ('.$totals['cost_reported'].'/'.$totals['requests'].' requests)' : 'Unavailable — no cost reported' }}</dd>
    </dl>
    <p class="sandbox-help">Missing usage is not zero. Earlier requests made before tracking began are excluded. Cost estimates use your saved model rates, excluding cache price differences and other fees. Token counts follow each provider’s reporting; subscription tokens are not a bill.</p>
    @if($usageConnection)
        <p><strong>{{ $usageConnection->name }}</strong> · Daily budget {{ $usageConnection->daily_request_limit }} requests · {{ $usageConnection->turn_request_limit }} per saved question</p>
        @unless($discussion ?? false)<p>{{ $totals['daily_requests'] }} requests started today (UTC).</p>@endunless
        @if(($usageConnection->allowance['status'] ?? null) === 'reported')
            <p>Provider-reported key allowance: ${{ number_format($usageConnection->allowance['remaining_usd'], 2) }} USD remaining.</p>
            @if($usageConnection->allowance['reset'] ?? null)<p>Key reset rule: {{ $usageConnection->allowance['reset'] }}</p>@endif
        @else
            <p>Account allowance: {{ ($usageConnection->allowance['status'] ?? null) === 'failed' ? 'Check failed' : 'Unavailable here' }}. Check your provider for remaining usage and reset times.</p>
        @endif
        @if($usageConnection->allowance_checked_at)<p class="sandbox-help">Last checked {{ $usageConnection->allowance_checked_at->utc()->asDateTime() }} UTC. Usage elsewhere may have changed this balance.</p>@endif
        <div class="sandbox-usage-actions">
        <a href="{{ $usageConnection->usageUrl() }}" target="_blank" rel="noopener noreferrer">Open provider usage<span class="sr-only"> (new tab)</span></a>
        @unless($discussion ?? false)<flux:button size="sm" icon="arrow-path" wire:click="refreshAllowance({{ $usageConnection->id }})" wire:loading.attr="disabled">{{ $usageConnection->provider === 'openrouter' ? 'Check key allowance' : 'Check availability' }}</flux:button>@endunless
        </div>
    @endif
</details>
