<div x-data="{ browse: false, dirty: false }" @input="if ($event.target.closest('form')) dirty = true" @world-saved.window="dirty = false" @beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = ''; }">
    <header class="studio-header world-header">
        <a href="{{ route('studio.index') }}" class="studio-brand">Dreamer’s Palette</a>
        <nav class="studio-header-links" aria-label="Studio"><a href="{{ route('studio.index') }}">Write</a><a href="{{ route('studio.worlds') }}" aria-current="page">Worlds</a><a href="/dashboard">Dashboard</a></nav>
    </header>
    <x-studio.world-tools :world="$world" :active="$section" />
    <div class="world-development" :class="{ 'browse-open': browse }">
        <aside class="world-browser" aria-label="{{ ucfirst($section) }}">
            <div class="world-section-heading"><h2>{{ ucfirst($section) }}</h2><button class="development-browse-toggle" x-ref="closeBrowse" @click="browse = false; $nextTick(() => $refs.openBrowse.focus())" aria-label="Close browse"><flux:icon.x-mark /></button></div>
            <flux:input label="Search" icon="magnifying-glass" wire:model.live.debounce.250ms="search" />
            <flux:select label="Show" wire:model.live.boolean="archived"><option value="false">Active</option><option value="true">Archived</option></flux:select>
            @if($section === 'relationships')
                <flux:select label="Connected to" wire:model.live="subjectFilter"><option value="">All subjects</option>@foreach($catalogue as $subject)<option value="{{ $subject['key'] }}">{{ $subject['name'] }} · {{ $subject['type'] }}</option>@endforeach</flux:select>
            @endif
            @can('update', $world)<flux:button variant="primary" icon="plus" wire:click="edit">{{ $section === 'relationships' ? 'Add relationship' : 'New comparison' }}</flux:button>@endcan
            <div class="development-entries">
                @forelse($records as $listed)
                    <button wire:key="thinking-{{ $section }}-{{ $listed->id }}" wire:click="select({{ $listed->id }})" @click="browse = false" @class(['active' => $selected === $listed->id])>
                        @if($section === 'relationships')<span>{{ $listed->source_name }}</span><small>{{ $listed->label }} → {{ $listed->target_name }}</small>@else<span>{{ $listed->title }}</span><small>{{ count($listed->subjects) }} subjects · {{ count($listed->criteria) }} criteria</small>@endif
                    </button>
                @empty<p class="world-empty">{{ $search || $subjectFilter ? 'No matches' : ($archived ? 'Nothing archived' : 'Nothing added yet') }}</p>@endforelse
            </div>
        </aside>
        <main id="studio-main" class="world-document development-document" :inert="browse">
            <button class="development-browse-toggle" x-ref="openBrowse" @click="browse = true; $nextTick(() => $refs.closeBrowse.focus())"><flux:icon.bars-3 variant="micro" />Browse {{ $section }}</button>
            <p class="development-notice" role="status">{{ $notice }}</p>
            @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
            @if($record)
                <div class="world-entry-heading"><h1>{{ $section === 'relationships' ? $record->label : $record->title }}</h1>
                    @can('update', $world)<div class="thinking-actions">@if(!$record->trashed())<flux:button icon="pencil-square" size="sm" wire:click="edit({{ $record->id }})">Edit</flux:button>@endif<flux:button size="sm" :icon="$record->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $record->id }}, {{ $record->revision }}, {{ $record->trashed() ? 'false' : 'true' }})">{{ $record->trashed() ? 'Restore' : 'Archive' }}</flux:button></div>@endcan
                </div>
                @if($record->trashed())<p class="studio-alert">Archived. Finished drafts retain their saved copy.</p>@endif
                @if($section === 'relationships')
                    <div class="relationship-path">
                        <x-studio.world-subject :world="$world" :catalogue="$catalogue" :subject-key="$record->source_key" :name="$record->source_name" />
                        <div class="relationship-direction"><span>{{ $record->label }}</span><flux:icon.arrow-long-right /></div>
                        <x-studio.world-subject :world="$world" :catalogue="$catalogue" :subject-key="$record->target_key" :name="$record->target_name" />
                    </div>
                    @if($record->period)<div class="development-fact"><h2>When this applies</h2><p>{{ $record->period }}</p></div>@endif
                    @if($dependencyImpact)
                    <section class="reasoning-reveals"><h2>If {{ $record->target_name }} changes</h2>
                    <p>Following all unarchived dependency links, across their recorded periods. This shows what to review; it does not change those records.</p>
                    @if($dependencyImpact['cycle'])<p class="studio-alert">A circular dependency is reachable. These subjects need to be reviewed together.</p>
                    @endif
                    <ul>
                    @foreach($dependencyImpact['affected'] as $affected)<li>{{ $affected['name'] }} · {{ implode(' → ', $affected['path_names']) }}</li>
                    @endforeach
                    </ul>
                    @if($dependencyImpact['truncated'])<p>Showing the first 2,000 affected subjects.</p>
                    @endif
                    </section>
                    @endif
                    <section class="thinking-reason"><h2>Context & reasoning</h2><x-studio.markdown :text="$record->notes_md ?? ''" /></section>
                @else
                    <div class="comparison-scroll" role="region" aria-label="{{ $record->title }} comparison" tabindex="0">
                        <table class="world-comparison"><thead><tr><th scope="col">Subject</th>@foreach($record->criteria as $criterion)<th scope="col">{{ $criterion }}</th>@endforeach</tr></thead><tbody>
                            @foreach($record->subjects as $subject)<tr><th scope="row"><x-studio.world-subject :world="$world" :catalogue="$catalogue" :subject-key="$subject['key']" :name="$subject['name']" /></th>@foreach($record->criteria as $column => $criterion)<td>@if(filled($subject['cells'][$column] ?? ''))<x-studio.markdown :text="$subject['cells'][$column]" />@else<span class="comparison-unknown">Not established</span>@endif</td>@endforeach</tr>@endforeach
                        </tbody></table>
                    </div>
                    <x-studio.markdown :text="$record->notes_md ?? ''" />
                @endif
            @else
                <div class="thinking-empty"><flux:icon :name="$section === 'relationships' ? 'share' : 'table-cells'" /><h1>{{ $section === 'relationships' ? 'Relationships' : 'Comparisons' }}</h1><p>{{ $section === 'relationships' ? 'Connect characters, places, beliefs and systems. Name what links them and record how it changes.' : 'Choose world subjects and the questions you want to compare. Keep uncertainty visible alongside what you know.' }}</p>@can('update', $world)<flux:button variant="primary" icon="plus" wire:click="edit">{{ $section === 'relationships' ? 'Add relationship' : 'New comparison' }}</flux:button>@endcan</div>
            @endif
        </main>
    </div>
    <flux:modal wire:model="editing" class="thinking-modal" :dismissible="false" :closable="false">
        <form wire:submit="save" class="thinking-form">
            <h2>{{ $editingId ? 'Edit' : 'New' }} {{ $editingSection === 'relationships' ? 'relationship' : 'comparison' }}</h2>
            @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
            @if($editingSection === 'relationships')
                <div class="thinking-form-pair">
                    <flux:select label="From" wire:model="relationshipForm.source_key"><option value="">Choose subject</option>@foreach($choices as $subject)<option value="{{ $subject['key'] }}">{{ $subject['name'] }} · {{ $subject['type'] }}</option>@endforeach</flux:select>
                    <flux:select label="To" wire:model="relationshipForm.target_key"><option value="">Choose subject</option>@foreach($choices as $subject)<option value="{{ $subject['key'] }}">{{ $subject['name'] }} · {{ $subject['type'] }}</option>@endforeach</flux:select>
                </div>
                <flux:input label="Relationship" placeholder="e.g. protects, worships, depends on" wire:model="relationshipForm.label" />
                <flux:checkbox label="From depends on To — include in change impact" wire:model="relationshipForm.is_dependency" />
                <flux:input label="When this applies (optional)" placeholder="e.g. Before the exile" wire:model="relationshipForm.period" />
                <flux:textarea label="Context & reasoning" wire:model="relationshipForm.notes_md" rows="7" />
            @else
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
            @endif
            <div class="thinking-actions"><flux:button wire:click="$set('editing', false)" @click="dirty = false">Cancel</flux:button><flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">Save</flux:button></div>
        </form>
    </flux:modal>
</div>
