<div x-data="{ browse: false }">
<header class="studio-header world-header"><a class="studio-brand" href="{{ route('studio.index') }}">Dreamer’s Palette</a><a href="{{ route('studio.worlds') }}">Worlds</a></header><x-studio.world-tools :world="$world" active="calendars" />
<div class="world-development" :class="{ 'browse-open': browse }"><aside class="world-browser" aria-label="Calendars"><div class="world-section-heading"><h2>Calendars</h2><button class="development-browse-toggle" @click="browse = false" aria-label="Close browse"><flux:icon.x-mark /></button></div><flux:select label="Show" wire:model.live.boolean="archived"><option value="false">Active</option><option value="true">Archived</option></flux:select>@can('update',$world)<flux:button icon="plus" variant="primary" wire:click="edit">New calendar</flux:button>
@endcan<div class="development-entries">@forelse($calendars as $listed)<button wire:click="select({{ $listed->id }})" @click="browse = false">{{ $listed->name }}</button>
@empty<p>No calendars here.</p>
@endforelse</div></aside>
<main id="studio-main" class="world-document development-document" :inert="browse"><button class="development-browse-toggle" @click="browse = true"><flux:icon.bars-3 variant="micro" />Browse calendars</button><p role="status">{{ $notice }}</p>@error('conflict')<p class="studio-alert">{{ $message }}</p>
@enderror
@if($calendar)<div class="world-entry-heading"><h1>{{ $calendar->name }}</h1>@can('update',$world)<div class="thinking-actions">@if(!$calendar->trashed())<flux:button icon="pencil-square" wire:click="edit({{ $calendar->id }})">Edit calendar</flux:button>
@endif<flux:button :icon="$calendar->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $calendar->id }}, {{ $calendar->revision }}, {{ $calendar->trashed() ? 'false' : 'true' }})">{{ $calendar->trashed() ? 'Restore' : 'Archive' }}</flux:button></div>
@endcan</div><div class="calendar-months">@foreach($calendar->months as $index=>$month)<div><span>{{ str_pad((string)($index+1),2,'0',STR_PAD_LEFT) }}</span><strong>{{ $month['name'] }}</strong><span>{{ $month['days'] }} days</span></div>
@endforeach</div>
@if($calendar->leap_every)<p>Every {{ $calendar->leap_every }} years, {{ $calendar->leap_days }} extra {{ Str::plural('day', $calendar->leap_days) }} {{ $calendar->leap_days === 1 ? 'is' : 'are' }} added to {{ $calendar->months[$calendar->leap_month-1]['name'] }}.</p>
@endif
@if(count($calendar->weekdays))<p>Week: {{ implode(' → ',$calendar->weekdays) }}</p>
@endif
<form wire:submit="convert" class="studio-form manuscript-details-form"><h2>World date</h2><div class="calendar-date-fields"><flux:input type="number" label="Year" wire:model="dateForm.year" min="1" /><flux:select label="Month" wire:model="dateForm.month">@foreach($calendar->months as $index=>$month)<option value="{{ $index+1 }}">{{ $month['name'] }}</option>
@endforeach</flux:select><flux:input type="number" label="Day" wire:model="dateForm.day" min="1" /></div><flux:button type="submit" icon="calendar-days">Show date</flux:button><p role="status">{{ $converted }}</p>
@can('update',$world)@if(!$calendar->trashed())<flux:select label="Assign to event" wire:model.live="dateForm.event_id"><option value="">Choose event</option>@foreach($events as $event)<option value="{{ $event->id }}">{{ $event->title }}</option>
@endforeach</flux:select><flux:button icon="check" wire:click="assign">Save event date</flux:button>
@if($events->firstWhere('id', $dateForm->event_id)?->calendar_id)
<p>Current date: {{ $events->firstWhere('id', $dateForm->event_id)->calendarLabel() }}</p>
<flux:button icon="x-mark" wire:click="clearEventDate">Remove calendar date</flux:button>
@endif
@endif
@endcan</form>
<section class="compile-recent"><h2>Chronology in this calendar</h2>@forelse($datedEvents as $event)<a class="compile-export-row" href="{{ route('studio.world.develop',['world'=>$world,'section'=>'timeline','selected'=>$event->id]) }}"><span>{{ $event->calendarLabel() }}</span><strong>{{ $event->title }}</strong></a>
@empty<p>No dated events yet.</p>
@endforelse</section><x-studio.markdown :text="$calendar->notes_md ?? ''" />
@else<div class="thinking-empty"><flux:icon.calendar-days /><h1>Calendars</h1><p>Define your world's months, week and leap cycle, then date its events.</p>@can('update',$world)<flux:button icon="plus" variant="primary" wire:click="edit">New calendar</flux:button>
@endcan</div>
@endif</main></div>
<flux:modal wire:model="editing" class="thinking-modal" :dismissible="false" :closable="false"><form wire:submit="save" class="thinking-form"><h2>{{ $editingId ? 'Edit calendar' : 'New calendar' }}</h2>@error('conflict')<p class="studio-alert">{{ $message }}</p>
@enderror<flux:input label="Name" wire:model="form.name" /><section><h3>Months, in order</h3>@foreach($form->months as $index=>$month)<div class="calendar-month-input" wire:key="calendar-month-{{ $index }}"><flux:input label="Month {{ $index+1 }}" wire:model="form.months.{{ $index }}.name" /><flux:input label="Days" type="number" wire:model="form.months.{{ $index }}.days" /><flux:button icon="trash" wire:click="removeMonth({{ $index }})" aria-label="Remove month {{ $index+1 }}" /></div>
@endforeach<flux:button icon="plus" size="sm" wire:click="addMonth">Add month</flux:button><flux:error name="form.months" /></section>
<section><h3>Weekdays, starting on the epoch day (optional)</h3>@foreach($form->weekdays as $index=>$weekday)<div class="thinking-field-action"><flux:input label="Day {{ $index+1 }} name" wire:model="form.weekdays.{{ $index }}" /><flux:button icon="trash" wire:click="removeWeekday({{ $index }})" aria-label="Remove weekday {{ $index+1 }}" /></div>
@endforeach<flux:button size="sm" icon="plus" wire:click="addWeekday">Add weekday</flux:button></section>
<section><h3>Leap cycle (optional)</h3><div class="calendar-date-fields"><flux:input label="Every N years" type="number" wire:model="form.leap_every" /><flux:input label="Month number" type="number" wire:model="form.leap_month" /><flux:input label="Extra days" type="number" wire:model="form.leap_days" /></div></section><p>Years start at 1. Dates count from the first day of that year. Changing months or leap rules can change existing event date labels.</p><flux:textarea label="Calendar notes" wire:model="form.notes_md" rows="4" /><div class="thinking-actions"><flux:button wire:click="$set('editing',false)">Cancel</flux:button><flux:button type="submit" variant="primary" icon="check">Save calendar</flux:button></div></form></flux:modal>
</div>
