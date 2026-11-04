# Keep a novel discussion flowing across reading, sources and other studio work

Prepared 2026/11/04. Local only. Timebox: 35–45 minutes.

## Goal, risk and stop condition

**High:** private manuscript references, background work, provider preferences and
saved writing. Verify that you can ask about your novel, inspect the evidence,
leave the exercise, return to its result, and reuse saved reading for a new question.
Stop after that journey, selected risk probes and read-only verification. Stop
immediately if writing disappears, a source from another world appears, or the
interface claims an answer is ready while the question is still pending.

## Account, data and preserved starting state

Use your existing local author, ID 1, with `worlds.view`, `worlds.update`, ownership
of the exercise and connection. Start at exercise 13, **The heroic trio relationship**,
in world 1: <http://127.0.0.1:8000/studio/worlds/1/sandbox?exercise=13>.
The earlier question and 33 completed reading sections were preserved. Do not paste
the original question again merely to resume it. No reset is required or authorised.
Keep the current chapters, questions, notes and canon. Use a separate disposable
exercise/chapter for deliberate source changes. Choose an existing local subscription
connection; no paid API fallback is enabled.

## Phase 0 — shape review before clicking

With the agent, inspect `studio_assistant_turns` and `studio_assistant_sources` for
exercise 13. Confirm the current status and source revisions; notes are derived
interpretations, never canon. Captured request context stores `connection_model`,
`effort` and `auto_continue`; the turn's `model` records the requested override.
`acknowledged_at` retains activity review across navigation. Saved references reuse
only identical chapter prose and revision in the same author's world. Source IDs
inside reused notes must point to the new request's source snapshots.

## Phase 1 — intended journey, notes mode

| Step | Action | Expected visible result / observe |
| --- | --- | --- |
| 1 | Open the existing exercise and Sources consulted. | Existing read passages and pinned revisions remain; reading visibility is retained. |
| 2 | If the original turn is paused, choose Continue reading with automatic continuation enabled. | The same saved question resumes. A working indicator, named passages and background explanation appear. Further batches continue automatically; the original question eventually receives a response or a clear failure. |
| 3 | While reading, expand Sources consulted and reading notes. Leave them open through several updates. | Neither disclosure snaps closed. Passage progress continues updating. |
| 4 | Open a source citation from the discussion. | Only the source pane changes. Conversation scroll, typed message, selected model and other open sections stay in place; no page navigation. Browser back should not be needed. Modified-click retains normal link behaviour. |
| 5 | Close Details, then reopen with the Details toolbar control. | The same panel is recoverable. Check Conversation similarly, including Read & discuss. |
| 6 | In an empty disposable exercise, select Timeline then List. | Each view has a distinct heading, selected control and useful empty explanation. Adding an event/idea makes it appear in the appropriate view. |
| 7 | Open composer settings. Choose a model name/alias supported by the selected account and a local thinking effort. Send a short follow-up question. | The turn records the model and effort. Reading may reuse saved references and revisit relevant prose. The connection's global default remains unchanged. |
| 8 | Navigate to another studio screen while that question is working. Open Studio activity. | Work continues. Its exercise is listed; when done it becomes Answer ready, or clearly Needs attention / Question pending. |
| 9 | Return through the activity link and inspect the response and citations. | The saved question/result is available. Sources consulted labels reused references. Coverage remains honest; the answer addresses the new question rather than blindly quoting old notes. |
| 10 | Mark the activity reviewed; leave and return. | Reviewed terminal activity stays acknowledged. New results continue to appear. |

## Phase 2 — misuse risk map, fix loop only when requested

