<div>
    <x-studio.header active="write">
        <x-slot:context><a href="{{ route('studio.manuscript', $manuscript) }}">← {{ $manuscript->title }}</a></x-slot:context>
    </x-studio.header>
    <main id="studio-main" class="manuscript-library"><h1 class="studio-page-title">Manuscript details</h1>
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
