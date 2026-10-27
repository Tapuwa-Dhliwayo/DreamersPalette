# World development tools

Local implementation: 2026/10/25, on dev. No Anubis deployment.

Open Worlds → Dawn of the Moon. The world toolbar connects Magic, Characters,
Timeline, Arcs and Alignment. All records belong to the selected private world.

## Characters

One person can have several author-defined roles, including God and Herald. Roles
are comma-separated, aliases one per line. There is no mandatory folder hierarchy.
Domains and allegiances supplement Markdown notes. Search includes aliases; the role
filter uses the roles actually entered in that world. Timeline appearances link back
to events where that person participates. This is not yet a relationship graph.

## Timeline

Events store a fictional date/era label independently from a numeric chronological
order. Negative and fractional orders are supported; equal orders use record ID as
a stable tie-breaker. These numbers order records; they do not calculate elapsed time.
Participants must belong to the same world. No Gregorian calendar is imposed. Custom
calendar arithmetic and parallel timeline lanes remain future work.

## Arcs

An arc may focus on one person and may link to a manuscript already linked to that
world. Each beat has a title, ordering number, 0–100 value, optional timeline event,
and Markdown change/consequence notes. The author names the measure, such as pressure,
trust or influence. Values are authored judgments, not AI analysis or computed quality.

Flux Pro renders the line and point chart once two beats exist. A single beat remains
visible in the readable beat list without a misleading empty chart. The x-axis is the
sequence of ordered beats, not the distance between their ordering numbers. The y-axis
is fixed at 0–100. Tooltip titles and the list identify the beats.

## Reusable alignment charts

Each chart has its own editable left/right/top/bottom labels and axis explanation.
Traditional alignment is a starting set of labels, not a fixed taxonomy. Create as many
charts as needed. Place a character/god, magic system, or named concept; link at most
one existing record. A placement carries its display name and Markdown reasoning.

Coordinates run −100 to +100: x increases left to right; y increases bottom to top.
The custom three-by-three plot is a coordinate tool, while Flux Pro handles arc data
charts. Name markers open the edit form. Click a labelled region or Add to chart, choose a character (which fills their name), then choose a region visually and save.
Exact overlapping markers remain accessible through their list entries. Drag a name marker to reposition it, or use arrow keys in five-unit steps. Stale moves are rejected; failed requests revert the marker. Exact coordinates are optional under the placement form’s disclosure. Placement history over fictional time is not implemented yet.

## Storage and safeguards

Migration `2026_10_25_000003_create_world_development_tools.php` creates seven tables:
`studio_world_people`, `studio_world_events`, `studio_world_event_people`,
`studio_story_arcs`, `studio_arc_beats`, `studio_alignment_charts`,
`studio_alignment_placements`. Numeric IDs, deliberate relationship/order indexes,
no database cascades or foreign-key constraints. Existing writing is preserved.

World route capability and WorldPolicy protect access. Every write authorizes world
update; foreign references are validated within that world. Edit targets are locked
Livewire state. Record revision comparisons reject stale saves and retain form text.
The six Form Objects own form validation; shared model revision behavior owns the
transaction and conflict guard. No additional service/repository layer.

Forms use explicit Save. Reload protection warns about unsaved inputs; these forms do
not have manuscript autosave/local recovery. There is no delete/archive action yet.
New finished drafts copy people, event participants, arcs/beats and charts/placements
into world_context alongside existing magic version references. The draft comparison
world context can read those copies without relying on current world rows. Older
finished drafts have only the context captured when they were finished.

## Verification and attention

Actual local browser creation/saving was checked with isolated temporary records;
transactional probes checked combined roles, stale writes, fictional ordering,
participant/arc links, owner-only access, same-world validation, mutually exclusive
subject links and coordinate boundaries. No automated tests were added, per AGENTS.md.
Manual alignment review: `docs/manual-testing/world-alignment.md`.

Future increments: relationships, custom calendars, multi-lane timelines, multiple
arc tracks and temporal alignment changes. These are not claimed
as delivered by this increment.


Interaction correction, 2026/10/25: axis labels now sit immediately beside a bounded
chart; all nine regions show their meanings and are clickable. The world navigation
and character forms are named Characters; God/Herald remain optional roles.

Verified correction: browser region selection, automatic character name, visual form
position changes, drag persistence and two consecutive keyboard moves. Transactional
checks rejected stale moves, invalid bounds and placement IDs from another chart.
Desktop/tablet/phone layouts inspected; temporary records removed. No schema changes
or automated tests. Design detector findings: none; no suppressions.
