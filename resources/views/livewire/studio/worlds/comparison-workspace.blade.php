<main id="studio-main" class="comparison-workspace">
    <div class="relationships-heading"><h1 class="studio-page-title">Comparisons</h1>@can('update', $world)<flux:button icon="plus" variant="primary" wire:click="edit" :disabled="$editing">New comparison</flux:button>@endcan</div>
    @if($notice)<p class="development-notice" role="status">{{ $notice }}</p>@endif
    @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
    @if($editing)
        <form wire:submit="save" class="thinking-form comparison-editor" wire:key="comparison-edit-{{ $editingId ?? 'new' }}">
            <h2>{{ $editingId ? 'Edit comparison' : 'New comparison' }}</h2>
                <flux:input label="Title" wire:model="comparisonForm.title" />
                <section class="comparison-criteria"><h3>Criteria</h3>
                    @foreach($comparisonForm->criteria as $index => $criterion)<div wire:key="criterion-{{ $index }}" class="thinking-field-action"><flux:input label="Criterion {{ $index + 1 }}" wire:model="comparisonForm.criteria.{{ $index }}" placeholder="e.g. Source of authority" /><flux:button icon="trash" size="sm" wire:click="removeCriterion({{ $index }})" @click="dirty = true" aria-label="Remove criterion {{ $index + 1 }}" /></div>@endforeach
                    <flux:button icon="plus" size="sm" wire:click="addCriterion" @click="dirty = true">Add criterion</flux:button>
                    <flux:error name="comparisonForm.criteria" />
                </section>
                <section class="comparison-edit-subjects"><h3>Subjects</h3>
                    @foreach($comparisonForm->subjects as $row => $subject)<div class="comparison-edit-subject" wire:key="subject-{{ $row }}">
                        <div class="thinking-field-action"><flux:select label="Subject {{ $row + 1 }}" wire:model="comparisonForm.subjects.{{ $row }}.key"><option value="">Choose subject</option>@foreach($choices as $choice)<option value="{{ $choice['key'] }}">{{ $choice['name'] }} · {{ $choice['type'] }}</option>@endforeach</flux:select><flux:button icon="trash" size="sm" wire:click="removeSubject({{ $row }})" @click="dirty = true" aria-label="Remove subject {{ $row + 1 }}" /></div>
                        <div class="thinking-form-pair">@foreach($comparisonForm->criteria as $column => $criterion)<flux:textarea label="{{ $criterion ?: 'Criterion '.($column + 1) }}" wire:model="comparisonForm.subjects.{{ $row }}.cells.{{ $column }}" placeholder="Leave blank if not established" rows="3" />@endforeach</div>
                    </div>@endforeach
                    <flux:button icon="plus" size="sm" wire:click="addSubject" @click="dirty = true">Add subject</flux:button><flux:error name="comparisonForm.subjects" />
                </section>
                <flux:textarea label="Conclusions & open questions" wire:model="comparisonForm.notes_md" rows="4" />

            <div class="thinking-actions"><flux:button wire:click="cancelEdit" @click="dirty = false">Cancel</flux:button><flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">Save comparison</flux:button></div>
        </form>
    @else
        <div class="comparison-controls">
            <flux:input label="Find comparison" icon="magnifying-glass" wire:model.live.debounce.250ms="search" />
            <flux:select label="Comparison" wire:model.live.number="selected" :disabled="$records->isEmpty()">@if($records->isEmpty())<option value="">No comparisons</option>@endif@foreach($records as $listed)<option value="{{ $listed->id }}">{{ $listed->title }}</option>@endforeach</flux:select>
            <flux:select label="Show" wire:model.live.boolean="archived"><option value="false">Active</option><option value="true">Archived</option></flux:select>
        </div>
        @if($record)
            <div class="world-entry-heading"><h2>{{ $record->title }}</h2>@can('update', $world)<div class="studio-heading-actions">@if(!$record->trashed())<flux:button size="sm" icon="pencil-square" wire:click="edit({{ $record->id }})">Edit comparison</flux:button>@endif<flux:button size="sm" :icon="$record->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $record->id }}, {{ $record->revision }}, {{ $record->trashed() ? 'false' : 'true' }})">{{ $record->trashed() ? 'Restore' : 'Archive' }}</flux:button></div>@endcan</div>
            @if($record->trashed())<p class="studio-alert">Archived. Finished drafts retain their saved copy.</p>@endif
            <p class="comparison-summary">{{ count($record->subjects) }} subjects · {{ count($record->criteria) }} criteria</p>
            <div class="comparison-desktop comparison-scroll" role="region" aria-label="{{ $record->title }} comparison" tabindex="0">
                <table class="world-comparison"><thead><tr><th scope="col">Subject</th>@foreach($record->criteria as $criterion)<th scope="col">{{ $criterion }}</th>@endforeach</tr></thead><tbody>
                    @foreach($record->subjects as $subject)<tr><th scope="row"><x-studio.world-subject :world="$world" :catalogue="$catalogue" :subject-key="$subject['key']" :name="$subject['name']" /></th>@foreach($record->criteria as $column => $criterion)<td>@if(filled($subject['cells'][$column] ?? ''))<x-studio.markdown :text="$subject['cells'][$column]" />@else<span class="comparison-unknown">Not established</span>@endif</td>@endforeach</tr>@endforeach
                </tbody></table>
            </div>
            <div class="comparison-mobile">@foreach($record->subjects as $subject)<section><h3><x-studio.world-subject :world="$world" :catalogue="$catalogue" :subject-key="$subject['key']" :name="$subject['name']" /></h3><dl>@foreach($record->criteria as $column => $criterion)<div><dt>{{ $criterion }}</dt><dd>@if(filled($subject['cells'][$column] ?? ''))<x-studio.markdown :text="$subject['cells'][$column]" />@else<span class="comparison-unknown">Not established</span>@endif</dd></div>@endforeach</dl></section>@endforeach</div>
            @if(filled($record->notes_md))<section class="comparison-conclusions"><h3>Conclusions & open questions</h3><x-studio.markdown :text="$record->notes_md" /></section>@endif
        @else
            <x-studio.empty-state icon="table-cells" title="No comparisons">{{ $search ? 'No comparisons match this search.' : 'Choose subjects and criteria to compare them side by side. Unknown answers stay visible.' }}<x-slot:action>@can('update', $world)<flux:button variant="primary" icon="plus" wire:click="edit">New comparison</flux:button>@endcan</x-slot:action></x-studio.empty-state>
        @endif
    @endif
</main>
