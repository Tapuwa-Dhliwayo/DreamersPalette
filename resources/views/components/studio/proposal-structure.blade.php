@props(['fields'=>[],'catalogue'=>[],'proposalNames'=>[]])
@php
    $name=fn($key)=>$catalogue[$key]['name'] ?? $proposalNames[$key] ?? (str_starts_with((string)$key,'board:') ? ($proposalNames[(int)substr($key,6)] ?? 'Proposed event') : $key);
    $collections=['beats','rules','cases','placements','subjects','knowers','pins','journey','months','evidence','profile','person_ids'];
@endphp
@foreach($collections as $collection)
    @continue(!array_key_exists($collection,$fields))
    @php($rows=$fields[$collection] ?? [])
    <section class="proposal-structure-preview">
        <h4>{{ ['profile'=>'Character profile','person_ids'=>'Participants','knowers'=>'Who knows or believes this'][$collection] ?? ucfirst($collection) }} · {{ count($rows) }}</h4>
        @if(!$rows)<p class="sandbox-help">No {{ str_replace('_',' ',$collection) }} supplied in this change.</p>
        @elseif($collection==='profile')
            @foreach($rows as $section=>$content)<h5>{{ ucfirst($section) }}</h5><x-studio.markdown :text="$content ?? ''" />@endforeach
        @elseif($collection==='subjects' && isset($fields['criteria']))
            <div class="proposal-table-scroll"><table><thead><tr><th>Subject</th>@foreach($fields['criteria'] as $criterion)<th>{{ $criterion }}</th>@endforeach</tr></thead><tbody>@foreach($rows as $row)<tr><th>{{ $name($row['key']) }}</th>@foreach($row['cells'] ?? [] as $cell)<td><x-studio.markdown :text="$cell ?? ''" /></td>@endforeach</tr>@endforeach</tbody></table></div>
        @elseif(in_array($collection,['beats','placements','pins','months'],true))
            <div class="proposal-table-scroll"><table><thead><tr>
                @foreach(match($collection){'beats'=>['Order','Beat','Event','Intensity'],'placements'=>['Subject','Horizontal','Vertical','Reason'],'pins'=>['Place','X %','Y %','Linked entry'],'months'=>['Month','Days']} as $heading)<th>{{ $heading }}</th>@endforeach
            </tr></thead><tbody>
                @foreach($rows as $row)<tr>
                    @if($collection==='beats')<td>{{ $row['position'] }}</td><th>{{ $row['title'] }}@if(filled($row['notes_md'] ?? null))<x-studio.markdown :text="$row['notes_md']" />@endif</th><td>{{ isset($row['event_ref']) ? $name($row['event_ref']) : (!empty($row['event_id']) ? $name('event:'.$row['event_id']) : 'No linked event') }}</td><td>{{ $row['intensity'] ?? 'Unrated' }}</td>
                    @elseif($collection==='placements')<th>{{ !empty($row['person_id']) ? $name('person:'.$row['person_id']) : (!empty($row['system_id']) ? $name('system:'.$row['system_id']) : $row['name']) }}</th><td>{{ $row['x'] }}</td><td>{{ $row['y'] }}</td><td><x-studio.markdown :text="$row['reason_md'] ?? ''" /></td>
                    @elseif($collection==='pins')<th>{{ $row['name'] }}</th><td>{{ $row['x'] }}</td><td>{{ $row['y'] }}</td><td>{{ !empty($row['entry_id']) ? $name('entry:'.$row['entry_id']) : 'None' }}</td>
                    @else<th>{{ $row['name'] }}</th><td>{{ $row['days'] }}</td>@endif
                </tr>@endforeach
            </tbody></table></div>
        @else
            <ol>@foreach($rows as $row)
                <li>
                    @if($collection==='person_ids'){{ $name('person:'.$row) }}
                    @elseif($collection==='journey'){{ collect($fields['pins'] ?? [])->firstWhere('key',$row)['name'] ?? 'Unresolved pin' }}
                    @elseif($collection==='knowers')<strong>{{ $name($row['key']) }}</strong><x-studio.markdown :text="$row['belief'] ?? ''" />@if(!empty($row['event_id']))<p>{{ $name('event:'.$row['event_id']) }}</p>@endif
                    @elseif($collection==='subjects'){{ $name($row['key']) }}
                    @elseif($collection==='evidence')<x-studio.markdown :text="$row['text'] ?? ''" /><p>{{ $row['source'] ?? '' }} · {{ ($row['supports'] ?? false) ? 'Supports' : 'Challenges' }}</p>
                    @else
                        <strong>{{ $row['title'] }}</strong>
                        @foreach(collect($row)->only(['statement_md','conditions_md','consequences_md','exceptions_md','reasoning_md','situation_md','outcome_md']) as $field=>$content)@if(filled($content))<h5>{{ ucfirst(str_replace('_md','',$field)) }}</h5><x-studio.markdown :text="$content" />@endif @endforeach
                        @if(isset($row['decision_id']))<p>Decision: {{ \App\Models\MagicDecision::find($row['decision_id'])?->name ?? 'Not selected' }}</p>@endif
                        @if(isset($row['rule_ids']) || isset($row['rule_refs']))<p>{{ count($row['rule_ids'] ?? []) }} existing rule links · {{ count($row['rule_refs'] ?? []) }} links to rules in this proposal</p>@endif
                    @endif
                </li>
            @endforeach</ol>
        @endif
    </section>
@endforeach
