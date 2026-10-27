@props(['world', 'subjectKey', 'name', 'catalogue'])
@php
    $subject = $catalogue->get($subjectKey);
    [$kind, $subjectId] = explode(':', $subjectKey);
    $url = $kind === 'system' ? route('studio.world', ['world' => $world, 'system' => $subjectId]) : route('studio.world.develop', ['world' => $world, 'section' => $kind === 'person' ? 'people' : 'entries', 'selected' => $subjectId]);
@endphp
@if($subject)
    <a href="{{ $url }}" class="thinking-subject">{{ $subject['name'] }}<small>{{ $subject['type'] }}</small></a>
@else
    <span class="thinking-subject">{{ $name }}<small>Archived</small></span>
@endif
