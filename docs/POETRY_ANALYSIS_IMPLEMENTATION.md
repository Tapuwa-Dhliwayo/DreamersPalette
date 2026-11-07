# Poetry analysis implementation

Updated: 2026/11/07.

## Delivery

Poems → Analyse opens `/dashboard/poems/{id}/analysis`. The existing React editor
also offers Save and analyse, preserving its normal failed-save/local recovery
behaviour. Analysis is a React workspace backed by private Laravel APIs. It has
multiple conversations, responsive poem/conversation panes, model and supported
thinking-effort selection, explicit comparison selection (four maximum), author
notes, source inspection, revision selection, background progress, stop/retry,
archive/restore and dashboard assistant activity.

Poem lineation is displayed verbatim. Responses render Markdown. Conversation
drafts use session storage scoped to poem/conversation. The latest saved model
selection is recovered from the conversation; credentials never enter React.

## Ownership

New records:

- `PoemAnalysisConversation`: owns messages/turns and archive behaviour.
- `PoemAnalysisSnapshot`: immutable title, Markdown and collection context,
  deduplicated per poem by SHA-256 fingerprint.
- `PoemAuthorNote`: explicit reusable author explanations, with optional source
  user-message attribution and recoverable retirement.

`PoemAnalysis` coordinates poetry context and execution. Existing provider,
model-catalogue, connection, request ledger and turn infrastructure are reused.
World source reading, proposals and canonisation stay with world exercises.
Shared assistant classes retain their existing Worldbuilding namespace to avoid
an unrelated file move/refactor. `AssistantTurn::subject()` / `authorId()` /
`activityUrl()` provide the small shared ownership boundary.

The new migration makes exercise ownership nullable on shared messages/turns and
adds an indexed poem conversation identifier. World records keep their IDs and
existing ownership. Connection access is available to authors with worlds.update
or poems.update; poetry conversations additionally require exact poem/conversation
ownership. Public visibility of a poem never grants access to its discussions.

## Context and revision behaviour

Each question stores its exact prompt, instruction version, main poem snapshot,
comparison snapshots, author notes, connection revision/model and thinking effort.
Earlier main-poem versions accompany labelled history. New source versions are
visible in the conversation. Inspection can show an earlier main poem or a turn's
comparison texts/author notes. The current server fingerprint must match what the
browser loaded before a new question can be accepted.

Previous-version discussion deliberately retains the last analysed snapshot;
latest-version discussion captures the current saved poem and collection context.
Pasting writing into a message never edits or publishes the poem. Save and analyse
is the editor's explicit save boundary.

The prompt ceiling is 180,000 bytes, with a 12,000-character question boundary and
four comparison poems. Over-limit context is rejected without truncation; start a
new conversation or reduce attachments. Automatic summarisation, whole-collection
reading, audio critique, AI text application and publishing changes are outside
this increment.

## Execution and recovery

Questions are persisted transactionally before a detached existing Artisan worker
is launched. Request UUIDs prevent duplicate accepted turns. A shared per-author
lock protects concurrency accounting across worlds and poetry. The provider
request ledger enforces connection budgets and records usage.

Workers claim a queued turn with a unique run identity. Response writes lock and
recheck that identity/status; stopped or superseded workers cannot mutate saved
answers. Stop retains the last persisted partial response. Retry creates a fresh
attempt linked to the original, using its exact prompt and snapshot. A changed
connection revision requires a new question. Polling detects abandoned work after
the configured provider timeout plus 60 seconds. Browser polling is 1.5 seconds
while working and 10 seconds otherwise; dashboard activity polls every 10 seconds.

The existing usage completion method now preserves an already recorded terminal
status while an older provider process unwinds. Both world and poetry requests use
this guard.

## Three execution/verification rounds

No automated tests, test-suite changes or manual testing charter were added.
Temporary PHP runtime scripts outside the repository exercised the real Laravel
HTTP kernel, policies, models, migrations and worker with disposable SQLite data.
Every round rolled back its authored fixtures. This is direct application
execution, with external provider responses simulated rather than billed calls.

### Round 1 — core request and worker

11 checks passed: lineation preservation, credential exclusion, poetry-only model
access, UUID idempotency, direct poetry ownership, worker completion, saved
question/answer reload, usage ledger, shared author scope and source snapshots.

### Round 2 — revisions, context and boundaries

23 cumulative checks passed. Added stale-fingerprint rejection without a new
turn, explicit previous-version discussion, revised source capture, historical
immutability, retained prior-version/author-note context, comparison snapshots,
note retirement, input validation, cross-author conversation/connection/comparison
rejection, and archive/restore history.

### Round 3 — interruption and integration

41 cumulative checks passed. Added dashboard activity/acknowledgement, latest
revision selection, cancellation before launch and while running, new-attempt
retry with unchanged prompt, late-output rejection, terminal usage preservation,
stale-work expiration, world-turn ownership/usage/activity compatibility, shared
world/poetry concurrency, request-budget rejection before transport, model-level
snapshot immutability, private/public reader separation, and simulated SSE through
the actual provider adapter with usage capture.

These checks validate the exercised boundaries; they do not establish production
MySQL concurrency behaviour or an end-to-end world conversation generation run.

## Other checks

- Focused ESLint: passed for all changed JavaScript/JSX files.
- Full `npm run lint`: two errors remain in untouched CollectionEditorPage.jsx
  and HomePage.jsx; an existing unused-disable warning remains in Logo.jsx.
- `npm run build`: passed.
- Focused Pint check: passed for changed PHP files.
- PHP syntax checks, Composer validation and `git diff --check`: passed.
- All original migrations, plus this increment, ran on disposable SQLite after
  registering a scratch-only utf8mb4_bin collation. Production migration files
  were not altered for SQLite.

