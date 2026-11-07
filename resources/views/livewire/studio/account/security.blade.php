<div>
    <x-studio.header active="account" />
    <main id="studio-main" class="manuscript-library account-settings">
        <h1 class="studio-page-title">Account</h1>
        <x-studio.account-nav current="security" />
        <flux:heading size="lg" class="mb-4">Security</flux:heading>
        <div id="account-security" wire:ignore><p role="status" class="text-sm">Loading security settings…</p></div>
    </main>
    @viteReactRefresh
    @vite('resources/js/account-security.jsx')
</div>
