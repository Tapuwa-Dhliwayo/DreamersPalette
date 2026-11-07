@props(['path','field','rule'=>'string','catalogue'=>[],'entryTypes'=>[],'manuscripts'=>[],'calendars'=>[],'chapters'=>[],'label'=>null,'boardEvents'=>[]])
@php
    $labels=['person_id'=>'Character','event_id'=>'Event','event_ref'=>'Linked event','system_id'=>'Magic system','calendar_id'=>'Calendar','calendar_day'=>'Calendar day number','manuscript_id'=>'Manuscript','reveal_chapter_id'=>'Reveal chapter','truth_md'=>'Established truth','question_md'=>'Question','open_questions_md'=>'Open questions','conclusion_md'=>'Conclusion','statement_md'=>'Statement','conditions_md'=>'Conditions','consequences_md'=>'Consequences','exceptions_md'=>'Exceptions','reasoning_md'=>'Reasoning','reason_md'=>'Reason','notes_md'=>'Notes','intensity'=>'Intensity (leave blank if unknown)','is_mutual'=>'Mutual relationship','is_resolved'=>'Resolved','children_mode'=>'When updating child lists','key'=>'Subject','entry_id'=>'Encyclopedia entry'];
    $caption=$label ?? $labels[$field] ?? ucfirst(str_replace('_',' ',$field));
    $kind=match($field){'person_id'=>'person','event_id'=>'event','system_id'=>'system','entry_id'=>'entry',default=>null};
    $choices=match($field){'type_id'=>collect($entryTypes)->mapWithKeys(fn($type)=>[$type->id=>$type->name])->all(),'manuscript_id'=>collect($manuscripts)->pluck('title','id')->all(),'calendar_id'=>collect($calendars)->pluck('name','id')->all(),'reveal_chapter_id'=>collect($chapters)->pluck('title','id')->all(),'decision_id'=>\App\Models\MagicDecision::orderBy('id')->pluck('name','id')->all(),'children_mode'=>['merge'=>'Keep omitted children','replace'=>'Archive omitted children in supplied lists'],'key'=>collect($catalogue)->filter(fn($record)=>in_array($record['kind'],['person','entry','system']))->pluck('name','key')->all(),default=>[]};
    if($field==='event_ref') $choices=collect($catalogue)->where('kind','event')->pluck('name','key')->all()+collect($boardEvents)->mapWithKeys(fn($event)=>['board:'.$event->id=>'Proposed: '.$event->title])->all();
    if($kind)$choices=collect($catalogue)->where('kind',$kind)->mapWithKeys(fn($record)=>[(int)explode(':',$record['key'])[1]=>$record['name']])->all();
@endphp
@if($kind || $choices || in_array($field,['type_id','manuscript_id','calendar_id','reveal_chapter_id','key','decision_id','children_mode','event_ref']))
    <flux:select :label="$caption" wire:model="{{ $path }}"><option value="">Not set</option>@foreach($choices as $key=>$name)<option value="{{ $key }}">{{ $name }}</option>@endforeach</flux:select>
@elseif(str_contains($rule,'boolean'))
    <flux:checkbox :label="$caption" wire:model="{{ $path }}" />
@elseif(str_contains($rule,'integer') || str_contains($rule,'numeric'))
    <flux:input :label="$caption" type="number" :step="str_contains($rule,'numeric') ? 'any' : '1'" wire:model="{{ $path }}" />
@elseif(str_ends_with($field,'_md') || in_array($field,\App\Services\Worldbuilding\ProposalTypes::PROFILE))
    <flux:textarea :label="$caption" rows="3" wire:model="{{ $path }}" />
@else
    <flux:input :label="$caption" wire:model="{{ $path }}" />
@endif
