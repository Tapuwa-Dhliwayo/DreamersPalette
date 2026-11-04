# Develop an idea in conversation and make selected changes canon

Prepared: 2026/11/04. Local DreamersPalette only. Timebox: 40–45 minutes.

## Risk and goal

**High:** private writing is supplied to an explicitly chosen provider, and accepted
proposals change established world records. Confirm that a natural conversation
can become connected sandbox ideas, a naming shortlist and a reviewed canon change,
without losing uncertainty, creating duplicates or overwriting newer writing.

Stop when the normal journey, one or two chosen misuse probes and read-only state
verification are complete, with observations recorded. Stop earlier for a privacy
boundary failure, lost writing or a blocked core workflow.

## Accounts and starting data

- Use your existing verified local author account, ID **1**.
- World **1**, **Asprino 9**, is the copied Anubis baseline. The implementation did
  not add invented canon to it. Your existing people and writing remain.
- Local connections **My ChatGPT** and **My Claude** are configured for the existing
  local CLI logins. No API key is required for this session. These use subscription
  allowances. OpenRouter/direct API connections have separate billing and are not
  needed for this charter.
- Ownership plus `worlds.view` and `worlds.update` are required. The sandbox is an
  authoring surface. Connection settings also require `worlds.update`.
- A second account/world is only needed if you choose the cross-author risk probe.
  Do not change the real account's password or permissions for this session.

Start at <http://127.0.0.1:8000/studio/worlds/1/sandbox>. Sign in normally.
Connections: <http://127.0.0.1:8000/studio/assistant-connections>.
The local server is running as transient user service `dreamers-studio.service`.
It is not enabled at boot. To start it in a future local session, use the project's
usual local server command (`php artisan serve`). Both provider CLIs must be on
this user's machine and signed in. Local binary locations are in ignored `.env`.

## Workflow inventory and review order

This charter covers one workflow: **conversation → sandbox → reviewed canon**.
Other workflows should get separate focused sessions if this first pass is useful.

| Priority | Workflow | Main records | Risk |
| --- | --- | --- | --- |
| 1 | This exercise-to-canon journey and ownership | exercises, ideas, links, canon changes | High |
| 2 | Provider connection, interruption and billing boundaries | connections, turns, messages | High |
| 3 | Existing-record updates, stale review and Undo | canon changes, world revisions | High |
| 4 | Naming guidance, shortlist and established-character rename | conventions, ideas, aliases | Medium |
| 5 | Longer conversations, summaries and provider handoff | messages, context manifests, summaries | Medium |

## Fixture reset

Do not refresh or rebuild the database. Each repeat uses a new exercise named
**Sandbox review — crossing 1**, then **crossing 2**, and so on.

For a completed pass, use **History → Undo acceptance** before changing its
accepted records elsewhere. Then use **Exercise & context → Archive exercise**.
The authored conversation and proposals remain recoverable. Created canon records
are archived by Undo; existing records are restored only if their revisions still
match. If Undo refuses a newer edit, record the affected IDs and stop that reset;
do not force it or use database deletion. Existing Asprino writing is preserved.

## Phase 0 — shape review, before clicking

Read the stored structures with the agent, without changing them. Confirm:

- `studio_world_exercises.world_id` identifies the world; messages and ideas belong
  to that exercise, not to a provider session.
- `studio_assistant_turns` retains provider/model, request UUID, status, exact prompt,
  context manifest and validated proposal batch. Messages are ordered by numeric ID.
- `studio_sandbox_ideas` retains source message, origin, revision, target key/source
  revision, uncertainty, scenario, sequence and separate board coordinates.
- `studio_sandbox_links` retains meaning and direction, independently of its layout.
- `studio_canon_changes.changes` contains before/after snapshots and accepted record
  identities. It must still explain the acceptance after later board edits.
- Naming drafts have `reviewed=false`; they must not be sent as approved conventions.
- An accepted working summary has `summary_through_id`. Older source messages remain
  stored; request manifests identify the message IDs actually included.
- Provider secrets are encrypted and hidden from model serialization. CLI credentials
  are not stored in world records or passed as writing context.

Implementation: `Sandbox`, its five world Form Objects, `AssistantConversation`,
`ExerciseContext`, `SandboxProposals`, `Canonisation`, `WorldContext`, and the models
under `app/Models`. Routes are `studio.world.sandbox` and
`studio.assistant-connections`. Both schema additions are forward migrations.

## Phase 1 — intended workflow, notes mode

Run this as one uninterrupted author journey. Record findings; do not fix code
mid-pass. The material below is deliberately a review exercise, not new story canon.

