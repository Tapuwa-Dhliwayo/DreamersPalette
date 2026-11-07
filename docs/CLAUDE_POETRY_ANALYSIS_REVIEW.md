# Claude review prompt — poetry analysis

Review the poetry analysis addition in this repository. Start with AGENTS.md,
docs/agent/PROJECT_CONTEXT.md and docs/POETRY_ANALYSIS_IMPLEMENTATION.md. Inspect the
actual code and challenge the implementation report; do not treat its claims as
proof. This is a review request, not permission to edit, reset databases, deploy,
send paid model requests or create tests/charters.

The feature brings a poet's analysis conversation into the poetry workflow:
write/save → analyse with collection context → discuss authorial intent → revise
→ continue with the exact analysed version identified. It reuses existing model
connections, model discovery, usage budgets and background assistant turns. It
must preserve worldbuilding behaviour and private author boundaries.

There are unrelated pre-existing world-authoring and timeline changes in the
working tree. Do not attribute that entire diff to poetry. Use this scope:

New files:
- app/Services/Poetry/PoemAnalysis.php
- app/Models/PoemAnalysisConversation.php
- app/Models/PoemAnalysisSnapshot.php
- app/Models/PoemAuthorNote.php
- app/Policies/PoemAnalysisConversationPolicy.php
- app/Http/Controllers/Content/AssistantActivityController.php
- app/Http/Controllers/Content/PoemAnalysisController.php
- app/Http/Requests/Content/PoemAnalysisRequest.php
- database/migrations/2026_11_07_000001_add_poem_analysis.php
- resources/js/services/poemAnalysis/index.js
- resources/js/utils/requestUuid.js
- resources/js/hooks/usePoemAnalysis.js
- resources/js/pages/dashboard/PoemAnalysisPage.jsx
- resources/js/components/dashboard/AssistantActivity.jsx

Shared integration changes:
- app/Models/Poem.php, AssistantTurn.php, AssistantRequest.php
- app/Policies/AssistantConnectionPolicy.php
- app/Services/Worldbuilding/AssistantModels.php, AssistantProviders.php,
  AssistantUsage.php
- app/Console/Commands/RunAssistantTurn.php
- app/Livewire/Studio/AssistantConnections.php, AssistantActivity.php
- routes/api.php and routes/web.php
- resources/js/app/router.jsx and routes.js
- resources/js/layouts/DashboardLayout.jsx
- resources/js/pages/dashboard/Poems.jsx and PoemEditorPage.jsx
- resources/js/components/dashboard/ContentList.jsx
- resources/js/index.css
- resources/views/components/studio/header.blade.php
- resources/views/livewire/studio/assistant-activity.blade.php

Review in this order:

1. Trace a complete initial question, worker response, author explanation, saved
   poem revision and follow-up. Verify the correct version is selected every time,
   earlier snapshots remain exact and history cannot misrepresent older text as
   the current poem. Inspect collection changes and comparison/author-note history.
2. Check exact author ownership for every API, source snapshot, message, note,
   connection, model discovery, activity and retry path. Public poems must not
   expose private analysis. Verify poetry-only authors need no world permission.
3. Analyse races: duplicate submissions, queued cancellation, cancellation during
   streaming, late writes, retry after follow-ups, archive/restore, disconnect or
   connection revision changes, stale work, user/subject locks, connection budgets
   and concurrent world plus poetry requests. Check lock ordering under MySQL.
4. Review the additive migration against actual MySQL schema/history. Nullable
   exercise ownership must not break any shared query, command or activity path.
   No cascade deletion, historical migration rewrite or destructive refresh.
5. Inspect frontend lifecycles: request and polling races, unmounts, model changes,
   duplicate send prevention, first-send failure, request UUID persistence,
   conversation switching, tab-scoped recovery, partial responses, stale source
   handling, model/effort restoration and scrolling. Look for write-loss bugs.
6. Verify desktop/mobile usability and accessibility if the environment allows:
   overflow, keyboard focus, pane switching, composer reachability, source
   inspection, archive confirmation, activity navigation and long Markdown.
7. Assess critique instructions: sound/page equality, independent criticism,
   deliberate syntax, grounded interpretations, speaker/author separation and no
   unsolicited rewriting. Check whether the model can falsely imply wider reading.
8. Check maintainability against AGENTS.md: model-owned behaviour, thin delivery
   boundaries, justified cross-model orchestration, no unnecessary abstractions,
   no new automated tests and no broad unrelated cleanup.

Verification evidence and limits (updated after the first review):
- Read the Review corrections section of POETRY_ANALYSIS_IMPLEMENTATION.md. All
  thirteen first-review items have changes; verify those fixes independently.
- 53 cumulative direct HTTP-kernel/model/worker runtime checks passed on disposable
  SQLite, plus five direct JavaScript polling/UUID checks. External responses were
  simulated; one path exercised the real SSE provider parser.
- Scratch scripts if still available: /tmp/poetry-review-checks.php and
  /tmp/poetry-poll-runtime.mjs. Authored PHP fixtures were rolled back.
- Focused lint/Pint, PHP syntax, production build and diff checks passed. Existing
  full-project ESLint errors remain in untouched CollectionEditorPage.jsx and
  HomePage.jsx, with a warning in Logo.jsx.
- The first reviewer confirmed the local MySQL migration is applied and indexes
  survived. No schema changes were made in the correction pass. SQLite does not
  prove MySQL locking under concurrent load.
- Browser checks reached the live local empty analysis workspace, model controls,
  mobile pane/draft continuity, desktop pane structure and empty activity menu.
  No actual poem/conversation was created and no model request was sent. Populated
  thread retries, keyboard coverage, long-response scrolling, actual detached
  worker startup and live provider generation remain unverified end to end.

If MySQL/browser access is available in your environment, verify the outstanding
paths using disposable fixtures and preserve existing writing. Applying only the
new local additive migration is within the implementation's intended scope, but
never run migrate:fresh, reset, wipe, broad pending migrations or production work.
Do not send a real paid/provider request merely to complete a review without an
explicit author decision about that request. Otherwise state each blocker plainly.

Return findings first, ordered by severity, with exact file/line references,
concrete triggers and consequences, and a minimal recommended fix. Distinguish
confirmed bugs from concerns needing runtime verification. Then list the checks
you actually executed, outstanding verification, and whether the change is ready
for author use locally. If there are no findings, say so while retaining the
verification limitations. Do not give a ceremonial approval or rewrite code.

Second-review follow-up to inspect:
- JSX now uses findLast for the latest attempt per user message (it previously
  already used filter(...).at(-1) in the current worktree).
- AssistantTurn chooses the original world timeout copy by subject. Unknown world
  stop IDs retain their prior no-op semantics; request-ledger cleanup remains.
- AssistantTurn::failRun atomically retains received text, marks a still-owned run
  failed and closes running requests. Poetry invokes it on access-loss cancellation
  and exceptions; cancelled/superseded runs cannot be revived or overwritten.
- Latest evidence: 59 cumulative isolated PHP checks, the five polling/UUID checks,
  two actual JSX-lookup checks, focused lint/Pint, PHP syntax and build passed.
  /tmp/poetry-second-review.php contains the disposable retry-chain and final-stream
  archival exercises if still available. No actual provider call was made.
