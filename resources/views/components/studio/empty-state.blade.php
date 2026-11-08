@props(['icon', 'title', 'as' => 'h2'])
<div {{ $attributes->class('studio-empty-state') }}>
    <flux:icon :name="$icon" />
    <{{ $as }}>{{ $title }}</{{ $as }}>
    <p>{{ $slot }}</p>
    @if(isset($action) && $action->hasActualContent())<div class="studio-empty-state-action">{{ $action }}</div>@endif
</div>
