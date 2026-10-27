<div>
    <header class="studio-header"><a class="studio-brand" href="{{ route('studio.index') }}">Dreamer’s Palette</a><a href="{{ route('studio.manuscript', $manuscript) }}">Back to writing</a></header>
    <main id="studio-main" class="world-document development-document"><h1>Review access</h1><p role="status">{{ $notice }}</p>
        <form wire:submit="grant" class="studio-form manuscript-details-form"><flux:input label="Editor’s account email" type="email" wire:model="form.email" /><flux:select label="Finished draft" wire:model="form.draft_id"><option value="">Choose a draft</option>@foreach($drafts as $draft)<option value="{{ $draft->id }}">{{ $draft->name }}</option>
        @endforeach</flux:select><flux:input type="number" label="Days of access" wire:model="form.days" min="1" max="90" /><flux:checkbox label="Allow proposed revisions as well as comments" wire:model="form.can_suggest" /><p>Only this locked draft is shared. Editors cannot change working writing or browse your live world. Nothing is sent automatically.</p><div class="studio-form-actions"><flux:button type="submit" variant="primary" icon="user-plus">Grant review access</flux:button></div></form>
        <section class="compile-recent"><h2>Reviewers</h2>@forelse($grants as $grant)<div class="compile-export-row"><div><strong>{{ $grant->reviewer->name }} · {{ $grant->draft->name }}</strong><p>{{ $grant->revoked_at ? 'Revoked' : ($grant->expires_at->isPast() ? 'Expired' : 'Until '.$grant->expires_at->asDateTime()) }} · {{ $grant->can_suggest ? 'Comments & suggestions' : 'Comments' }}</p></div><div class="thinking-actions"><flux:button size="sm" icon="document-magnifying-glass" href="{{ route('studio.review', $grant) }}">Read review</flux:button>@if(!$grant->revoked_at)<flux:button size="sm" icon="no-symbol" wire:click="revoke({{ $grant->id }})">Revoke</flux:button>
        @endif</div></div>
        @empty<p>No review access granted.</p>
        @endforelse</section>
    </main>
</div>
