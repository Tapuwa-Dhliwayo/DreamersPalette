@props(['text' => ''])
<div {{ $attributes->class(['studio-prose world-prose']) }}>{!! Str::markdown($text ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
