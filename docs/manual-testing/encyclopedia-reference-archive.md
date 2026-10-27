# Author links a manuscript passage and archives/restores its world reference

Updated: 2026/10/26. Timebox: 35–45 minutes. Risk: High — private prose, source anchors,
world ownership and recoverable archive transitions.

Goal: reference a location while writing, create an entry from another selected name,
then archive/restore without losing the prose, reference identity or finished context.
Stop after the intended flow, selected misuse probes and resulting-state verification,
or when a blocking defect prevents continuation. Never reset the database.

## Accounts, data and start

Use an author with dashboard access, manuscript view/update/finish and world view/update.
Have an active world and use Link world in the manuscript toolbar to attach it.
Confirm that this works even when the world has no magic system. Choose your own location
name and alias, and put that wording into a saved chapter. A second author/world is needed
only for privacy probes. Use a disposable local world for destructive-looking probes;
production is outside this session. Enter through Worlds → Encyclopedia and /studio.

## Phase 0 — shape review

Read the stored entry and passage link. Names/aliases are labels; IDs own identity.
The link has exact wording, context, source chapter revision, actor and a stable target.
Archived records have deleted_at/archived_by; they retain ordinary content and IDs.
A finished draft copies active encyclopedia and link context. Decide whether conservative
context matching and preview-only recognition fit your writing; record design findings.

## Phase 1 — intended workflow (notes)

1. Create a Location in Encyclopedia with one alias and Markdown notes. Save.
   Expect the entry in Active, readable formatting and search by its alias.
2. Open the linked manuscript, write the alias and save. Preview it. Expect a clickable
   highlight that opens the correct world entry without changing the Markdown source.
3. Return to Write, select the alias and choose Link to world. Link the existing entry.
   Expect Passage linked and one association under Links in this chapter. Retry Link:
   the same anchor/target must still have one record.
4. Select a different name. Choose Create from selection; create a Character or choose
   an encyclopedia type. Save. Expect both the record and passage association, with no
   mandatory folder creation. Existing prose remains unchanged.
5. Archive the original location in Encyclopedia. Expect it removed from Active. Choose
   Archived and open it; notes/aliases remain and Restore is offered. The manuscript's
   existing link can still read the target with an Archived notice.
6. Restore the location, then return to Active. Expect the same ID and content. Archive
   and restore the passage association from Links in this chapter; it must not delete
   the world entry or alter prose.
7. Archive a chapter from its sidebar and restore it from Archived chapters. Expect its
   prose/order retained. If it was the last chapter, the editor must show No chapters,
   not an editable Chapter 0. Check another chapter switch and subsequent save too.
8. Finish a disposable draft, then edit/archive the current world entry. Read the old
   draft's World context: its copied encyclopedia content must remain unchanged.

Observe save feedback, retained selector state, source wording, modal cancellation,
keyboard access, new/archived lists, long names and phone/tablet navigation. Record
findings through this pass; apply fixes afterwards.

## Phase 2 — misuse risk map (fix loop)

Choose probes in this order; the agent records attempts and findings.

- High: another author uses a direct world/manuscript URL or archive/link action.
  Expect denial on the server. A user without world-view capability must not receive
  its live catalogue just because they can open their own manuscript.
- High: link an entry from another world or submit a selected passage after another tab
  changes the chapter. Expect rejection; preserve the form/prose for recovery.
- High: archive then restore a manuscript/chapter while an old tab has queued writing.
  Its older save must fail, preserving the restored prose.
- High: archive a reference, then link the same anchor again. Expect restoration of the
  original association, without a unique-key error or duplicate.
- Medium: two entries share an alias. Preview should offer choices and never silently
  create a confirmed association. Edited anchor context should show Needs review.
- Medium: repeat alias creation, create from selection during a failed link, or switch
  to Archived before creating a new entry. Expect no repeated UUID-created entry and a
  clear active result after creation. Cancelling quick-create must not save.
- Medium: archive/restore a parent with independently archived children. Its own archive
  does not cascade; restoring it must not revive those separately archived children.

## Phase 3 — read-only verification

Substitute actual IDs; never write through these queries:

```sql
SELECT id, world_id, type_id, name, aliases, revision, deleted_at, archived_by
FROM studio_world_entries WHERE id = YOUR_ENTRY_ID;
SELECT id, chapter_id, target_kind, target_id, wording, source_revision,
       deleted_at, archived_by
FROM studio_chapter_references WHERE manuscript_id = YOUR_MANUSCRIPT_ID;
SELECT id, manuscript_id, position, revision, deleted_at, archived_by
FROM studio_chapters WHERE manuscript_id = YOUR_MANUSCRIPT_ID ORDER BY position;
```

Expected: one association per target/anchor; correct world/author, retained archive rows,
unchanged prose and frozen snapshots, increased revisions after archive/restore. No email,
model API request, publishing action or production mutation should occur. Preview markers
must not appear in downloaded Markdown. Alias matching does not invent numeric analysis.

## Findings and record

| ID | Step/probe | Action | Expected | Actual | Severity | Evidence | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |

Capture URL, record IDs, screenshot and exact error. Record date, tester, local revision,
browser/device, accounts, completed probes, blockers, findings by severity and uncovered
areas. Suggested next session: relationships and comparisons using real Dawn material.
No automated tests were written.

Retest notes: after archiving a character, edit an event/arc that already references
them; save unrelated changes and confirm that the existing link remains. The selected
archived participant should be labelled. Archive the last chapter and confirm the
No chapters state; restore and recheck its text. The Active/Archived selector must not
visually revert while showing the other collection. These were implementation findings,
not a reason to reset any review data.
