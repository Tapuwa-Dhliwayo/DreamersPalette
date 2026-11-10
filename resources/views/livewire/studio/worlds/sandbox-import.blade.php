@php
    $preview = $importRecord?->receipt['preview'] ?? null;
    $origin = collect([$importRecord?->external_origin['client'] ?? null, $importRecord?->external_origin['model'] ?? null])->filter()->implode(' · ');
    $actions = ['new' => 'New', 'update_record' => 'Update to an existing record', 'refine_proposal' => 'Refines a pending proposal'];
    $completeness = ['complete' => 'Complete', 'partial' => 'Partial draft', 'unsupported' => 'Needs unsupported work'];
@endphp
<flux:modal name="import-work" class="sandbox-import" x-on:close="$wire.cancelImport()">
    <flux:heading size="lg">Import work</flux:heading>
    @if($importErrors)
        <div role="alert" class="sandbox-errors">
            <p><strong>This package cannot be imported. Nothing was saved.</strong> Send these lines back to the chat client to get a corrected file.</p>
            <ul class="sandbox-import-errors">@foreach($importErrors as $path => $messages)@foreach((array) $messages as $message)<li>@unless(str_starts_with($path, 'importForm.') || $path === 'destination')<code>{{ $path }}</code> @endunless{{ $message }}</li>@endforeach @endforeach</ul>
        </div>
    @endif
    @if(!$importRecord)
        <form wire:submit="previewImport" class="sandbox-form">
            <div><label for="import-upload">Package file</label><input id="import-upload" type="file" wire:model="importForm.upload" accept=".md,.markdown,.txt,text/markdown,text/plain"></div>
            <div><label for="import-pasted">Or paste the Markdown from the chat</label><textarea id="import-pasted" rows="6" wire:model="importForm.pasted" placeholder="# …&#10;```dreamerspalette-exchange"></textarea></div>
            <p class="sandbox-help">Up to {{ number_format(\App\Services\Exchange\ExchangePackage::MAX_FILE_BYTES / 1048576, 0) }} MiB. Nothing is saved until you review the preview.</p>
            <div class="sandbox-actions"><flux:button type="submit" variant="primary" icon="eye" wire:loading.attr="disabled" wire:target="previewImport,importForm.upload">Preview import</flux:button><flux:modal.close><flux:button size="sm" variant="ghost">Cancel</flux:button></flux:modal.close></div>
        </form>
    @elseif($importRecord->isSaved())
        <p role="status">This package was already imported on {{ $importRecord->saved_at->asDate() }} as “{{ $importRecord->title }}”. Importing it again would add nothing.</p>
        <div class="sandbox-actions"><flux:button variant="primary" icon="arrow-top-right-on-square" wire:click="openImportedWork">Open imported work</flux:button><flux:button size="sm" variant="ghost" wire:click="cancelImport">Close</flux:button></div>
    @elseif($preview)
        <div class="sandbox-import-summary">
            <p><strong>{{ $importRecord->title }}</strong> · {{ ucfirst(str_replace('_', ' ', $preview['deliverable'])) }}</p>
            <p>Saved to: {{ $preview['creates_exercise'] ? 'New exercise: '.$preview['exercise_title'] : 'Exercise: '.$preview['exercise_title'] }}</p>
            @if($origin)<p class="sandbox-help">Reported by the sender, not verified: {{ $origin }}</p>@endif
        </div>
        <ol class="sandbox-import-items">
            @foreach($preview['items'] as $item)
                <li wire:key="import-item-{{ $item['index'] }}">
                    <div class="sandbox-import-item-head"><span class="sandbox-kind-dot kind-{{ $item['kind'] }}" aria-hidden="true"></span><span>{{ $item['kind'] === 'person' ? 'Character' : ucfirst($item['kind']) }}</span><strong>{{ $item['title'] }}</strong></div>
                    <p class="sandbox-help">{{ $actions[$item['action']] ?? $item['action'] }}@foreach($item['structure'] as $field => $count) · {{ $count }} {{ \Illuminate\Support\Str::plural(str_replace('_', ' ', \Illuminate\Support\Str::singular($field)), $count) }}@endforeach · {{ $completeness[$item['completeness']] ?? $item['completeness'] }}</p>
                    @if($item['missing'])<p class="sandbox-help">Still missing: {{ implode('; ', $item['missing']) }}</p>@endif
                    @foreach($item['possible_duplicates'] as $duplicate)<p class="sandbox-import-warning">May duplicate an existing {{ $duplicate['type'] }} named “{{ $duplicate['title'] }}”. Nothing is merged.</p>@endforeach
                    @foreach($item['conflicts'] as $conflict)<p class="sandbox-errors sandbox-import-conflict" role="alert">{{ $conflict['message'] }}</p>@endforeach
                </li>
            @endforeach
        </ol>
        <p class="sandbox-help">{{ $preview['link_count'] }} {{ \Illuminate\Support\Str::plural('connection', $preview['link_count']) }} between these items.</p>
        @if($preview['conflict_count'] > 0)
            <label class="sandbox-inline-check"><input type="checkbox" wire:model.live="importForm.conflictsAsAlternatives"> Save conflicting items as separate alternatives</label>
            <p class="sandbox-help">Your own corrections are kept. The imported version is added alongside as “Imported alternative”.</p>
        @endif
        <div class="sandbox-actions"><flux:button variant="primary" icon="arrow-down-tray" wire:click="saveImport" wire:loading.attr="disabled" wire:target="saveImport" :disabled="$preview['conflict_count'] > 0 && !$importForm->conflictsAsAlternatives">Save import</flux:button><flux:button size="sm" variant="ghost" icon="x-mark" wire:click="cancelImport">Cancel</flux:button></div>
    @endif
</flux:modal>
