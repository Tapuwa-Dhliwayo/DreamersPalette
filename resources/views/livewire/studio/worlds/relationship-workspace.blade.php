<main id="studio-main" class="relationships-workspace">
    <div class="relationships-heading"><div><h1 class="studio-page-title">Relationships</h1><p>{{ $records->count() }} {{ Str::plural('connection', $records->count()) }} · {{ $records->flatMap(fn ($link) => [$link->source_key, $link->target_key])->unique()->count() }} linked subjects</p></div>
        @can('update', $world)<flux:button variant="primary" icon="plus" wire:click="edit" :disabled="$editing">Add relationship</flux:button>@endcan
    </div>
    <div class="relationships-filters">
        <flux:input label="Search connections" icon="magnifying-glass" wire:model.live.debounce.250ms="search" :disabled="$editing" />
        <flux:select label="Connected to" wire:model.live="subjectFilter" :disabled="$editing"><option value="">All subjects</option>@foreach($catalogue as $subject)<option value="{{ $subject['key'] }}">{{ $subject['name'] }} · {{ $subject['type'] }}</option>@endforeach</flux:select>
        <flux:select label="Show" wire:model.live.boolean="archived" :disabled="$editing"><option value="false">Active</option><option value="true">Archived</option></flux:select>
    </div>
    @if($notice)<p class="development-notice" role="status">{{ $notice }}</p>@endif
    @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
    <div class="relationships-layout">
        <div class="relationships-connections">
            @if($records->isNotEmpty())<div @if($editing) inert @endif><x-studio.relationship-map :records="$records" :catalogue="$catalogue" :selected="$selected" :subject-filter="$subjectFilter" /></div>@endif
            <section class="relationships-list" aria-label="Connections">
                <h2>Connections</h2>
                @forelse($records as $link)
                    <button type="button" wire:key="connection-{{ $link->id }}" wire:click="select({{ $link->id }})" @disabled($editing) @class(['relationship-connection', 'is-selected' => $selected === $link->id]) aria-pressed="{{ $selected === $link->id ? 'true' : 'false' }}">
                        <span class="relationship-number">{{ $loop->iteration }}</span><span><strong>{{ $catalogue->get($link->source_key)['name'] ?? $link->source_name }} <span aria-label="{{ $link->is_mutual ? 'mutual' : 'to' }}">{{ $link->is_mutual ? '↔' : '→' }}</span> {{ $catalogue->get($link->target_key)['name'] ?? $link->target_name }}</strong><small>{{ $link->displayLabel() }}</small>@if($link->period)<small>{{ $link->period }}</small>@endif</span>
                    </button>
                @empty
                    <x-studio.empty-state icon="share" title="No connections">{{ $search || $subjectFilter ? 'No relationships match these filters.' : 'Connect two subjects, describe their relationship, and choose whether it has a direction or is mutual.' }}</x-studio.empty-state>
                @endforelse
            </section>
        </div>
        <aside id="relationship-inspector" x-ref="relationshipInspector" tabindex="-1" class="relationship-inspector" aria-label="Connection details">
            @if($editing)
                <form wire:submit="save" class="thinking-form" wire:key="relationship-edit-{{ $editingId ?? 'new' }}">
                    <h2>{{ $editingId ? 'Edit relationship' : 'New relationship' }}</h2>
                    <flux:select label="From subject" wire:model.live="relationshipForm.source_key"><option value="">Choose subject</option>@foreach($choices as $subject)<option value="{{ $subject['key'] }}">{{ $subject['name'] }} · {{ $subject['type'] }}</option>@endforeach</flux:select>
                    <flux:button size="sm" icon="arrows-up-down" wire:click="reverseRelationship" @click="dirty = true">Swap subjects</flux:button>
                    <p class="relationship-form-hint">Swapping changes the subject order. Update directional wording if needed.</p>
                    <flux:select label="To subject" wire:model.live="relationshipForm.target_key"><option value="">Choose subject</option>@foreach($choices as $subject)<option value="{{ $subject['key'] }}">{{ $subject['name'] }} · {{ $subject['type'] }}</option>@endforeach</flux:select>
                    <flux:checkbox label="Mutual — this relationship applies both ways" wire:model.live="relationshipForm.is_mutual" />
                    @if($relationshipForm->source_key && $relationshipForm->target_key)<p class="relationship-sentence">{{ $choices[$relationshipForm->source_key]['name'] ?? 'From' }} {{ $relationshipForm->is_mutual ? '↔' : '→' }} {{ $choices[$relationshipForm->target_key]['name'] ?? 'To' }}</p>@endif
                    <flux:textarea label="Relationship" placeholder="e.g. is a friend of, protects, rules over" wire:model="relationshipForm.label" rows="3" />
                    <flux:checkbox label="From depends on To — include in change impact" wire:model="relationshipForm.is_dependency" />
                    <flux:input label="When this applies (optional)" placeholder="e.g. Before the exile" wire:model="relationshipForm.period" />
                    <flux:textarea label="Context & reasoning" wire:model="relationshipForm.notes_md" rows="7" />
                    <div class="thinking-actions"><flux:button wire:click="cancelEdit" @click="dirty = false">Cancel</flux:button><flux:button type="submit" icon="check" variant="primary" wire:loading.attr="disabled" wire:target="save">Save relationship</flux:button></div>
                </form>
            @elseif($record)
                <div class="relationship-inspector-heading"><h2>Connection details</h2>@can('update', $world)<div class="studio-heading-actions">@if(!$record->trashed())<flux:button size="sm" icon="pencil-square" wire:click="edit({{ $record->id }})">Edit</flux:button>@endif<flux:button size="sm" :icon="$record->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $record->id }}, {{ $record->revision }}, {{ $record->trashed() ? 'false' : 'true' }})">{{ $record->trashed() ? 'Restore' : 'Archive' }}</flux:button></div>@endcan</div>
                @if($record->trashed())<p class="studio-alert">Archived. Finished drafts retain their saved copy.</p>@endif
                <div class="relationship-endpoints"><x-studio.world-subject :world="$world" :catalogue="$catalogue" :subject-key="$record->source_key" :name="$record->source_name" /><span aria-label="{{ $record->is_mutual ? 'mutual' : 'to' }}">{{ $record->is_mutual ? '↔' : '→' }}</span><x-studio.world-subject :world="$world" :catalogue="$catalogue" :subject-key="$record->target_key" :name="$record->target_name" /></div>
                <p class="relationship-description">{{ $record->displayLabel() }}</p>
                <dl class="relationship-facts"><div><dt>Direction</dt><dd>{{ $record->is_mutual ? 'Mutual · both subjects' : 'From '.$record->source_name.' to '.$record->target_name }}</dd></div>@if($record->period)<div><dt>When this applies</dt><dd>{{ $record->period }}</dd></div>@endif @if($record->is_dependency)<div><dt>Dependency</dt><dd>{{ $record->source_name }} depends on {{ $record->target_name }}</dd></div>@endif</dl>
                @if(filled($record->notes_md))<section class="thinking-reason"><h3>Context & reasoning</h3><x-studio.markdown :text="$record->notes_md" /></section>@endif
                @if($dependencyImpact)<section class="relationship-impact"><h3>If {{ $record->target_name }} changes</h3>@if($dependencyImpact['cycle'])<p class="studio-alert">A circular dependency is reachable. Review these subjects together.</p>@endif<ul>@forelse($dependencyImpact['affected'] as $affected)<li>{{ $affected['name'] }} · {{ implode(' → ', $affected['path_names']) }}</li>@empty<li>No other affected subjects.</li>@endforelse</ul>@if($dependencyImpact['truncated'])<p>Showing the first 2,000 affected subjects.</p>@endif</section>@endif
            @else
                <h2>Connection details</h2><p>Select a connection, or add a relationship between world subjects.</p>
            @endif
        </aside>
    </div>
</main>