| Priority | Probe | Required behaviour |
| --- | --- | --- |
| 1 | Another world/account's source or activity ID | Server refuses; no private content or acknowledgement crosses ownership. |
| 2 | Chapter edited after reading | Historical citations retain their saved prose. A new question excludes that chapter's stale reference; unchanged chapters remain reusable. |
| 3 | Stop/resume, repeated Continue, reload while working | One original user question, one active worker, completed notes preserved. Resume uses the selected continuation preference. |
| 4 | Turn off automatic continuation before a whole-source request | At the batch boundary, the question is explicitly pending, progress is retained, Continue is obvious. The pause is not presented as an answer. |
| 5 | Invalid/unavailable model or unsupported effort | Clear saved failure, no silent model/provider fallback. API effort remains provider default; local effort support depends on the model. |
| 6 | Unsaved details when opening a citation | Discard confirmation protects those details; rejecting it keeps your writing and place. |
| 7 | Source disclosure, composer settings, source notes while polling | Open/closed state and input survive. Inspect on a phone as well as desktop; source links select the Details pane. |
| 8 | Close the browser entirely, then return | The background process is independent of the page; persisted in-app activity is available on return. No desktop push or email is claimed. |

## Phase 3 — read-only verification

```sql
SELECT id, exercise_id, message_id, response_id, model, status,
       JSON_EXTRACT(request_context, '$.effort') AS effort,
       JSON_EXTRACT(request_context, '$.auto_continue') AS auto_continue,
       acknowledged_at
FROM studio_assistant_turns WHERE exercise_id = 13 ORDER BY id;

SELECT s.id, s.turn_id, s.chapter_id, s.chapter_revision,
       JSON_LENGTH(s.reads) AS saved_sections
FROM studio_assistant_sources s
JOIN studio_assistant_turns t ON t.id = s.turn_id
WHERE t.exercise_id = 13 ORDER BY s.turn_id, s.position;
```

Inspect `reads[*].reused_from` and remapped source citations in a new request.
Compare the original message ID/count before and after continuation. Confirm no
chapter content/revision, canon record or connection default changed through reading.
No emails, native push or paid API fallback should occur.

## Findings and verification evidence

| ID | Location | Finding | Resolution / retest |
| --- | --- | --- | --- |
| 1 | Reading | No working icon | Working indicator plus named progress; verify live updates. |
| 2 | Sources consulted | Polling snaps disclosure shut | Preserve disclosure element state across morphs. |
| 3 | Composer | No model/effort preference | Per-question model override, local effort, remembered exercise preferences. |
| 4 | Background | Unclear whether it is safe to leave | Background explanation and persistent studio-wide activity. |
| 5 | Details | Closing loses the panel | Explicit reopen control. |
| 6 | Timeline/List | Empty views look unchanged | Distinct headings, selected state and view-specific empty copy. |
| 7 | Reading pause | 24-section limit leaves question unanswered | Automatic continuation by default; optional manual batches explain the pending question. |
| 8 | Citation | Page navigation breaks discussion | Open source through the workspace action; retain unrelated panel state. |
| 9 | Novel reference | New questions reread everything | Reuse versioned reading within the author's world; revisit prose for new-question gaps. |

Rolled-back runtime probes reused all 33 existing saved ranges in another exercise,
remapped citations, excluded a changed chapter and refused cross-world sources.
A bounded whole-reading probe crossed batch limits automatically and respected
manual pause. Model/effort reached the provider adapter without changing the stored
connection default. Activity acknowledgement persisted. Actual sandbox request
returned 200 with the new controls. No automated tests were added.

Record remaining observations with exact exercise/turn/source IDs and a screenshot;
separate literary-quality issues from coverage or interaction defects.

Browser follow-up used the main account, as requested. The temporary account and
its limited permissions were removed. On the real discussion, opening a citation
preserved an unsent scratch message and the expanded source disclosure; the scratch
text was then cleared without sending. Details closed/reopened correctly. Main-
account disposable prose verified distinct empty Timeline/List views, sources
remaining open through live polling and a simulated pause, local effort becoming
available when its connection was selected, studio activity on another screen,
fixture-only acknowledgement, and a phone-sized source deep link selecting Details.
No original question was resent, provider called, real activity acknowledged or
manuscript changed during these browser checks.