## Attention before use

The normal MySQL migration attempt was blocked by sandbox permissions: TCP
connection failed and Unix socket access returned Operation not permitted.
**The new migration is NOT applied to the working MySQL database.** The feature
cannot be used there until its additive migration is applied from a normal local
shell. Do not run pending unrelated migrations indiscriminately; use:

```bash
php artisan migrate --path=database/migrations/2026_11_07_000001_add_poem_analysis.php
```

Headless Chrome launch was also blocked (setsockopt: Operation not permitted).
Desktop/mobile layout, keyboard behaviour, browser storage, scrolling and actual
React interactions were reviewed in source, not exercised in a browser. They need
browser verification after the migration. No real local-subscription or paid API
generation was sent. Real provider availability and model quality remain
unverified. Detached process launch was intercepted in the scratch rehearsal;
worker execution itself ran directly, so real process startup needs checking too.

Unrelated world-authoring/timeline changes were already present in this worktree
and were preserved. No commit, push, deployment, normal database refresh or
production mutation was performed.

## Review corrections — 2026/11/07

This section supersedes the initial migration/browser status above. Tapuwa's review
confirmed the additive migration is applied on local MySQL, its existing indexes
survived, and no poetry conversations/turns existed there at review time. No schema
changes or migration commands were needed for these corrections.

All thirteen review items were addressed:

- Retry accepts only the latest interrupted turn. Reusing an accepted request UUID
  returns the same attempt; an older failed original cannot create another billed
  attempt after a retry or later question. Failure explanations appear beside
  their author question; only the latest interrupted attempt offers Retry.
- World activity URLs are relative, selecting the full-page anchor branch.
- Usage estimates update as usage arrives and at completion, even if cancellation
  or expiration already recorded a terminal status. Completion preserves that
  status and its finish time.
- Polling reschedules after transient failures, clears its own recovered error,
  prevents overlapping polls and refreshes immediately after send/retry.
- Instruction version 2 names `poem_under_discussion`, states whether a newer
  saved version exists and excludes that selected snapshot from earlier versions.
  It does not send or imply knowledge of the newer text during older-text discussion.
- Both activity menus use `AssistantTurn::outstandingFor()` and exclude subjects
  the author can no longer update, including archived poems.
- Explicit New analysis uses `conversation=new`, preventing automatic reselection.
- Edited author notes lose exact-message attribution when their wording changes.
  Retired notes now have an authorised restore endpoint and UI action.
- Local Claude world requests retain their original worldbuilding system prompt;
  the poetry prompt applies only to poetry_analysis requests.
- Streaming cancellation checks are bounded to one turn check per 0.5 seconds and
  full connection/ownership checks per two seconds, with a forced final check.
  Response persistence still checks the run identity/status under a row lock.
- Stalled-work expiry and stopping/request cleanup now belong to AssistantTurn;
  poetry, Sandbox and both activity menus reuse them. Conversation archiving stops
  each active turn and closes its running request records.
- Shared activity APIs moved to AssistantActivityController.
- UUIDs use cryptographic getRandomValues, which works without secure-context
  randomUUID on a plain-HTTP development host.

Verification: 53 cumulative direct PHP HTTP-kernel/model/worker checks passed on
isolated SQLite, including repeated retry rejection/idempotency, cancelled cost
estimates, previous-version metadata, changed attribution, note restore, relative
world links, archive ledger closure and archived-poem activity consistency. The
scratch limiter was explicitly isolated to the array cache. Five direct JavaScript
checks exercised the actual polling effect's failure recovery, error clearing,
fast scheduling, immediate refresh, unmount cleanup and UUID generation. No test
suite or repository test files were added.

A browser connection was available during this correction pass. On the existing
local account, opened Poems → Analyse, inspected the empty analysis workspace,
connection/model controls and activity menu, checked mobile poem/conversation pane
switching while retaining an unsent draft, and inspected desktop pane structure.
Cleared the temporary draft and restored the viewport. No poem, note, conversation
or provider response was created by that browser check. Populated-thread retry,
long-response scrolling, keyboard coverage, real worker startup, MySQL races and
live model generation are still unverified end to end. No real provider request,
production mutation, commit, push or deployment was performed.

## Second review follow-up — 2026/11/07

The current JSX already selected the last matching attempt with filter(...).at(-1)
when the second review arrived. It now uses explicit findLast(...). Direct
execution of the actual JSX lookup confirmed the second failure supplies its own
error and remains retryable; running/completed retries do not show the original
failure box. A three-attempt runtime sequence (failed, failed, completed) also
passed on the same question.

Shared stalled-turn handling now selects timeout wording by subject, restoring the
original Sandbox wording for worlds regardless of which activity screen expires
it. Unknown world stop IDs again behave as the previous no-op. Stopping known
world turns still closes their request records deliberately.

AssistantTurn::failRun() now handles failed-run cleanup under the turn/run lock,
saving any remaining received text, recording a terminal failure and closing still
running request records atomically. Poetry calls it when access disappears before
streaming or at the final forced check, and on exceptions. Cancelled/superseded runs
remain untouched. An end-of-stream poem-archive fixture confirmed that the final
unflushed tail survives and the turn immediately leaves the active concurrency
count, without waiting for expiration.

59 cumulative isolated PHP runtime checks passed, including six new checks for
these findings; the existing five polling/UUID checks and two direct JSX-attempt
checks passed. Focused ESLint/Pint, PHP syntax, production build and diff whitespace
checks passed. No real provider generation, normal MySQL fixtures, production
changes or git commit were performed. The populated-thread browser, real detached
worker and concurrent MySQL limitations described above still apply.
