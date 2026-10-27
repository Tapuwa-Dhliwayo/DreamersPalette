<div>
    <header class="studio-header"><a class="studio-brand" href="{{ route('studio.index') }}">Dreamer’s Palette</a><a href="{{ route('studio.manuscript', $manuscript) }}">Back to writing</a></header>
    <main id="studio-main" class="manuscript-library"><h1>Manuscript details</h1>
        <form wire:submit="save" class="studio-form manuscript-details-form">
            <p role="status">{{ $notice }}</p>
            @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>
            @enderror
            <flux:input label="Title" wire:model="form.title" />
            <flux:textarea label="Description" wire:model="form.description" rows="6" />
            <div class="studio-form-actions"><flux:button href="{{ route('studio.manuscript', $manuscript) }}">Cancel</flux:button><flux:button type="submit" variant="primary" icon="check">Save details</flux:button></div>
        </form>
    </main>
</div>
