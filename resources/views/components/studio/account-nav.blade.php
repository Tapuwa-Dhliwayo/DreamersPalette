@props(['current'])

<flux:navbar aria-label="Account settings" class="mb-6 border-b border-neutral-200">
    @can('account.view')<flux:navbar.item :href="route('account.profile')" :current="$current === 'profile'" icon="user-circle">Profile</flux:navbar.item>@endcan
    @can('account.security.manage')<flux:navbar.item :href="route('account.security')" :current="$current === 'security'" icon="shield-check">Security</flux:navbar.item>@endcan
</flux:navbar>
