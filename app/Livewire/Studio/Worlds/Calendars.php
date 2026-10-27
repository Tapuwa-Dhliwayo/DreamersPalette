<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\CalendarDateForm;
use App\Livewire\Forms\Worlds\CalendarForm;
use App\Models\World;
use App\Models\WorldCalendar;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.studio')]
class Calendars extends Component
{
    public World $world;

    public CalendarForm $form;

    public CalendarDateForm $dateForm;

    #[Url]
    public ?int $selected = null;

    public bool $editing = false;

    public bool $archived = false;

    public string $notice = '';

    public string $converted = '';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public int $eventRevision = 0;

    #[Locked]
    public int $calendarRevision = 0;

    public function mount(World $world): void
    {
        $this->authorize('view', $world);
        $this->world = $world;
        if ($this->selected) {
            $this->select($this->selected);
        }
    }

    public function select(int $id): void
    {
        $this->authorize('view', $this->world);
        $calendar = $this->world->calendars()->withTrashed()->findOrFail($id);
        $this->selected = $id;
        $this->calendarRevision = $calendar->revision;
        $this->dateForm->reset();
        $this->converted = '';
    }

    public function edit(?int $id = null): void
    {
        $this->authorize('update', $this->world);
        $this->resetValidation();
        $this->form->reset();
        $this->editingId = $id;
        if ($id) {
            $calendar = $this->world->calendars()->findOrFail($id);
            $this->form->fill([...$calendar->only(['name', 'months', 'weekdays', 'leap_every', 'leap_month', 'leap_days', 'revision']), 'notes_md' => $calendar->notes_md ?? '']);
        }
        $this->editing = true;
    }

    public function addMonth(): void
    {
        $this->authorize('update', $this->world);
        if (count($this->form->months) < 60) {
            $this->form->months[] = ['name' => '', 'days' => 30];
        }
    }

    public function removeMonth(int $index): void
    {
        $this->authorize('update', $this->world);
        if (isset($this->form->months[$index])) {
            array_splice($this->form->months, $index, 1);
        }
    }

    public function addWeekday(): void
    {
        $this->authorize('update', $this->world);
        if (count($this->form->weekdays) < 30) {
            $this->form->weekdays[] = '';
        }
    }

    public function removeWeekday(int $index): void
    {
        $this->authorize('update', $this->world);
        if (isset($this->form->weekdays[$index])) {
            array_splice($this->form->weekdays, $index, 1);
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->world);
        $fields = $this->form->fields();
        DB::transaction(function () use ($fields) {
            World::whereKey($this->world->id)->lockForUpdate()->firstOrFail();
            $calendar = $this->editingId ? $this->world->calendars()->findOrFail($this->editingId) : null;
            if ($calendar) {
                $latestDay = $this->world->events()->withTrashed()->where('calendar_id', $calendar->id)->max('calendar_day');
                $candidate = (new WorldCalendar)->forceFill($fields);
                if ($latestDay !== null && $latestDay >= $candidate->daysBeforeYear(1000001)) {
                    throw ValidationException::withMessages(['form.months' => 'Existing events exceed this calendar’s date range. Keep a longer year or review those dates first.']);
                }
                $calendar->revise($fields, $this->form->revision);
            } else {
                $calendar = $this->world->calendars()->create($fields);
            }$this->selected = $calendar->id;
        });
        $this->calendarRevision = $this->world->calendars()->findOrFail($this->selected)->revision;
        $this->editing = false;
        $this->archived = false;
        $this->notice = 'Saved';
    }

    public function convert(): void
    {
        $this->authorize('view', $this->world);
        $fields = $this->dateForm->fields();
        $calendar = $this->world->calendars()->withTrashed()->findOrFail($this->selected);
        $ordinal = $calendar->ordinal($fields['year'], $fields['month'], $fields['day']);
        $this->converted = $calendar->label($ordinal).' · Day '.$ordinal.' from the epoch';
    }

    public function updatedDateFormEventId(): void
    {
        $this->authorize('update', $this->world);
        $this->eventRevision = $this->dateForm->event_id ? $this->world->events()->findOrFail($this->dateForm->event_id)->revision : 0;
    }

    public function assign(): void
    {
        $this->authorize('update', $this->world);
        $fields = $this->dateForm->fields();
        if (! $fields['event_id']) {
            throw ValidationException::withMessages(['dateForm.event_id' => 'Choose an event.']);
        }
        DB::transaction(function () use ($fields) {
            World::whereKey($this->world->id)->lockForUpdate()->firstOrFail();
            $calendar = $this->world->calendars()->lockForUpdate()->findOrFail($this->selected);
            if ($calendar->revision !== $this->calendarRevision) {
                throw ValidationException::withMessages(['conflict' => 'The calendar changed. Reopen it before assigning a date.']);
            }$ordinal = $calendar->ordinal($fields['year'], $fields['month'], $fields['day']);
            $event = $this->world->events()->findOrFail($fields['event_id']);
            $event->revise(['calendar_id' => $calendar->id, 'calendar_day' => $ordinal], $this->eventRevision);
            $this->eventRevision = $event->fresh()->revision;
        });
        $this->notice = 'Event date saved';
    }

    public function clearEventDate(): void
    {
        $this->authorize('update', $this->world);
        abort_unless($this->dateForm->event_id, 422);
        DB::transaction(function (): void {
            World::whereKey($this->world->id)->lockForUpdate()->firstOrFail();
            $event = $this->world->events()->findOrFail($this->dateForm->event_id);
            $event->revise(['calendar_id' => null, 'calendar_day' => null], $this->eventRevision);
            $this->eventRevision = $event->fresh()->revision;
        });
        $this->notice = 'Calendar date removed. The event’s freeform date or era is available in Timeline.';
    }

    public function archiveRecord(int $id, int $revision, bool $archive): void
    {
        $this->authorize('update', $this->world);
        $this->world->calendars()->withTrashed()->findOrFail($id)->setArchived($archive, auth()->id(), $revision);
        $this->selected = null;
        if (! $archive) {
            $this->archived = false;
            $this->select($id);
        }
        $this->notice = $archive ? 'Archived' : 'Restored';
    }

    public function render()
    {
        $this->authorize('view', $this->world);

        return view('livewire.studio.worlds.calendars', ['calendars' => $this->world->calendars()->when($this->archived, fn ($query) => $query->onlyTrashed())->get(), 'calendar' => $this->selected ? $this->world->calendars()->withTrashed()->findOrFail($this->selected) : null, 'events' => $this->world->events()->get(), 'datedEvents' => $this->selected ? $this->world->events()->where('calendar_id', $this->selected)->reorder('calendar_day')->get() : collect()]);
    }
}