1. **Check the two local connections.** Open Connections and choose **Check login**
   for each. Expect “Local login available.” No API key or provider purchase should
   be requested. Return to Asprino's Sandbox.
2. **Create the exercise.** Title it **Sandbox review — crossing 1**. Set its purpose
   to exploring a young engineer, a damaged bridge and two possible outcomes.
   Include one existing character as context; leave chapter checkboxes unselected.
   Save. Expect the three working areas and the chosen exercise title.
3. **Talk naturally.** Choose My Claude and Conversation. Ask how an engineer's
   warning might change a commander's decision, explicitly saying both outcomes
   are undecided. Expect a reply attributed to Claude. Reload after receiving it;
   the message and reply must remain.
4. **Collect proposals.** Choose Conversation + proposed ideas. Ask for one invented
   review character and two event possibilities, clearly separate from existing
   canon, with the outcomes still undecided. Choose **Collect proposals** when the
   response finishes. Expect editable pieces, assistant provenance and source links.
   Collect the same batch again: the piece count must not increase.
5. **Shape the board.** Open a piece, edit its notes and save. Move it with the drag
   handle, then with an arrow key while its handle is focused. Reload and confirm
   both details and position persisted. Try **Undo move** after a subsequent move.
   Saving a board piece must not create a world character or event yet.
6. **Connect the pieces.** Open Connect. Add character → event with meaning
   **Character participates in event** and label **Witnesses**. Add an ordinary
   board connection between the two possibilities. Expect arrows and readable
   endpoint names in the connections panel. Board-only links are not canon relations.
7. **Explore sequence.** Make both proposed events use scenario **Bridge version**
   and lane **The crossing**, with positions 10 and 20. In Timeline use Earlier /
   Later, or drop one event onto the other. Expect the sequence to save; fictional
   dates remain undecided. Fork one event into **Ferry version** and use the scenario
   filter. No competing version should be deleted or silently selected for canon.
8. **Review naming guidance.** Open Naming and import drafts from the pinned chat.
   Expect three unreviewed conventions. Open one, correct its guidance if necessary,
   tick the review checkbox and save. Drafts stay excluded; reviewed guidance is
   eligible for the next request. Do not mark uncertain linguistic claims verified.
9. **Generate a shortlist.** Choose Naming shortlist and request three invented
   review-character names fitting the convention you reviewed. Collect the batch.
   Open a name, choose the sandbox character under **Assign to sandbox character**,
   and use it. Expect only that proposed character's name to change. Similarity
   notices are advisory; they must not prevent intentional resemblance.
10. **Switch providers without leaving the exercise.** Choose My ChatGPT and
    Conversation. Ask which names/outcomes were proposed and what remains undecided.
    Expect the earlier material and its tentative status to carry over, including
    names from a batch you have not collected. Inspect Context sent: only selected
    established records, included chapters and reviewed conventions should be listed.
11. **Review canon.** Select the proposed review character and one event. First leave
    an uncertainty populated and try Review canon: expect a clear refusal. Resolve
    or clear it deliberately, save, then review again. Read the exact proposed
    content and participation link. Unselected alternatives remain in the sandbox.
12. **Accept once.** Choose **Make these changes canon**. Expect accepted-state
    markers and one history entry. Open Characters and Timeline to confirm the
    created records and participation. Existing manuscript text, public writing
    and finished snapshots must not change.
13. **Undo the acceptance.** Before editing the created records elsewhere, return
    to the exercise's History and choose Undo acceptance. Expect the newly created
    world records to be archived and the original ideas to become editable again.
    History remains and is marked reversed. Finish with the fixture reset above.

## Phase 2 — misuse and probing, fix-loop mode

Choose the risks that look most revealing; this is a risk map, not a second scripted
journey. Keep the session inside its timebox. The agent records the finding, fixes
only that finding if requested, and hands it straight back for retest.

| Rank | Where it can break | What to attack | Required behaviour |
| --- | --- | --- | --- |
| 1 | Private world and connection ownership | Another world's exercise URL or another user's connection ID | Server refuses access; no writing or credentials exposed |
| 2 | Stale canon review | Change a proposal or established record in another tab after review | Acceptance is refused and requires a fresh review |
| 3 | Unsafe Undo | Edit an accepted world record, then Undo its acceptance | Newer content survives; reversal is refused |
| 4 | Duplicate execution | Double Send, repeat Collect, repeated acceptance | One active turn; collection and acceptance reuse their identities |
| 5 | Interrupted conversation | Stop, reload while responding, or disconnect its connection | Saved messages remain; cancelled/failed status is explicit; no silent fallback |
| 6 | Context confusion | Unreviewed convention; unselected chapter; uncollected shortlist | Only intended material is sent; shortlist context is retained |
| 7 | Timeline ambiguity | Equal positions, another scenario, cyclic before links | Sequence is not elapsed time; cross-scenario moves and contradictory canon order are refused |
| 8 | Unsaved details | Switch pieces, reload, or submit an invalid detail form | Warning or validation appears; failed submission does not claim success |

