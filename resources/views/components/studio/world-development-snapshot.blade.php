@props(['snapshot', 'manuscript' => null, 'draft' => null])
@foreach(['entries' => 'Encyclopedia', 'people' => 'Characters', 'events' => 'Timeline', 'arcs' => 'Arcs', 'alignments' => 'Alignment charts', 'relationships' => 'Relationships', 'comparisons' => 'Comparisons', 'maps' => 'Maps', 'calendars' => 'Calendars', 'investigations' => 'Investigations', 'knowledge' => 'Knowledge'] as $group => $label)
    @if(count($snapshot[$group] ?? []))
        <details class="world-rule-details"><summary>{{ $label }} · {{ count($snapshot[$group]) }}</summary>
            @foreach($snapshot[$group] as $record)
                <article class="world-rule"><h3>{{ $record['name'] ?? $record['title'] ?? $record['label'] }}</h3>
                    @if($group === 'entries')<p>{{ $record['type']['name'] ?? '' }}</p><p>{{ implode(' · ', $record['aliases']) }}</p>
@endif

                    @if($group === 'people')<p>{{ implode(' · ', $record['roles']) }}</p><p>{{ implode(' · ', $record['aliases']) }}</p>@if($record['domain'])<p>{{ $record['domain'] }}</p>
@endif
 
@endif

                    @if($group === 'events')<p>{{ $record['calendar_label'] ?? $record['date_label'] }}</p><p>{{ implode(' · ', array_column($record['people'], 'name')) }}</p>
@endif

                    @if($group === 'relationships')
                        <p>{{ $record['source_name'] }} → {{ $record['label'] }} → {{ $record['target_name'] }}</p><p>{{ $record['period'] }}</p>
                    
@endif

                    @if($group === 'comparisons')
                        <div class="comparison-scroll"><table class="world-comparison"><thead><tr><th>Subject</th>@foreach($record['criteria'] as $criterion)<th>{{ $criterion }}</th>
                        
@endforeach
</tr></thead><tbody>@foreach($record['subjects'] as $subject)<tr><th>{{ $subject['name'] }}</th>@foreach($record['criteria'] as $column => $criterion)<td><x-studio.markdown :text="$subject['cells'][$column] ?: '_Not established._'" /></td>
                        
@endforeach
</tr>
                        
@endforeach
</tbody></table></div>
                    
@endif

                    @if($group === 'investigations')
                        <h4>Question</h4><x-studio.markdown :text="$record['question_md']" />
                        @foreach($record['evidence'] as $evidence)
                        <h4>{{ $evidence['supports'] ? 'Supports' : 'Challenges' }} · {{ $evidence['source'] }}</h4><x-studio.markdown :text="$evidence['text']" />
                        @endforeach
                        <h4>Open questions</h4><x-studio.markdown :text="$record['open_questions_md'] ?? ''" />
                        <h4>{{ $record['is_resolved'] ? 'Decision' : 'Working conclusion' }}</h4><x-studio.markdown :text="$record['conclusion_md'] ?? ''" />
                    @endif
                    @if($group === 'knowledge')
                        <h4>Author’s truth</h4><x-studio.markdown :text="$record['truth_md'] ?? ''" />
                        @foreach($record['knowers'] as $knower)
                        <h4>{{ $knower['name'] }} · {{ $knower['event_title'] ?: 'Not dated' }}</h4><x-studio.markdown :text="$knower['belief']" />
                        @endforeach
                        <p>Reader reveal: {{ $record['reveal_chapter']['title'] ?? 'Not scheduled' }}</p>
                    @endif
                    @if($group === 'calendars')
                        @foreach($record['months'] as $month)<p>{{ $month['name'] }} · {{ $month['days'] }} days</p>
                        @endforeach
                    @endif
                    @if($group === 'maps')
                        @if($record['image_path'] && $manuscript && $draft)<img src="{{ route('studio.draft.map.image', [$manuscript, $draft->id, $record['id']]) }}" alt="{{ $record['title'] }} retained map" />
                        
@endif

                        <p>Version {{ $record['revision'] }} · {{ $record['width_units'] ? 'Width '.$record['width_units'].' '.$record['unit'] : 'No scale set' }}</p>
                        @foreach($record['pins'] as $pin)<p>{{ $pin['name'] }} · {{ $pin['x'] }}%, {{ $pin['y'] }}%</p>
                        
@endforeach

                    
@endif

                    <x-studio.markdown :text="$record['notes_md'] ?? ''" />
                    @if($group === 'arcs')<h4>{{ $record['measure'] }}</h4>@foreach($record['beats'] as $beat)<h4>{{ $beat['title'] }} · {{ $beat['intensity'] }}/100</h4><x-studio.markdown :text="$beat['notes_md'] ?? ''" />
@endforeach
 
@endif

                    @if($group === 'alignments')<p>{{ $record['left_label'] }} ↔ {{ $record['right_label'] }} · {{ $record['bottom_label'] }} ↔ {{ $record['top_label'] }}</p>@foreach($record['placements'] as $placement)<h4>{{ $placement['name'] }} · ({{ $placement['x'] }}, {{ $placement['y'] }})</h4><x-studio.markdown :text="$placement['reason_md'] ?? ''" />
@endforeach
 
@endif

                </article>
            
@endforeach

        </details>
    
@endif


@endforeach

