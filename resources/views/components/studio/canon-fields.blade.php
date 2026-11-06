@props(['fields', 'entryTypes', 'catalogue'])
@php
    $labels = ['notes_md' => 'Notes', 'truth_md' => 'Established truth', 'question_md' => 'Question', 'type_id' => 'Entry type', 'source_key' => 'From', 'target_key' => 'To', 'is_dependency' => 'Dependency'];
@endphp
<dl>
    @foreach($fields as $key => $content)
        @php
            $display = match ($key) {
                'type_id' => $entryTypes->firstWhere('id', $content)?->name ?? 'Not selected',
                'source_key', 'target_key' => $catalogue[$content]['name'] ?? $content,
                'is_dependency' => $content ? 'Yes' : 'No',
                default => is_array($content) ? implode(', ', array_map(fn ($part) => is_scalar($part) ? (string) $part : json_encode($part, JSON_UNESCAPED_UNICODE), $content)) : $content,
            };
        @endphp
        <dt>{{ $labels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</dt>
        <dd>@if(in_array($key, ['notes_md', 'truth_md', 'question_md'], true))<x-studio.markdown :text="$display ?? ''" />@else{{ $display }}@endif</dd>
    @endforeach
</dl>
