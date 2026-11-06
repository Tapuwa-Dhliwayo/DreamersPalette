# Build an encyclopedia through a private assistant connection

Prepared 2026/11/05. Local only. Timebox: 35–45 minutes.

## Goal, risk and stop condition

Verify one author can discuss, refine, review and save encyclopedia changes while
seeing honest usage, and another author cannot access that material or connection.
High risk: private writing/credentials, mistaken canon and accidental provider use.
Stop immediately on foreign content, exposed credentials or unreviewed canon writes.
Finish after the journey, selected risk probes and read-only verification.

## Accounts, data and reset

Use the existing author in one session and a separate normal verified author in
another. Both need dashboard.access, worlds.view, worlds.create and worlds.update.
Do not grant administrator privileges. Use separately owned disposable worlds and
exercise conversations; never edit exercise 13's original question just to test.

Use the existing local subscription only on its configured author account, or an
explicitly configured own API connection. API billing is separate. Do not enter a
real key into a screenshot or findings record. Do not copy another author's key.
No reset is required or authorised. Keep original manuscripts, sources, connections
and permissions. Archive disposable review records through normal recovery controls;
physical fixture cleanup is a separate explicit operation.

## Phase 0 — shape review

Before clicking, inspect the connection user_id/budgets, exercise world ownership,
turn request_context/context_manifest, request-ledger ownership and nullable totals.
Confirm target revisions came from supplied records, proposal revisions came from
captured board state, and evidence basis is an assistant attribution to review.
Accepted history must retain evidence and before/after snapshots. Request pricing
must be the historical connection-model rate, not a later rate or override model.
A null quota/token/cost is unknown, not zero. No foreign reading can be reused.

## Phase 1 — intended journey, notes mode

| Step | Action | Expected result / observe |
| --- | --- | --- |
| 1 | Open Connections as Author A. Inspect a saved connection, budgets and usage. | Only A's connections. Saved key never prefilled. Subscription/API billing distinguished. Account allowance is reported or explicitly unavailable. |
| 2 | Create a disposable world/exercise. Choose a connection and Build encyclopedia. Ask for a fictional place, a character and their relationship. | Saved question; reviewable proposals, evidence basis and any uncertainty. No encyclopedia changes yet. |
| 3 | Expand proposed changes and collect them. | Editable board pieces. Discussion and open disclosures retain their place. Recollecting reuses pieces. |
| 4 | Discuss one board proposal: ask for a specific refinement. Keep Build encyclopedia selected. Collect the response. | Same proposal revised using its captured revision, without duplicating or silently accepting it. Evidence remains inspectable. |
| 5 | Select proposed records and their relationship, then Review canon. | Exact content, human-readable entry types and relationship subjects. Resolve uncertainty before acceptance. |
| 6 | Accept selected changes, then Open record from History. | Records and relationship saved once. Entry type, aliases and notes render correctly. History retains attribution. |
| 7 | Ask to expand that existing entry's notes without changing its aliases/type. Collect, review, accept. | Before/after comparison; omitted fields preserved; same record updated. |
| 8 | Open Discussion usage and connection usage. | Reading requests and final responses counted separately. Tokens/cost show reporting coverage. Earlier untracked requests are excluded. No invented subscription balance. |
| 9 | Undo the latest acceptance. Leave and return. | Previous writing restored; Reversed acceptance remains visible; Undo no longer offered for that change. |
| 10 | In B's session open Connections and B's own world. Try A's world URL. | B has independent connections and no server-owner local-login option. A's world is refused; no private names, activity or usage appear. |
| 11 | Repeat proposal review and usage inspection on a phone-sized screen. | Readable fields, recoverable panels, working review/Undo and no page-width overflow. |

## Phase 2 — misuse risk map, fix loop only when requested

| Priority | Probe | Required behaviour |
| --- | --- | --- |
| 1 | Foreign world, connection, chapter, source citation, proposal or turn ID | Server refusal; no content, credential, usage or acknowledgement crosses ownership. |
| 2 | Change a target or board proposal while an assistant response is working | Stale collection/review refused; author writing preserved. |
| 3 | Recollect old refinements, double accept, repeat Undo | No duplicates, no older proposal overwriting newer work, no repeated reversal. |
| 4 | Similar name or deliberate update | Existing-name warning; explicitly choose the existing record. No silent merge. |
| 5 | Set a tiny request budget; request whole reading | Next request stops at budget; saved question/notes retained. Raising budget allows an explicit retry. |
| 6 | Disconnect during reading; revoke capability; start questions in several exercises | Next request refuses disconnected/unauthorised work; per-author concurrency enforced. No paid fallback. |
| 7 | Missing/interrupted usage, zero reported cost, model override or changed prices | Unknown stays unavailable; zero stays zero; historical estimates stable; wrong-model prices not reused. |
| 8 | OpenRouter key limit unavailable or check fails | Explicit unavailable/failed state, checked time and provider link. Key cap never labelled total account balance. |
| 9 | Working worker versus interrupted activity refresh | Live work is not marked failed. Stale attempt cleanup cannot cancel a newly resumed attempt. |

## Phase 3 — read-only verification

Inspect studio_assistant_connections for owner/budgets without selecting credential.
Inspect studio_assistant_requests by user_id, connection_id and turn_id: purpose,
model, status, nullable usage, captured pricing and finished_at. Request count must
include reading attempts and failed starts, without duplicated cumulative totals.
Compare A and B totals. Inspect sandbox provenance, accepted before/after history
and canon_changes.reversed_at. Confirm original chapter revisions/writing unchanged.

Useful queries (replace IDs with this session's disposable records):

```sql
SELECT user_id, connection_id, turn_id, provider, model, purpose, status,
       input_tokens, output_tokens, cache_read_tokens, estimated_cost_usd,
       reported_cost_usd, finished_at
FROM studio_assistant_requests WHERE user_id = :author_id ORDER BY id;

SELECT id, exercise_id, user_id, reversed_at, changes
FROM studio_canon_changes WHERE exercise_id = :exercise_id ORDER BY id;
```

## Findings and evidence

| Finding | Resolution / retest |
| --- | --- |
| Undo restored content but did not save the reversal marker | Explicit model-owned marker; runtime content/marker/repeat checks and browser retest passed. |
| Activity's broad joined UPDATE could deadlock during long transactions | Bounded IDs, row locks, stale-state recheck, matching run cleanup and transaction retries. |
| Raw entry type IDs in canon preview | Human-readable type names; Open record added to history. |

Completed engineering evidence: transactional two-author isolation, encryption,
request budgets, duplicate/revision/Undo probes; actual second-author Connections
200/private-world 403; one real neutral Codex proposal-to-encyclopedia request with
reported tokens; desktop/mobile browser acceptance, record display and Undo.
Disposable browser fixtures removed. Original question was not resent. No automated
tests added. Real paid API billing/streaming and live OpenRouter allowance remain
unverified until an author configures their own API connection.

Record author findings with world/exercise/turn IDs and screenshots excluding keys.
Suggested follow-up: paid-provider connection review, then novel-grounded literary
quality and citation review using deliberately selected manuscript sources.
