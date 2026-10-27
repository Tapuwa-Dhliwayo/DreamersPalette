# Author builds an alignment chart for their world

Updated: 2026/10/25. Duration: 35–45 minutes. Risk: High — private authored world
records, cross-world references and stale edits. Local dev only.

Goal: create a reusable chart, place a god and a concept, revise the reasoning, and
verify that coordinates, links and ownership remain correct. Stop when the intended
workflow, selected misuse risks and read-only state verification are complete, or a
blocking defect prevents continuing. Do not reset the database.

## Accounts and starting data

Use your existing local author account with dashboard.access and worlds.view/update.
Have Dawn of the Moon open and create your own God/Herald record in Characters.
A second account owning a different world is needed only for the ownership probe.
Do not grant it access to the first author's world. Existing records stay intact.
Start at `/studio/worlds/1/develop?section=alignments` if Dawn remains world 1.

## Phase 0 — stored shape review

Review the chart's four independent axis labels and explanation, then the placement's
name, optional person/system link, x/y coordinates, reasoning and revision. Check that
this can represent your actual moral or philosophical comparison without inventing a
character for a concept. God and Herald are roles on the same kind of person record.
A display name is authored per placement and does not automatically rename when its
linked person changes. Record a design finding if that behavior does not fit.

## Phase 1 — intended workflow (notes)

1. Choose New chart, name it, change all four labels and explain the axes. Save.
   Expect the chart selected, all labels visible, and a Saved message.
2. Click the upper-left labelled region. Choose your character; expect their name to
   fill automatically. Choose the same region in the visual picker and add reasoning. Save. Expect a name marker in
   the upper-left part of the plot, matching the placement list and linked person.
3. Add a second placement for a philosophy by naming it without a character link; choose the lower-right region.
   Expect a lower-right marker and a separate reasoning entry. The list orders subjects alphabetically. Drag the name marker to a different position; expect Position saved, then reload and confirm it stays there. Focus the marker and press an arrow key twice; both moves should save.
4. Open the first marker, change its region and reasoning, then save. Expect one
   updated placement and no duplicate. Reload: saved coordinates and prose persist.
5. Make a second chart with different axes. Expect its placements to be independent.
   Return to the first chart and verify the original placements remain.
6. At phone width, open Browse alignment charts, choose a chart and close the browser.
   Expect a full reading surface, visible labels and usable edit controls.

Observe keyboard focus, field labels, required-field errors, save feedback, long
subject names, marker/list correspondence and whether the tool supports your reasoning.
Record findings while completing the pass; do not mix fixes into this phase.

## Phase 2 — risk map (fix loop)

Choose probes from these risks; the agent records attempts and findings as you go.

- High: another author reaches the world through a direct URL or an existing action.
  Expect server-side denial, not merely hidden controls.
- High: a placement refers to a person/system from another world. Expect rejection.
- High: the same placement is open in two tabs. Save the first, then the second.
  Expect a conflict message and retained text; the older form must not overwrite.
- Medium: switch from a character to a magic system. Expect the character link cleared and system name filled. Forged dual links should fail validation.
- Medium: coordinates outside −100..100, missing name/axis labels, large prose.
  Expect field errors; valid form fields remain.
- Medium: subjects share exactly the same coordinates. Both must remain editable
  through list entries even when a marker overlaps.
- Medium: switching editor kinds after a save must not carry a previous field's
  value into another form. Cancel must not save; reload warns about unsaved changes.
- Medium: an interrupted create or rapid repeat submission may create a duplicate;
  create has no persistent idempotency token. Record what actually happens.

## Phase 3 — state verification (read-only)

Inspect the selected chart and its placements through the application or read-only
queries, substituting the actual IDs:

```sql
SELECT id, world_id, title, left_label, right_label, top_label, bottom_label, revision
FROM studio_alignment_charts WHERE id = YOUR_CHART_ID;
SELECT id, chart_id, name, person_id, system_id, x, y, revision
FROM studio_alignment_placements WHERE chart_id = YOUR_CHART_ID ORDER BY name;
```

Expected: one row per saved subject, only one optional link populated, correct world
and chart ownership, revision increased by edits, unchanged rows after failed saves.
At (−50, 50), the marker center is 25% from the left and 25% from the top; at (50, −50)
it is 75% from both. No email, AI request, publication or production write should fire.

## Findings and session record

| ID | Step/probe | Action | Expected | Actual | Severity | Evidence | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |

Capture affected URL, chart/placement IDs, screenshot and exact error. Record date,
tester, local revision, browser/device, accounts used, completed probes, blockers,
findings by severity and areas not covered. Next session: develop a character arc
through fictional timeline events. No automated tests were written.


Retest, 2026/10/25: main chart regions and the form’s visual picker replace mandatory
numeric entry. Drag a marker, click it to edit, and use repeated arrow-key moves.
Numbers remain available under the exact-position disclosure. Verify labels stay
adjacent to the chart on desktop and that tablet browsing does not squeeze the grid.
