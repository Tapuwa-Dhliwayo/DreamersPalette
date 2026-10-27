# World relationships and comparisons

Local increment, 2026/10/26. No production deployment.

Open a world's **Relationships** or **Compare** tool. Both reuse characters,
encyclopedia entries and magic systems; gods and heralds remain ordinary subjects
with author-defined roles.

Relationships store a directed source, target, author-written relationship label,
optional fictional period, and Markdown context/reasoning. The browser searches
names and labels and filters connections by subject. The reading view shows the
relationship direction and links to the subjects. Multiple relationships between
the same subjects are allowed: different periods or interpretations are meaningful.
Self-links are rejected. This slice provides a focused connection reader rather
than a whole-world network graph.

Comparisons store ordered author-defined criteria and ordered linked subjects,
with Markdown cells and conclusions/open questions. Blank cells display **Not
established**. Criteria are unique within a comparison; a subject appears once.
The editor supports adding/removing criteria and subjects. Removing a criterion
removes its cells only when the author saves; Cancel leaves the stored table intact.
Limits are 20 criteria, 40 subjects and 10,000 characters per cell. These are prose
comparison tables, not numeric chart data or automatic claims about canon.

Two additive tables: `studio_world_relationships` and
`studio_world_comparisons`, created by
`2026_10_26_000002_create_world_relationships_and_comparisons.php`.
Comparison JSON belongs to one authored document: criteria, subject order and
cells save atomically under that record's revision. Stable subject keys use the
existing person/entry/system kinds and IDs; labels are retained for archived
subjects. Cells align to the ordered criteria array. There are no external keys,
cascades, new roles or public routes.

Both records have revisions, archive metadata and timestamps. Archive/restore
preserves content and links. Archived subjects already linked remain selectable
when editing; new links require active subjects from the same world. World
policy checks guard reading and mutation, including Livewire actions. Saves lock
the parent world so an archived world cannot receive new edits. Stale record
revisions reject overwrites. Finished manuscript drafts copy active relationships
and comparisons into their retained world context; old drafts are unchanged.
Subject labels are copies for historical readability; active detail links display
the current name. There is no per-cell revision history or independent cell save.

Verification: local migration and production asset build; compiled Blade PHP
syntax check; transactional scratch run for relationship save/edit, retained
archived subjects, stale overwrite rejection, comparison archive/restore, self-link
rejection and draft context inclusion. Browser checks cover real form submissions, middle-criterion removal, archive/restore,
and desktop/phone layouts. Cross-world subjects and a foreign author world were
rejected in a rolled-back probe. The temporary browser world was removed after review.
The design detector flagged a nonstandard corner radius; it was corrected to the
existing 12px scale. No design suppressions were added.
No automated tests were added, per repository instructions.
