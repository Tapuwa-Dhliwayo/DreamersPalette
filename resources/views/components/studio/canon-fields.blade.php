@props(['fields', 'entryTypes', 'catalogue', 'proposalNames'=>[], 'context'=>[]])
@php
    $labels = ['notes_md' => 'Notes', 'truth_md' => 'Established truth', 'question_md' => 'Question', 'type_id' => 'Entry type', 'source_key' => 'From', 'target_key' => 'To', 'is_dependency' => 'Dependency','is_mutual'=>'Mutual relationship','is_resolved'=>'Resolved','person_id'=>'Character','manuscript_id'=>'Manuscript','calendar_id'=>'Calendar','calendar_day'=>'Calendar day','reveal_chapter_id'=>'Reveal chapter','open_questions_md'=>'Open questions','conclusion_md'=>'Conclusion','children_mode'=>'Child-list handling','keep_previous_name'=>'Keep previous name as alias','source_name'=>'From','target_name'=>'To'];
@endphp
<x-studio.proposal-structure :fields="array_replace(collect($context)->only(['criteria','pins'])->all(), collect($fields)->all())" :catalogue="$catalogue" :proposal-names="$proposalNames" />

<dl>
    @foreach($fields as $key => $content)
        @continue(in_array($key,['beats','rules','cases','placements','subjects','knowers','pins','journey','months','evidence','profile','person_ids'],true))
        @php
            $display = match ($key) {
                'type_id' => $entryTypes->firstWhere('id', $content)?->name ?? 'Not selected',
                'person_id'=>$catalogue['person:'.$content]['name'] ?? 'Not set',
                'event_id'=>$catalogue['event:'.$content]['name'] ?? 'Not set',
                'manuscript_id'=>$content ? \App\Models\Manuscript::find($content)?->title : 'Not set',
                'calendar_id'=>$content ? \App\Models\WorldCalendar::find($content)?->name : 'Not set',
                'reveal_chapter_id'=>$content ? \App\Models\ManuscriptChapter::find($content)?->title : 'Not set',
                'children_mode'=>$content==='replace'?'Archive omitted children':'Keep omitted children',
                'source_key', 'target_key' => $catalogue[$content]['name'] ?? $content,
                'is_dependency', 'is_mutual', 'is_resolved', 'keep_previous_name' => $content ? 'Yes' : 'No',
                default => is_array($content) ? implode(', ', array_map(fn ($part) => is_scalar($part) ? (string) $part : json_encode($part, JSON_UNESCAPED_UNICODE), $content)) : $content,
            };
        @endphp
        <dt>{{ $labels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</dt>
        <dd>@if($key==='notes_md')@if(filled($display))<details><summary>Supplementary Notes</summary><x-studio.markdown :text="$display" /></details>@else Not set @endif @elseif(in_array($key, ['notes_md', 'truth_md', 'question_md','open_questions_md','conclusion_md'], true))<x-studio.markdown :text="$display ?? ''" />@else{{ $display }}@endif</dd>
    @endforeach
</dl>
