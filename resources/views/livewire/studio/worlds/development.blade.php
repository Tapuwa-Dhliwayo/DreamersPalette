<div x-data="{ dirty: false, browse: false, moveError: '' }" @input="if ($event.target.closest('form')) dirty = true" @world-saved.window="dirty = false" @alignment-moved.window="moveError = ''" @alignment-move-failed.window="moveError = 'Position was not saved. Please try again.'" @beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = ''; }">
    <header class="studio-header world-header">
        <a href="{{ route('studio.index') }}" class="studio-brand">Dreamer’s Palette</a>
        <nav class="studio-header-links" aria-label="Studio"><a href="{{ route('studio.index') }}">Write</a><a href="{{ route('studio.worlds') }}" aria-current="page">Worlds</a><a href="/dashboard">Dashboard</a></nav>
    </header>
    <x-studio.world-tools :world="$world" :active="$section" />
    @php
        $titles = ['entries' => 'Encyclopedia', 'people' => 'Characters', 'timeline' => 'Timeline', 'arcs' => 'Story arcs', 'alignments' => 'Alignment charts'];
        $kinds = ['entries' => 'entry', 'people' => 'person', 'timeline' => 'event', 'arcs' => 'arc', 'alignments' => 'alignment'];
        $addLabels = ['entries' => 'New entry', 'people' => 'Add character', 'timeline' => 'Add event', 'arcs' => 'New arc', 'alignments' => 'New chart'];
    @endphp
    <div class="world-development" :class="{ 'browse-open': browse }">
        <aside class="world-browser" aria-label="{{ $titles[$section] }}">
            <div class="world-section-heading"><h2>{{ $titles[$section] }}</h2><button class="development-browse-toggle" x-ref="closeBrowse" @click="browse = false; $nextTick(() => $refs.openBrowse.focus())" aria-label="Close browse"><flux:icon.x-mark /></button></div>
            <flux:input wire:model.live.debounce.250ms="search" label="Search" icon="magnifying-glass" />
            <flux:select label="Show" wire:model.live.boolean="archived"><flux:select.option value="false">Active</flux:select.option><flux:select.option value="true">Archived</flux:select.option></flux:select>
            @if($section === 'entries')<flux:select label="Type" wire:model.live="typeFilter"><flux:select.option value="0">All types</flux:select.option>@foreach($types as $entryType)<flux:select.option value="{{ $entryType->id }}">{{ $entryType->name }}</flux:select.option>@endforeach</flux:select>@endif
            @if($section === 'people')<flux:select label="Role" wire:model.live="role"><flux:select.option value="">All roles</flux:select.option>@foreach($roles as $listedRole)<flux:select.option :value="$listedRole">{{ $listedRole }}</flux:select.option>@endforeach</flux:select>@endif
            @can('update', $world)<flux:button variant="primary" icon="plus" wire:click="edit('{{ $kinds[$section] }}')">{{ $addLabels[$section] }}</flux:button>@endcan
            <div class="development-entries">
                @forelse($entries as $listed)
                    <button wire:key="{{ $section }}-{{ $listed->id }}" wire:click="select({{ $listed->id }})" @click="browse = false" @class(['active' => $selected === $listed->id])>
                        <span>{{ in_array($section, ['people','entries']) ? $listed->name : $listed->title }}</span>
                        @if($section === 'people' && count($listed->roles))<small>{{ implode(' · ', $listed->roles) }}</small>@endif
                        @if($section === 'timeline')<small>{{ $listed->calendarLabel() ?: ($listed->date_label ?: 'Undated') }}</small>@endif
                    </button>
                @empty<p class="world-empty">{{ $search || $role ? 'No matches' : 'No entries yet' }}</p>@endforelse
            </div>
        </aside>
        <main id="studio-main" class="world-document development-document" :inert="browse">
            <button class="development-browse-toggle" x-ref="openBrowse" @click="browse = true; $nextTick(() => $refs.closeBrowse.focus())"><flux:icon.bars-3 variant="micro" />Browse {{ strtolower($titles[$section]) }}</button>
            <p class="development-notice" role="status">{{ $notice }}</p>
            @if($entry)
                <div class="world-entry-heading"><div><h1>{{ in_array($section, ['people','entries']) ? $entry->name : $entry->title }}</h1>@if($section === 'people')<div class="world-aliases">@foreach($entry->roles as $personRole)<span>{{ $personRole }}</span>@endforeach</div>@endif</div>@can('update', $world)@if(!$entry->trashed())<flux:button icon="pencil-square" size="sm" wire:click="edit('{{ $kinds[$section] }}', {{ $entry->id }})">Edit</flux:button>@endif@endcan</div>
                @if($entry->trashed())<p class="studio-alert">Archived. Existing links and finished drafts are retained.</p>@endif
                @can('update', $world)<div class="world-archive-actions"><flux:button size="sm" :icon="$entry->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $entry->id }}, {{ $entry->revision }}, {{ $entry->trashed() ? 'false' : 'true' }})">{{ $entry->trashed() ? 'Restore' : 'Archive' }}</flux:button></div>@endcan
                @if($section === 'entries')
                    <p class="world-state">{{ $entry->type->name }}</p>
                    @if(count($entry->aliases))<div class="development-fact"><h2>Also known as</h2><p>{{ implode(' · ', $entry->aliases) }}</p></div>@endif
                    <x-studio.markdown :text="$entry->notes_md ?? ''" />
                @elseif($section === 'people')
                    @if(count($entry->aliases))<div class="development-fact"><h2>Also known as</h2><p>{{ implode(' · ', $entry->aliases) }}</p></div>@endif
                    @if($entry->domain)<div class="development-fact"><h2>Domains & allegiances</h2><p>{{ $entry->domain }}</p></div>@endif
                    <x-studio.markdown :text="$entry->notes_md ?? ''" />
                    <section class="development-related"><h2>Timeline appearances</h2>@forelse($entry->events as $event)<a href="{{ route('studio.world.develop', ['world' => $world, 'section' => 'timeline', 'selected' => $event->id]) }}"><span>{{ $event->title }}</span><span>{{ $event->calendarLabel() ?: $event->date_label }}</span></a>@empty<p>No linked events</p>@endforelse</section>
                @elseif($section === 'timeline')
                    <p class="development-date">{{ $entry->calendarLabel() ?: ($entry->date_label ?: 'Undated') }}</p>
                    <x-studio.markdown :text="$entry->notes_md ?? ''" />
                    <section class="development-related"><h2>Participants</h2>@forelse($entry->people as $person)<a href="{{ route('studio.world.develop', ['world' => $world, 'section' => 'people', 'selected' => $person->id]) }}">{{ $person->name }}<span>{{ implode(' · ', $person->roles) }}</span></a>@empty<p>No participants</p>@endforelse</section>
                    <section class="development-chronology"><h2>World chronology</h2><ol>@foreach($events as $event)<li @class(['active' => $entry->id === $event->id])><button wire:click="select({{ $event->id }})"><time>{{ $event->calendarLabel() ?: ($event->date_label ?: 'Undated') }}</time><span>{{ $event->title }}</span></button></li>@endforeach</ol></section>
                @elseif($section === 'arcs')
                    <flux:select class="archive-filter" label="Show beats" wire:model.live.boolean="archivedChildren"><flux:select.option value="false">Active</flux:select.option><flux:select.option value="true">Archived</flux:select.option></flux:select>
                    <div class="world-aliases">@if($entry->person)<a href="{{ route('studio.world.develop', ['world' => $world, 'section' => 'people', 'selected' => $entry->person_id]) }}">{{ $entry->person->name }}</a>@endif @if($entry->manuscript)<a @if(!$entry->manuscript->trashed()) href="{{ route('studio.manuscript', $entry->manuscript) }}" @endif>{{ $entry->manuscript->title }}{{ $entry->manuscript->trashed() ? ' · Archived' : '' }}</a>@endif</div>
                    <x-studio.markdown :text="$entry->notes_md ?? ''" />
                    <div class="world-section-heading"><h2>{{ $entry->measure }}</h2>@can('update', $world)@if(!$entry->trashed() && !$archivedChildren)<flux:button icon="plus" size="sm" wire:click="edit('beat')">Add beat</flux:button>@endif@endcan</div>
                    @if($beats->isNotEmpty())
                        @if($beats->count() > 1)
                        <div class="development-arc-chart" wire:key="arc-chart-{{ $entry->id }}-{{ $beats->max('updated_at')?->timestamp }}-{{ $beats->sum('revision') }}" role="img" aria-label="{{ $entry->measure }} across {{ $beats->count() }} beats. Values are listed below.">
                            <flux:chart :value="$arcSeries" class="h-64">
                                <flux:chart.svg><flux:chart.line field="intensity" style="color:var(--studio-accent)" /><flux:chart.point field="intensity" style="color:var(--studio-accent)" /><flux:chart.axis axis="x" field="step"><flux:chart.axis.tick /><flux:chart.axis.line /></flux:chart.axis><flux:chart.axis axis="y" :tick-values="[0,25,50,75,100]"><flux:chart.axis.grid /><flux:chart.axis.tick /></flux:chart.axis></flux:chart.svg>
                                <flux:chart.tooltip><flux:chart.tooltip.heading field="title" /><flux:chart.tooltip.value field="intensity" :label="$entry->measure" /></flux:chart.tooltip>
                            </flux:chart>
                        </div>
                        @endif
                        <ol class="development-beats">@foreach($beats as $beat)<li wire:key="beat-{{ $beat->id }}"><div class="world-rule-heading"><h3>{{ $loop->iteration }}. {{ $beat->title }}</h3>@can('update', $world)@if(!$entry->trashed())<flux:button size="sm" :icon="$beat->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveChild('beat', {{ $beat->id }}, {{ $beat->revision }}, {{ $beat->trashed() ? 'false' : 'true' }})">{{ $beat->trashed() ? 'Restore' : 'Archive' }}</flux:button>@endif@endcan<span>{{ $beat->intensity }}/100</span>@can('update', $world)@if(!$archivedChildren && !$entry->trashed())<flux:button icon="pencil-square" size="sm" wire:click="edit('beat', {{ $beat->id }})">Edit beat</flux:button>@endif@endcan</div>@if($beat->event)<a class="development-event-link" href="{{ route('studio.world.develop', ['world' => $world, 'section' => 'timeline', 'selected' => $beat->event_id]) }}">{{ $beat->event->calendarLabel() ?: $beat->event->date_label }} · {{ $beat->event->title }}</a>@endif<x-studio.markdown :text="$beat->notes_md ?? ''" /></li>@endforeach</ol>
                    @else<p class="world-empty">No beats yet</p>@endif
                @else
                    <x-studio.markdown :text="$entry->notes_md ?? ''" />
                    <flux:select class="archive-filter" label="Show placements" wire:model.live.boolean="archivedChildren"><flux:select.option value="false">Active</flux:select.option><flux:select.option value="true">Archived</flux:select.option></flux:select>
                    <div class="world-section-heading"><h2>Alignment</h2>@can('update', $world)@if(!$entry->trashed() && !$archivedChildren)<flux:button icon="plus" size="sm" wire:click="edit('placement')">Add to chart</flux:button>@endif@endcan</div>
                    @can('update', $world)@if(!$entry->trashed() && !$archivedChildren)<p class="alignment-instructions">Click a region to add a character or concept. Drag a name to move it; click it to edit.</p>@endif@endcan
                    <p x-cloak x-show="moveError" x-text="moveError" role="alert" class="studio-alert"></p>
                    @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
                    <x-studio.alignment-board :chart="$entry" :placements="$placements" :editable="!$archivedChildren && !$entry->trashed() && auth()->user()->can('update', $world)" />
                    <ol class="development-placements">@forelse($placements as $placement)<li wire:key="placement-{{ $placement->id }}"><div class="world-rule-heading"><h3>{{ $loop->iteration }}. {{ $placement->name }}</h3>@can('update', $world)@if(!$entry->trashed())<flux:button size="sm" :icon="$placement->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveChild('placement', {{ $placement->id }}, {{ $placement->revision }}, {{ $placement->trashed() ? 'false' : 'true' }})">{{ $placement->trashed() ? 'Restore' : 'Archive' }}</flux:button>@endif@endcan<span>({{ $placement->x }}, {{ $placement->y }})</span>@can('update', $world)@if(!$archivedChildren && !$entry->trashed())<flux:button icon="pencil-square" size="sm" wire:click="edit('placement', {{ $placement->id }})">Edit placement</flux:button>@endif@endcan</div>@if($placement->person)<a href="{{ route('studio.world.develop', ['world' => $world, 'section' => 'people', 'selected' => $placement->person_id]) }}">{{ $placement->person->name }}</a>@endif @if($placement->system)<a @if(!$placement->system->trashed()) href="{{ route('studio.world', ['world' => $world, 'system' => $placement->system_id]) }}" @endif>{{ $placement->system->name }}{{ $placement->system->trashed() ? ' · Archived' : '' }}</a>@endif<x-studio.markdown :text="$placement->reason_md ?? ''" /></li>@empty<p>No subjects placed</p>@endforelse</ol>
                @endif
            @else
                <div class="studio-empty"><h1>{{ $titles[$section] }}</h1>@can('update', $world)<flux:button variant="primary" icon="plus" wire:click="edit('{{ $kinds[$section] }}')">{{ $addLabels[$section] }}</flux:button>@endcan</div>
            @endif
        </main>
    </div>
    <flux:modal wire:model="editing" class="world-form-modal" :dismissible="false" :closable="false">
        <form wire:submit="save" class="studio-form" wire:key="world-form-{{ $editingKind }}-{{ $editingId ?? 'new' }}">
            <flux:heading size="lg">{{ $editingId ? 'Edit' : 'New' }} {{ ['entry' => 'encyclopedia entry', 'person' => 'character', 'event' => 'event', 'arc' => 'arc', 'beat' => 'arc beat', 'alignment' => 'alignment chart', 'placement' => 'placement'][$editingKind] }}</flux:heading>
            @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
            @if($editingKind === 'entry')
                <flux:input label="Name" wire:model="entryForm.name" />
                <flux:select label="Type" wire:model="entryForm.type_id"><flux:select.option value="0">Choose type</flux:select.option>@foreach($types as $entryType)<flux:select.option value="{{ $entryType->id }}">{{ $entryType->name }}</flux:select.option>@endforeach</flux:select>
                <flux:textarea label="Aliases · one per line" wire:model="entryForm.aliases" rows="2" />
                <flux:textarea label="Notes · Markdown" wire:model="entryForm.notes_md" rows="10" />
            @elseif($editingKind === 'person')
                <flux:input label="Name" wire:model="personForm.name" />
                <flux:input label="Roles · comma separated" description="For example: Character, God, Herald. A person can have several roles." wire:model="personForm.roles" />
                <flux:textarea label="Aliases · one per line" wire:model="personForm.aliases" rows="2" />
                <flux:textarea label="Domains & allegiances" wire:model="personForm.domain" rows="2" />
                <flux:textarea label="Notes · Markdown" wire:model="personForm.notes_md" rows="10" />
            @elseif($editingKind === 'event')
                <flux:input label="Title" wire:model="eventForm.title" />
                @if($editingCalendarEvent?->calendar_id)
