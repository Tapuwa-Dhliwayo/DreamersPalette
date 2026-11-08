# Build and refine world records by asking naturally

Prepared 2026/11/05. Local review, 35–45 minutes. One workflow: conversation → linked
pending updates → grouped review → author-approved save.

## Goal, risk and stop condition

An ordinary author should be able to ask for character sheets, refine the result
and review the linked changes without knowing a response mode or reading a guide.
Stop on an unreviewed canon write, loss of original notes, foreign citations or an
unrequested reread of the novel during saved-response preparation.

## Starting data and accounts

Use the existing local author. Exercise 13 retains the original trio request,
response 72 and thought 47. Recovery turn 49 contains three pending character-sheet
updates linked to existing people 2, 3 and 4. They were prepared with one response
request, zero reading requests; no original character, response or thought was
changed. No database reset is required or authorised. The real trio is for review;
use a disposable world/exercise for deliberate acceptance, Undo and destructive
probing. A second ordinary author is needed for ownership probes; no admin role.

## Phase 0 — shape review

Review response_contract=2, the scoped record catalogue, before/after revisions,
captured board proposal IDs/revisions, collected_proposals and recovery provenance.
New conversations always allow discussion, clarification, proposals and refinement;
there is no user-selected response mode. Recovery snapshots the saved answer or
thought plus its original request. Its passage collection is empty; original
citation-turn IDs resolve only inside the same owned exercise. Neither source prose
nor copied source rows are needed for recovery. Evidence must remain historical
when chapters have changed. Pending proposals never count as saved canon.

## Phase 1 — intended journey, notes mode

| Step | Action | Expected result / observe |
| --- | --- | --- |
| 1 | Open the chat as an ordinary user and inspect Chat settings. | Connection, fetched model, supported effort and reading controls; no Response mode. |
| 2 | In a disposable exercise ask a question about a named group; explicitly discuss without preparing changes. | Natural reply, no unwanted board drafts or canon writes. |
| 3 | Ask “Fill out their existing character sheets from what we discussed.” | Separate pending updates linked to existing people, beside the answer. No mode switch or collection step. |
| 4 | Inspect the affected-sheet list and proposed content/evidence. | Named targets, Notes destination, stated interpretations and open questions. |
| 5 | Ask to correct one pending sheet while leaving the others alone. | Same board proposal is refined; peers and original canon stay intact. |
| 6 | Use Review updates beside the reply. | Combined before/after review; individual draft editing and all linked relationships available. |
| 7 | On disposable records only, accept selected changes. | Exactly the reviewed content saved once; original unrelated notes, aliases and roles preserved. Open questions remain in Notes when explicitly acknowledged. |
| 8 | Open the original trio response or thought. | Review linked updates opens the three already prepared drafts. Prepare linked updates is available for other legacy prose/thoughts and reuses a prior preparation on repeat. |
| 9 | Inspect recovery Sources consulted and usage. | Historical citations retained; no new manuscript-reading request. Original answer/thought intact. |
| 10 | Try discussion, preparation and review on mobile. | Controls and draft review usable; other open sections and unsent writing survive. |

## Phase 2 — risk map, fix loop only when requested

| Priority | Probe | Required behaviour |
| --- | --- | --- |
| 1 | Other author/exercise/message/thought/source ID | Authorisation or scoped lookup refusal before inference. No foreign content. |
| 2 | Ask to discuss, keep something hypothetical or clarify an ambiguous pronoun | No automatic canon write; clarification/discussion can contain no proposals. |
| 3 | Repeated Prepare/Review/accept | Reuse captured preparation/drafts; no duplicate people or repeated application. |
| 4 | Change a real record or pending draft while generation/review is open | Stale collection/acceptance refused; newer author writing retained. |
| 5 | Model returns malformed JSON or inconsistent intent/updates | Clear failure, saved request/received material retained; no silent successful prose fallback. Raw JSON is not streamed as the normal reply. |
| 6 | Stop, timeout, quota or replaced worker | Preserve question/readings; stale attempt cannot stage drafts or overwrite a newer attempt. |
| 7 | Uncertain literary interpretation | Review can show all sheets; caveats remain in Notes. Saving qualified drafts needs explicit acknowledgement. |
| 8 | Archived source thought, changed chapter, old response mode or summary | Current permissions enforced; old snapshots remain historical; old in-flight turns retain their original protocol. Explicit discussion summaries remain separately reviewed. |
| 9 | Recovery after whole-source reading | Recovery does not reset the author's next-question reading preference or reread the selected manuscript. |

## Phase 3 — read-only verification

Compare original people IDs, notes, aliases, roles and revisions before acceptance.
Inspect each draft's target_key, target_revision, source_message_id and provenance.
Inspect request_context.recovery, collected_proposals and request purposes. Recovery
has no new source snapshots or reading requests. Repeated preparation returns the same
turn. Accepted changes have actor, before/after snapshots and retained uncertainties;
Undo records reversal and refuses to overwrite newer edits. Check original source
message/thought hashes and original reading section counts.

## Findings and evidence

| Finding | Resolution / verification |
| --- | --- |
| Hidden mode overrode “fill out their character sheets” | Removed user mode. New reply contract derives discussion/propose/refine/clarify from the conversation. |
| Put on board made one unlinked thought | Renamed Keep as thought; separate Prepare linked updates on legacy answers/thoughts. Record updates are auto-staged and reviewed beside their response. |
| No route from the existing trio answer to actual sheets | Turn 49 recovered three linked drafts with 55 retained citation links. Original people/answer/thought unchanged; one response request, zero reading requests. |
| Uncertainty prevented viewing all sheets together | Combined review retains open questions in Notes, with explicit acknowledgement before acceptance. |
| Recovery could become the remembered reading preference | Reading preference comes from the last ordinary question, not a helper recovery/summary. |

Live neutral-provider scratch journey passed: discussion/zero drafts, natural request/
three linked drafts, follow-up refinement/same draft and unchanged peers, grouped
acceptance preserving original fields. All neutral fixture writes rolled back.
Ownership, foreign-message and citation-scope refusal, grouped uncertainty review and
acceptance acknowledgement were also exercised without inference or retained writes.
No automated tests were added or changed. Literary accuracy of recovered summaries
still needs the author's review; none of the real trio's drafts were accepted.