A failed provider request is not automatically retried against another account or
paid API. Any existing API connection must be selected explicitly.

## Permission checks

Complete the author pass first. If a second account is available, use a separate
browser session and request world 1's sandbox URL directly. Expect 403/404, not a
readable exercise. An owner without `worlds.update` also cannot use this authoring
surface. Repeat a server action if inspecting requests with the agent; hiding a
button is not the permission check. Disconnecting a connection does not log the
user out of their provider's CLI or erase conversation history.

## Phase 3 — verify resulting state, read-only mode

Record the exercise ID from the URL. Replace `EXERCISE_ID` below with that numeric
ID. These queries are read-only; never select the credential or full prompt column
into a shared report.

```sql
SELECT id, world_id, title, revision, summary_through_id, deleted_at
FROM studio_world_exercises WHERE id = EXERCISE_ID;

SELECT id, role, CHAR_LENGTH(content) AS characters
FROM studio_assistant_messages WHERE exercise_id = EXERCISE_ID ORDER BY id;

SELECT id, provider, model, mode, status, response_id, request_uuid,
       started_at, finished_at
FROM studio_assistant_turns WHERE exercise_id = EXERCISE_ID ORDER BY id;

SELECT id, kind, title, origin, source_message_id, source_token, revision,
       alternative, lane, position, target_key, target_revision, canonised_at,
       deleted_at
FROM studio_sandbox_ideas WHERE exercise_id = EXERCISE_ID ORDER BY id;

SELECT id, source_id, target_id, kind, label
FROM studio_sandbox_links WHERE exercise_id = EXERCISE_ID ORDER BY id;

SELECT id, request_uuid, reversed_at, JSON_LENGTH(changes) AS captured_changes
FROM studio_canon_changes WHERE exercise_id = EXERCISE_ID ORDER BY id;

SELECT id, world_id, name, reviewed, revision
FROM studio_naming_conventions WHERE user_id = 1 ORDER BY id;
```

For the accepted event, inspect `studio_world_event_people`: its event/person pair
must occur once. After Undo it must no longer be an active added participation;
newly created character/event rows remain archived. Repeated collection uses the
same `source_token`. Acceptance UUID is unique. No new public book/chapter, email,
notification or finished-draft row should be created by this workflow.

Board x/y are layout only. Timeline positions 10 and 20 express sequence; they do
not mean ten days apart. No travel-distance or calendar arithmetic is claimed by
this sandbox move.

## Expected final state and evidence

- Original Asprino characters and writing remain unchanged.
- The review exercise retains its conversation, ideas, links and one reversed
  acceptance; archive it to end the pass.
- Exactly the convention you reviewed is enabled as naming context.
- No active review turn remains. No API fallback or publishing occurred.

Capture the exercise URL/ID, chosen connection/model, relevant message and change
IDs, screenshots of confusing behaviour, and the exact refusal text for any failed
probe. Do not capture keys or provider credential files.

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| --- | --- | --- | --- | --- | --- | --- |
| | | | | | | |

Severity: Critical / High / Medium / Low. Status: New / Fixed / Documented /
Needs investigation / Ready for retest / Not a defect.

## Follow-up sessions and current limits

A separate longer-conversation session should generate a summary using
**Summarise discussion**, review its reply, accept **Use as working summary**, then
switch providers. The summary must cover only the earlier messages, keep those
messages visible, and retain later discussion. Requests over the configured
context-size limit are refused rather than silently trimmed.

Further focused sessions can cover established-character renaming/alias retention,
map and magic version preservation, alignment/comparison setup, and provider usage
limits. New maps are blank until edited in Maps; alignment placements, comparison
cells, magic rules, arc beats, knowledge perspectives and investigation evidence
continue to use their existing specialised tools. Sandbox proposals currently
create/update those records' supported core fields; they do not replace every
specialised editor.

Local Codex currently publishes completed message chunks; Claude publishes text
deltas. Both appear through saved-message polling. Every request receives a
DreamersPalette context handoff rather than resuming a hidden native provider
session. API streams are implemented but have not been exercised against a paid
account. Live subscription quota/reset counters are not implemented. Unsaved detail
forms have leave protection, not a separate browser recovery archive.