<p>Calendar date: {{ $editingCalendarEvent->calendarLabel() }}. <a href="{{ route('studio.world.calendars', ['world' => $world, 'selected' => $editingCalendarEvent->calendar_id]) }}">Change or remove it in Calendars</a>.</p>
<flux:error name="eventForm.date_label" />
@else
<flux:input label="World date or era" wire:model="eventForm.date_label" />
@endif
                <flux:input label="Chronological order" description="Lower numbers appear earlier. Decimals let you insert events between others." type="number" step="0.001" wire:model="eventForm.position" />
                <flux:textarea label="What happened · Markdown" wire:model="eventForm.notes_md" rows="8" />
                <fieldset class="world-rule-choices"><legend>Participants</legend>@foreach($people as $person)<label><input type="checkbox" wire:model="eventForm.person_ids" value="{{ $person->id }}" />{{ $person->name }}{{ $person->trashed() ? ' · Archived' : '' }}</label>@endforeach @error('eventForm.person_ids.*')<p role="alert">{{ $message }}</p>@enderror</fieldset>
            @elseif($editingKind === 'arc')
                <flux:input label="Title" wire:model="arcForm.title" />
                <flux:select label="Focus" wire:model="arcForm.person_id"><flux:select.option value="">Whole story</flux:select.option>@foreach($people as $person)<flux:select.option value="{{ $person->id }}">{{ $person->name }}{{ $person->trashed() ? ' · Archived' : '' }}</flux:select.option>@endforeach</flux:select>
                <flux:select label="Manuscript" wire:model="arcForm.manuscript_id"><flux:select.option value="">World arc</flux:select.option>@foreach($manuscripts as $manuscript)<flux:select.option value="{{ $manuscript->id }}">{{ $manuscript->title }}{{ $manuscript->trashed() ? ' · Archived' : '' }}</flux:select.option>@endforeach</flux:select>
                <flux:input label="Chart measures" description="Name what your 0–100 beat values represent, such as pressure, trust or influence." wire:model="arcForm.measure" />
                <flux:textarea label="Arc · Markdown" wire:model="arcForm.notes_md" rows="8" />
            @elseif($editingKind === 'beat')
                <flux:input label="Title" wire:model="beatForm.title" />
                <flux:input label="Beat order" type="number" step="0.001" wire:model="beatForm.position" />
                <flux:input label="Value · 0 to 100" type="number" min="0" max="100" wire:model="beatForm.intensity" />
                <flux:select label="Timeline event" wire:model="beatForm.event_id"><flux:select.option value="">No linked event</flux:select.option>@foreach($availableEvents as $event)<flux:select.option value="{{ $event->id }}">{{ $event->calendarLabel() ?: $event->date_label }} · {{ $event->title }}{{ $event->trashed() ? ' · Archived' : '' }}</flux:select.option>@endforeach</flux:select>
                <flux:textarea label="Change & consequences · Markdown" wire:model="beatForm.notes_md" rows="8" />
            @elseif($editingKind === 'alignment')
                <flux:input label="Title" wire:model="alignmentForm.title" />
                <div class="development-field-pair"><flux:input label="Left" wire:model="alignmentForm.left_label" /><flux:input label="Right" wire:model="alignmentForm.right_label" /></div>
                <div class="development-field-pair"><flux:input label="Top" wire:model="alignmentForm.top_label" /><flux:input label="Bottom" wire:model="alignmentForm.bottom_label" /></div>
                <flux:textarea label="What these axes mean · Markdown" wire:model="alignmentForm.notes_md" rows="6" />
            @else
                <flux:select label="Character" wire:model.live="placementForm.person_id"><flux:select.option value="">Choose a character, or name another subject below</flux:select.option>@foreach($people as $person)<flux:select.option value="{{ $person->id }}">{{ $person->name }}{{ $person->trashed() ? ' · Archived' : '' }}</flux:select.option>@endforeach</flux:select>
                <flux:input label="Name on chart" wire:model="placementForm.name" />
                <flux:text>Choose a position on the chart.</flux:text>
                @if($entry instanceof \App\Models\AlignmentChart)<x-studio.alignment-board :chart="$entry" :picker="true" />@endif
                <details class="alignment-options"><summary>Link a magic system or set an exact position</summary>
                    <flux:select label="Magic system" wire:model.live="placementForm.system_id"><flux:select.option value="">No system link</flux:select.option>@foreach($systems as $system)<flux:select.option value="{{ $system->id }}">{{ $system->name }}{{ $system->trashed() ? ' · Archived' : '' }}</flux:select.option>@endforeach</flux:select>
                    <div class="development-field-pair"><flux:input label="Horizontal · −100 left, +100 right" type="number" min="-100" max="100" wire:model="placementForm.x" /><flux:input label="Vertical · −100 bottom, +100 top" type="number" min="-100" max="100" wire:model="placementForm.y" /></div>
                </details>
                <flux:textarea label="Reasoning · Markdown" wire:model="placementForm.reason_md" rows="3" />
            @endif
            <div class="studio-form-actions"><flux:button @click="dirty = false" wire:click="$set('editing', false)">Cancel</flux:button><flux:button type="submit" variant="primary" icon="check">Save</flux:button></div>
        </form>
    </flux:modal>
</div>
