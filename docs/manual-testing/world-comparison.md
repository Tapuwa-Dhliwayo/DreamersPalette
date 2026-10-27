# Charter: compare world subjects

2026/10/26 · 30–45 minutes · local development only.

Mission: develop a comparison that helps explain a real worldbuilding choice,
then verify that edits, uncertainty and archive/restore preserve that reasoning.
Use an owned disposable world with two characters or encyclopedia subjects. Do
not reset the database. Archive review material afterwards; preserve real writing.

## Phase 0 — shape review

Read the comparison's `criteria` and `subjects` JSON before clicking. Criteria and
rows must have stable order, each row's cells must match that criterion order,
and each subject must have its world key and a readable retained name. Finished
draft world context must contain a complete copy, not only live record IDs.
There is one revision for the whole comparison; simultaneous cell edits are not
merged. Decide whether that limitation fits this session before proceeding.

## Phase 1 — intended workflow

1. Open the world's Compare tool and choose New comparison. Expect an empty form.
2. Give it a title, add two meaningful criteria and two subjects. Enter differing
   explanations; leave one cell blank. Save. Expect the complete table and **Not
   established** for the blank, with links to both subjects.
3. Open a subject, then return to the saved comparison. Expect the same contents.
4. Edit. Add a third criterion, fill its cells, remove the middle criterion, and
   save. Expect each surviving cell under its original criterion, without shifting.
5. Edit again and remove a subject; Cancel. Expect the saved row still present.
6. Archive the comparison. In Browse → Show → Archived, select it and Restore.
   Expect all criteria, rows, Markdown and conclusions retained.
7. Finish a disposable linked manuscript draft. Later change the comparison.
   Expect the finished draft's stored world context to retain its earlier copy.

## Phase 2 — risk map / fix loop

Highest: another world or another author's subject must never be accepted by a
forged selection; a stale browser must never silently overwrite newer content.
Next: archive a linked subject and edit the comparison; its old association must
remain readable. An archived subject must not become a newly linkable subject.
Probe criteria removal in the middle, blank cells, duplicates, Markdown links,
long prose and narrower screens. Only the table region should scroll sideways.
Record findings and the exact probes here during the session; no automated tests.

## Phase 3 — verify

Read the saved row, its revision, deletion timestamp and archive actor. Confirm
restoration clears the archive state without changing cells. Compare the finished
draft's `world_context.development.comparisons` to the expected earlier version.
Check that review actions did not alter another world or manuscript's content.

## Deviations and attention

No automated tests, by project instruction. Browser and transactional checks are
engineering verification; Tapuwa's real-world comparison remains the design test.
Whole-world network graphs, per-cell history, calculated scores and charting are
outside this increment.
