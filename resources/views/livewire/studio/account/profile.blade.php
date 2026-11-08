<div>
    <x-studio.header active="account" />
    <main id="studio-main" class="manuscript-library account-settings">
        <h1 class="studio-page-title">Account</h1>
        <x-studio.account-nav current="profile" />
        <form wire:submit="save" class="studio-form">
            <flux:heading size="lg">Profile</flux:heading>
            @if($notice)<flux:callout variant="success" icon="check-circle" role="status" wire:dirty.remove>{{ $notice }}</flux:callout>@endif
            <div class="flex items-center gap-4">
                @if($form->avatar && !$errors->has('form.avatar'))
                    <flux:avatar :src="$form->avatar->temporaryUrl()" size="xl" />
                @elseif($profile?->avatar_url && !$form->remove_avatar)
                    <flux:avatar :src="$profile->avatar_url" size="xl" />
                @else
                    <flux:avatar :name="$form->display_name ?: auth()->user()->name" size="xl" />
                @endif
                <flux:input type="file" wire:model="form.avatar" label="Profile image" accept="image/jpeg,image/png,image/webp" description="JPG, PNG or WebP. Maximum 2 MB." />
            </div>
            @if($profile?->avatar_url)<flux:checkbox wire:model.live="form.remove_avatar" label="Remove current profile image" />@endif
            <flux:input wire:model="form.display_name" label="Display name" autocomplete="nickname" maxlength="100" required />
            <flux:textarea wire:model="form.bio" label="Bio" rows="6" maxlength="5000" />
            {{-- Shown, never an input: the sign-in email cannot be changed from the account page. --}}
            <flux:field>
                <flux:label>Sign-in email</flux:label>
                <flux:text class="text-neutral-900">{{ auth()->user()->email }}</flux:text>
                <flux:description>Your sign-in email can’t be changed.</flux:description>
            </flux:field>
            <div class="studio-form-actions">
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save,form.avatar">Save profile</flux:button>
                <flux:text wire:dirty wire:loading.remove wire:target="save">Unsaved changes</flux:text>
                <flux:text wire:loading wire:target="form.avatar">Uploading image…</flux:text>
            </div>
        </form>
    </main>
</div>
