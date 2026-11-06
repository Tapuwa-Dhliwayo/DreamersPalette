# World relationships and comparisons

Local increment, 2026/10/26. No production deployment.

Open a world's **Relationships** or **Compare** tool. Both reuse characters,
encyclopedia entries and magic systems; gods and heralds remain ordinary subjects
with author-defined roles.

Relationships store source and target subjects, an explicit directed or mutual connection, an author-written relationship label,
optional fictional period, and Markdown context/reasoning. The browser searches
names and labels and filters connections by subject. The reading view shows the
relationship direction and links to the subjects. Multiple relationships between
the same subjects are allowed: different periods or interpretations are meaningful.
Self-links are rejected. The 2026/11/06 pass replaces the document-style relationship browser with a connection map and inspector. Numbered edges correspond to the connection list; arrows preserve direction, and explicitly mutual links have arrows at both ends. Clicking a subject filters its connections. Existing reciprocal records are retained independently, not silently merged. Large maps focus on a subject and cap the map at eight subjects and 24 displayed edges while retaining the full filtered list. Narrow screens use the connection list and inspector.

Comparisons store ordered author-defined criteria and ordered linked subjects,
with Markdown cells and conclusions/open questions. Blank cells display **Not
established**. Criteria are unique within a comparison; a subject appears once.
The full-page editor supports adding/removing criteria and subjects. Desktop reading uses the comparison matrix; narrow screens show each subject with its criteria and answers. Removing a criterion
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


## Separate world-tools pass — 2026/11/06

Fixed the relationship editor's TypeError when optional period/notes are null; comparison notes use the same explicit form preparation. Relationship editing now stays in the inspector with subject order, swap control and an explicit mutual option. Dependencies remain directed; both form validation and the model reject a mutual dependency. Existing links default to their previous directed meaning through additive migration `2026_11_06_000002_add_mutual_world_relationships.php`. Finished draft context captures the new attribute automatically; existing snapshots are unchanged.

Filtering/searching now chooses a record within the displayed results instead of leaving a hidden prior selection in the inspector. The connected-subject filter is represented in the URL so character-page links open the intended neighborhood. Character dossiers expose recorded relationships and links to the map. Timeline browsing uses an ordered event rail rather than repeating the entire chronology underneath each event. Arc notes and alignment-axis explanations are disclosures so the chart/board can take priority. Maps, calendars, reasoning and encyclopedia workflows retain their existing domain-specific tools.

Verification: reproduced the populated relationship view and the null-period error in the actual browser before the fix; checked editor loading, mutual preview, swapping and cancellation afterward. Rolled-back local execution verified null field loading, a saved mutual relationship, revision increment, rejection of a mutual dependency comparison null notes, and populated comparison/timeline rendering. The original relationship records were not changed. Build and Blade compilation passed. No production deployment.
