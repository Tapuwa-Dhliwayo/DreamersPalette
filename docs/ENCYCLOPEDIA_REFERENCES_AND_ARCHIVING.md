# Encyclopedia, manuscript references and archives

Implemented locally on dev, 2026/10/26. Production is unchanged.

## Encyclopedia

Worlds → Encyclopedia stores locations, species, cultures, factions, items, religions,
philosophies, languages, resources and concepts. Each entry has a name, type, aliases,
Markdown notes and revision. Search names/aliases and filter by type. Characters and
magic keep their established models and tools; the manuscript reference browser searches
all three kinds together. No folder setup is required.

The entry types are lookup records. This increment supplies reference records, not the
specialised language, belief, ecology or comparison tools still in the wider plan.
Existing alignment placements still link characters/magic; other encyclopedia subjects
can be named on a chart but are not yet linked there. Images, semantic canon/claim
classification and standalone entry history remain later
work. Finished drafts retain copies of encyclopedia entries alongside other world data.

## Writing with references

Use Link world in the manuscript toolbar to connect it to an active world. This does
not require a magic system or any existing encyclopedia entries. Its editor exposes Encyclopedia
for reading/searching and Link to world for selected Markdown wording. Linking flushes
prose first and checks chapter revision, active draft and the exact passage context.
Choose an existing entry, add the wording as its alias, or create a character/typed
encyclopedia entry from the selection and link it transactionally. Creation requests
have a UUID for retry safety; primary IDs remain numeric. Adding an alias requires
world update permission; linking your own chapter requires manuscript update and world
view. Other-world target IDs are rejected.

The selected wording is unchanged. Explicit links store the target's stable identity,
original display name, wording, surrounding context, source revision and author. They
are separate from Markdown. Repeated links to the same target/anchor reuse one record.
Changed or ambiguous anchors show Needs review rather than silently relinking. The
chapter-link list can archive and restore individual associations.

Preview recognises current active names and aliases using word boundaries and longer
phrases first. Click a highlight to read the entry. Shared aliases open choices rather
than choosing a target. Recognition is advisory, separate from confirmed associations.
It processes text nodes, excluding existing links/code, with a 1,500-term bound. Highlight
markup exists only in rendered preview; stored prose and Markdown exports stay clean.
The raw Markdown textarea does not yet have inline highlights or a persistent docked
encyclopedia. The reference browser is a dialog. Anchor context changes are deliberately
conservative and may require review even if the name itself remains.

## Archiving and restoring

Active/Archived controls and Restore actions are available for manuscripts, worlds,
encyclopedia entries, characters, magic systems/rules/cases, timeline events, arcs/beats,
alignment charts/placements and passage links. Chapter archives are in the manuscript
sidebar. There is no permanent-purge control. Archiving a parent does not cascade into
its children; restoring it reveals its retained children, except those independently
archived. Restore the parent before managing its child records.

Archive actions retain record IDs, prose, aliases, links, coordinates and authored
history. Archived entries are omitted from new recognition/search choices; existing
passage links can still open them with an Archived notice. Finished snapshots are never
rewritten. Archiving/restoring a magic rule or case records a new magic-system version.

Each archive stores the acting user. Record revisions prevent stale writes. Archiving
or restoring manuscripts invalidates queued chapter saves; chapter transitions also
check draft generation and manuscript revision. Archived worlds/manuscripts deny ordinary
opening and mutations, while their owning author can restore them from the library.
All capabilities reuse Spatie permissions and ownership policies. No administrator bypass
or editor grants are added.

## Database

`2026_10_26_000001_add_encyclopedia_and_studio_archives.php` creates entry types, world
entries and chapter references, then adds recoverable archive columns to existing private
studio tables. No public publishing/blog table changes, foreign keys or cascades.
The migration has not been deployed. During local review, reference archive fields were
added to that original migration and applied directly to the running table, preserving
the review data. No fresh/reset/rollback was run.

Alias lists remain JSON, consistent with the existing character/magic implementation.
Dedicated indexed alias rows can follow if catalogue size or richer identity management
justifies them. This increment does not add a generic repository/service layer.

## Verification and attention

Local browser checks cover preview alias highlighting, reading entries from a highlight,
explicit passage linking, selected-text character creation, typed entry creation and
entry archive/restore. Transactional scratch checks cover retained prose/links/snapshots,
stale selections and saves, retry safety, same-world references, alias creation and
ownership, plus magic versions and beat/placement restoration. Temporary records are
removed after review. No automated tests were added, per AGENTS.md.

Manual continuation: `manual-testing/encyclopedia-reference-archive.md`.
Next planned increments remain relationships/comparisons, richer compile/export,
geography and deeper narrative tools. No Anubis release is authorised yet.

Archive regression checks also covered editing an event/arc after a participant was
archived: existing archived relationships remain selected and labelled, and are not
silently detached by a subsequent save. Browser checks exercised the last-chapter
empty state, chapter restore and manuscript-library archive/restore. Active/Archived
selectors use explicit boolean binding so the visible choice matches server filtering.
