@props(['status' => []])
@if(($status['recorded'] ?? null) === false)
    <p class="sandbox-notice">Evidence coverage was not recorded for this earlier response; review its sources before accepting changes.</p>
@endif
@if($status['partial'] ?? false)
    <div class="sandbox-notice">
        <strong>Partial evidence</strong>
        <p>Some relevant material was unavailable or omitted. Review these limits before accepting the proposal or relying on a complete account.</p>
        @if($status['omitted_notes_count'] ?? count($status['omitted_notes'] ?? []))
            <p>{{ $status['omitted_notes_count'] ?? count($status['omitted_notes'] ?? []) }} saved analysis ranges were omitted.</p>
        @endif
        @if(!empty($status['unfetched_subjects']))<p>{{ count($status['unfetched_subjects']) }} selected subjects were not supplied in full.</p>@endif
        @if(($status['stop_reason'] ?? 'ready') !== 'ready')<p>Information gathering stopped: {{ str_replace('_', ' ', $status['stop_reason']) }}.</p>@endif
        @if(($status['inherited_stop_reason'] ?? 'ready') !== 'ready' && !empty($status['inherited_stop_reason']))<p>The original investigation stopped: {{ str_replace('_', ' ', $status['inherited_stop_reason']) }}.</p>@endif
        @if(!empty($status['pruned_retrieval_count']) || !empty($status['pruned_retrieval']))<p>Older information was removed to keep this request within its allowance.</p>@endif
        @if(($status['scope'] ?? '') === 'saved_response')<p>Prepared from a saved response; its evidence limitations still apply.</p>@endif
        @if($status['previous_limitations']['partial'] ?? false)<p>This refines an earlier proposal whose evidence was incomplete.</p>@endif
    </div>
@elseif(($status['scope'] ?? '') === 'focused')
    <p class="sandbox-help">Focused investigation · limited to the selected material.</p>
@endif
@if(!empty($status['context_sections_omitted']))
    <p class="sandbox-help">Additional background is available on request.</p>
@endif
