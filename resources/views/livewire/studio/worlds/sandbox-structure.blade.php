@php
    $definition=\App\Services\Worldbuilding\ProposalTypes::definition($ideaForm->kind);
    $legacy=['aliases','roles','domain','type_id','measure','source_key','target_key','period','keep_previous_name','is_dependency','left_label','right_label','top_label','bottom_label','criteria'];
    $collections=['beats','rules','cases','placements','subjects','knowers','pins','journey','months','weekdays','evidence'];
    $rules=$definition['rules'];
@endphp
<section class="sandbox-structure-editor">
    <h3>Structured {{ $ideaForm->kind === 'person' ? 'character' : $ideaForm->kind }} data</h3>
    <flux:select label="Completeness" wire:model.live="ideaForm.completeness"><option value="complete">Complete for this request</option><option value="partial">Partial draft</option><option value="unsupported">Needs unsupported work</option></flux:select>
    <flux:textarea label="Missing work (one item per line; clear only when complete)" wire:model="ideaForm.missing" rows="2" />
    @foreach(\App\Services\Worldbuilding\ProposalTypes::fieldNames($ideaForm->kind) as $field)
        @continue(in_array($field,$legacy,true) || $field==='position')
        @if($field==='profile')
            @foreach(\App\Services\Worldbuilding\ProposalTypes::PROFILE as $section)
                <x-studio.proposal-value-input :path="'ideaForm.structured.profile.'.$section" :field="$section" rule="nullable|string" />
            @endforeach
        @elseif(in_array($field,$collections,true))
            <section class="sandbox-structure-collection">
                <h4>{{ ucfirst(str_replace('_',' ',$field)) }} · {{ count($ideaForm->structured[$field] ?? []) }}</h4>
                @foreach($ideaForm->structured[$field] ?? [] as $index=>$row)
                    <fieldset wire:key="structure-{{ $ideaForm->kind }}-{{ $field }}-{{ $index }}"><legend>{{ ucfirst(rtrim($field,'s')) }} {{ $index+1 }}</legend>
                        @if(is_array($row))
                            @foreach($rules as $key=>$rule)
                                @continue(!str_starts_with($key,$field.'.*.') || str_contains(substr($key,strlen($field.'.*.')),'.'))
                                @php($column=substr($key,strlen($field.'.*.')))
                                @continue(in_array($column,['id','revision','name','event_title'],true) && $column!=='name')
                                @if(in_array($column,['id','revision','event_title'],true)) @continue @endif
                                @if($column==='cells')
                                    @foreach(array_values(array_filter(preg_split('/\r?\n/',$ideaForm->criteria))) as $cell=>$criterion)
                                        <flux:textarea :label="$criterion" rows="2" wire:model="ideaForm.structured.{{ $field }}.{{ $index }}.cells.{{ $cell }}" />
                                    @endforeach
                                @elseif(in_array($column,['rule_ids','rule_refs'],true))
                                    <label>{{ $column==='rule_ids' ? 'Existing rules' : 'Rules in this proposal' }}
                                        <select multiple wire:model="ideaForm.structured.{{ $field }}.{{ $index }}.{{ $column }}">
                                            @if($column==='rule_refs')@foreach($ideaForm->structured['rules'] ?? [] as $ruleIndex=>$proposedRule)<option value="{{ $ruleIndex }}">{{ $proposedRule['title'] ?: 'New rule '.($ruleIndex+1) }}</option>@endforeach
                                            @else @foreach($existingRules ?? [] as $ruleRecord)<option value="{{ $ruleRecord->id }}">{{ $ruleRecord->title }}</option>@endforeach @endif
                                        </select>
                                    </label>
                                @elseif(str_contains($rule,'array'))
                                    @continue
                                @elseif($column==='key' && $field==='pins')
                                    @continue
                                @else
                                    <x-studio.proposal-value-input :path="'ideaForm.structured.'.$field.'.'.$index.'.'.$column" :field="$column" :rule="$rule" :catalogue="$catalogue" :entry-types="$entryTypes" :manuscripts="$structureManuscripts" :calendars="$structureCalendars" :chapters="$structureChapters" :board-events="$structureEvents" />
                                @endif
                            @endforeach
                        @elseif($field==='journey')
                            <flux:select label="Next pin" wire:model="ideaForm.structured.journey.{{ $index }}"><option value="">Choose pin</option>@foreach($ideaForm->structured['pins'] ?? [] as $pin)<option value="{{ $pin['key'] }}">{{ $pin['name'] }}</option>@endforeach</flux:select>
                        @else
                            <flux:input label="Name" wire:model="ideaForm.structured.{{ $field }}.{{ $index }}" />
                        @endif
                        <flux:button type="button" size="sm" variant="subtle" wire:click="removeStructureRow('{{ $field }}',{{ $index }})">Remove row</flux:button>
                    </fieldset>
                @endforeach
                <flux:button type="button" size="sm" icon="plus" wire:click="addStructureRow('{{ $field }}')">Add {{ rtrim(str_replace('_',' ',$field),'s') }}</flux:button>
            </section>
        @elseif($field==='person_ids')
            <label>Participants<select multiple wire:model="ideaForm.structured.person_ids">@foreach($catalogue as $record)@if($record['kind']==='person')<option value="{{ explode(':',$record['key'])[1] }}">{{ $record['name'] }}</option>@endif @endforeach</select></label>
        @else
            <x-studio.proposal-value-input :path="'ideaForm.structured.'.$field" :field="$field" :rule="$rules[$field]" :catalogue="$catalogue" :entry-types="$entryTypes" :manuscripts="$structureManuscripts" :calendars="$structureCalendars" :chapters="$structureChapters" :board-events="$structureEvents" />
        @endif
    @endforeach
</section>
