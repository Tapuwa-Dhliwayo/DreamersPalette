<form wire:submit="saveExercise" class="sandbox-form" @input="dirty=true">
    <flux:input label="Exercise title" wire:model="exerciseForm.title" placeholder="A question, a scene, a possibility" />
    <flux:textarea label="What are we exploring?" wire:model="exerciseForm.purpose" rows="3" />
    <flux:textarea label="Instructions for this exercise" wire:model="exerciseForm.instructions" rows="3" />
    <details><summary>Retained summary</summary><flux:textarea label="Your working summary" wire:model="exerciseForm.summary" rows="5" /><p class="sandbox-help">The original messages remain in context. This summary does not replace them.</p></details>
    <fieldset><legend>Established world context</legend><p class="sandbox-help">Choose the records to send. Sandbox ideas, connections and this conversation are included automatically.</p><div class="sandbox-context-choices">@forelse($catalogue as $record)<label><input type="checkbox" wire:model="exerciseForm.context_keys" value="{{ $record['key'] }}"> {{ $record['name'] }} <small>{{ $record['kind'] }}</small></label>@empty<p>No established world records available.</p>@endforelse</div></fieldset>
    @if($chapters->isNotEmpty())<fieldset><legend>Include chapter text</legend><p class="sandbox-help">Selected chapters are sent in full to the chosen provider.</p><div class="sandbox-context-choices">@foreach($chapters as $chapter)<label><input type="checkbox" wire:model="exerciseForm.passages" value="{{ $chapter->id }}"> {{ $chapter->title }}</label>@endforeach</div></fieldset>@endif
    <flux:button type="submit" variant="primary" icon="check">{{ $exercise ? 'Save exercise & context' : 'Create exercise' }}</flux:button>
    @if($exercise)<flux:button icon="archive-box" wire:click="archiveExercise({{ $exercise->id }}, {{ $exercise->revision }}, true)" wire:confirm="Archive this exercise and stop its active response? Its ideas and conversation remain recoverable.">Archive exercise</flux:button>@endif
</form>
