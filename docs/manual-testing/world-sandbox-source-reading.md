# Explore an existing manuscript without locating its chapters first

Prepared 2026/11/04. Local only. Timebox: 35–45 minutes.

## Goal, risk and stop condition

**High:** private manuscript reading, durable questions and evidence-backed literary
judgments. Verify that selecting a large manuscript makes it available for bounded
reading, that the assistant distinguishes available from actually read material,
and that interruption does not lose the question or completed reading.

Stop after the original scenario, a chosen interruption/scope probe and read-only
verification are complete. Stop immediately for private-source leakage, lost writing
or an assistant response falsely presented by the interface as a full-book review.
Record findings in notes mode during the normal journey. Fix only when requested.

## Starting account and material

Use your existing verified local author, ID 1, and the existing exercise **13**,
**The heroic trio relationship**, in **Asprino 9**, world 1:
<http://127.0.0.1:8000/studio/worlds/1/sandbox?exercise=13>.

Its existing 15 selected chapters contain 313,346 characters. The three selected
world people and all manuscript prose were preserved by this fix. You do not need
to deselect chapters to begin. The question that failed under the old workflow was
not saved by that old version; paste it once into the updated composer below.

My ChatGPT and My Claude use local subscription logins. Reading makes additional
requests to the connection you explicitly choose. No automatic paid API fallback
occurs. For the first pass use one of those existing local connections.

Permissions: `worlds.view`, `worlds.update`, ownership of the world/exercise, and
ownership of the selected connection. Sources must belong to active manuscripts
owned by the same author and linked to this world.

## Fixture reset and preservation

No database refresh, wipe or chapter edits are needed. Use new questions in the
same exercise, or create a separate exercise for repeated probes. Do not canonise
anything during this reading review. Stop any active response before leaving.

Archive a disposable exercise through Exercise & context if you want it out of
the working list. Preserve the real exercise and its new conversation as review
evidence. Reading snapshots/notes do not modify the manuscript or chapter summaries.

## Phase 0 — shape review before clicking

With the agent, inspect these stored structures without modifying them:

- `studio_assistant_turns.message_id`, `request_context`, `reading_depth`, `stage`,
  `progress`, `status` and request UUID. The question is committed before preparation.
- The captured source IDs and exercise settings. Later exercise changes must not
  silently alter the scope of an in-progress or retried question.
- `studio_assistant_sources`: turn/chapter IDs, saved chapter revision, title,
  passage text and completed read ranges with notes. All selected sources are
  snapshotted in one scoped transaction. Unselected chapters are not copied.
- Original source prose versus derived reading notes. Notes are assistant
  interpretations, not canon and not replacements for chapter prose.
- Final `context_manifest.reading`: unread, partial or full coverage and actual
  passage ranges, separate from `source_collection` availability.
- Saved original messages, accepted conversation-summary coverage and the lack of
  raw full-chapter replay in ordinary request context.

Implementation: `AssistantConversation`, `ExerciseContext`, `SourceReading`,
`AssistantSource`, `ManuscriptChapter::readingPassages()`, and the Sandbox component.
The additive migration is `2026_11_04_000003_add_assistant_source_reading.php`.

## Phase 1 — intended journey, notes mode

1. **Open the original exercise.** In Exercise & context inspect Source collection.
   Expect the existing selected chapters, grouped by manuscript, with controls for
   all available chapters and all chapters in a particular manuscript. Keep the
   current 15-chapter scope. It describes permission to consult, not a promise to
   send every chapter in one request.
2. **Open Read & discuss.** Expect a wider conversation area beside details/source
   evidence. The board remains available from Board/Timeline/List. The connection
   and reading controls can be expanded from the composer summary.
3. **Choose the connection and focused reading.** Select My Claude or My ChatGPT;
   Response = Conversation; Reading = Find relevant passages. The selected account
   and reading approach should remain clear when controls are collapsed.
4. **Submit your real question once:**

   > So from the text Samuela is not yet to the point where is deep enough that him
   > dying in the next two chapters is satisfying but what if its a death of
   > potential a death where by the future was robbed from us and next two chapter
   > explore him in Tiberius absence

   Expect the question to appear in the saved conversation immediately. Preparation
   and reading progress should replace the old oversized-exercise refusal. Reload
   while it is reading: the question and turn must remain, without a duplicate send.
5. **Inspect the finished discussion.** It should engage the actual narrative
   question, separate evidence from interpretation, and qualify what it has not
   read. Focused reading must not be labelled a whole-manuscript review. A search
   hit or a lack of a literal name match is not evidence of presence/absence by itself.
6. **Open Sources consulted.** Expect 15 available chapters, with per-chapter unread,
   partial or full coverage and passage ranges actually read. A chapter merely
   being available must not be shown as read. Observe whether the selected scenes
   actually help answer your question; relevance quality is a finding to record.
7. **Follow a citation.** Expect saved source passages to open beside the discussion
   inside DreamersPalette. Inspect nearby passages and Reading notes. The notes
   are labelled assistant interpretation. The displayed source version must match
   the one used for the response, including after a later manuscript edit.
8. **Ask a follow-up without knowing a chapter name.** For example, ask for earlier
   scenes that establish Samuelá's unrealised plans or clarify the trio's separation.
   Expect the assistant to investigate within the selected collection, rather than
   require you to supply exact chapter locations. Source references remain scoped.
9. **Try conversation summarisation.** Choose Summarise discussion. Expect a summary
   of the discussion, without another manuscript reading pass. Review it before
   using Use as working summary. Original messages and source snapshots remain.
10. **Choose a broader pass if time/allowance permits.** Set Reading = Read whole
    source collection and explicitly ask for an assessment across the selected
    manuscript. Expect section-by-section progress. A run pauses after a bounded
    number of new sections; Continue reading resumes the same question and skips
    completed reading requests. A final whole-source answer comes only after all
    selected source sections have been read. Do not wait beyond the session timebox;
    pausing and retaining a resumable state is an acceptable stopping point.

## Phase 2 — choose misuse probes, fix-loop mode

| Priority | Risk | Probe and required behaviour |
| --- | --- | --- |
| 1 | Unauthorised source | With the agent, attempt another exercise's reading ID or a chapter outside the selected/owned collection. Server refuses; no source content leaks. |
| 2 | Lost question | Stop while preparing/reading, then reload. Question and completed notes remain. Resume saved question does not add another user message. |
| 3 | Preparation failure | If an actual provider/preparation error occurs, inspect the saved question and Retry saved question. Edit saved question restores the original text to the composer for revised scope/wording. |
| 4 | Changed source | Change a chapter only in a separate disposable exercise if desired. Historical citations retain their saved version; coverage indicates that the chapter changed. |
| 5 | False completeness | Compare partial/unread chapter coverage with the response. A whole-book assertion from focused excerpts should be recorded as a substantive defect. |
| 6 | Scope changed mid-reading | Change the exercise's current source selection while a request is running. The in-progress request continues against its captured collection; a new question uses the new saved scope. |
| 7 | Duplicate continuation | Repeated Resume/Continue must not create duplicate user messages or concurrent processing of the same turn. |
| 8 | Layout and navigation | Open citations on phone/tablet. The source pane must actually be selected; switching back to conversation/board must remain possible. Unsaved detail protection remains. |

Provider/model changes are not silently applied to a saved retry. If that original
connection/model is no longer available, Edit saved question lets you explicitly
choose a new connection and send a new request. Failed/paused attempts remain visible.

## Phase 3 — read-only state verification

Replace TURN_ID with the numeric request ID, then inspect with the agent:

```sql
SELECT id, exercise_id, message_id, response_id, provider, model, reading_depth,
       status, stage, progress, started_at, finished_at
FROM studio_assistant_turns WHERE id = TURN_ID;

SELECT id, chapter_id, chapter_revision, manuscript_id, position, title,
       JSON_LENGTH(passages) AS available_passages,
       JSON_LENGTH(reads) AS completed_reading_requests
FROM studio_assistant_sources WHERE turn_id = TURN_ID ORDER BY position, id;

SELECT id, role, CHAR_LENGTH(content) AS characters
FROM studio_assistant_messages WHERE exercise_id = 13 ORDER BY id;
```

Inspect `context_manifest.reading` for exact coverage. Repeated retries reuse the
same `message_id`. Completed read ranges and notes remain across pauses. Do not
print the full prompt or manuscript into a shared report; inspect only the relevant
passages. Original chapter revisions/content should not change through reading.
There must be no new canon change, public chapter, finished draft, notification or
email from this workflow. No API fallback should be charged.

## Findings and stop state

Expected final state: the real question and grounded discussion are saved; source
coverage is inspectable; citations show pinned passages; original writing/canon
is unchanged; no unwanted active response remains.

Capture the exercise URL, turn ID, connection/model, citation/range, exact error
text and a screenshot where useful. For a literary-quality concern, identify the
source passage and what the answer missed rather than recording only “bad answer.”

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| --- | --- | --- | --- | --- | --- | --- |
| | | | | | | |

Severity: Critical / High / Medium / Low. Status: New / Fixed / Documented /
Needs investigation / Ready for retest / Not a defect.

## Engineering verification already performed

The real 15-chapter exercise prepared a 2,955-character brief and a scoped source
catalogue in a rolled-back probe; no real manuscript was sent by that probe.
A live local-provider exercise with 214,336 characters of disposable prose finished
with relevant reading and honest unread coverage. Separate live probes preserved a
failed question, retried without duplicating it, and paused/resumed whole-source
reading without repeating the completed section request. Source-viewer and chapter
snapshot ownership failures were checked through actual requests/rollback probes.
An interrupted-worker probe confirmed that a replaced attempt cannot complete or
overwrite the winning response. Desktop and phone source deep links were checked
in the browser against disposable prose.
No automated tests were added. These checks do not replace your literary review.
