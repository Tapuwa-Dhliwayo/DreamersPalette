# World sandbox implementation

Approved scope: local only. No deployment or remote data changes.

Conversation → sandbox → reviewed canon. Exercises retain messages and context;
ideas remain separate from existing world records until accepted. Naming is the
first specialist capability. Existing world tools and finished snapshots remain.

## Delivery ledger

- Foundation: additive schema, rich models, owner/world policies.
- Connections: local Codex/Claude logins and separately configured API connections.
- Exercises: durable conversation, selected context and structured proposals.
- Sandbox: editable board, links, alternatives and relative timeline.
- Canon: reviewed atomic changes, revision guards and guarded reversal.
- Naming: reviewed conventions, shortlists and existing-name awareness.
- Review: real execution checks and one end-to-end manual charter; no new tests.

T3 reference: pingdotgg/t3code revision 8b44f42b14c8e273156474a924ccf29680dd7857.
Patterns are reimplemented in the existing Laravel/Livewire application rather
than importing the T3 orchestration framework. No T3 source was copied.

Local subscription use depends on a logged-in CLI under the application user's
account. A browser stays in DreamersPalette while a bounded local process runs.
Connection setup may require the provider's own sign-in. No account credentials
are included in the repository or copied to Anubis. Hosted OAuth remains outside
this local increment.

## Delivered locally — 2026/11/04

Worlds → Sandbox is available at `/studio/worlds/{world}/sandbox`; Connections is
`/studio/assistant-connections`. Both are authoring routes with `worlds.update` and
owner/world policies. Two forward migrations have been applied to the existing
local database. No original deployed migration was edited.

The workspace includes saved exercises and selected context, conversation and
proposal collection, source attribution, editable board positions with keyboard
movement and Undo move, directed links, scenario filters, draggable/keyboard
sequence changes, naming guidance/shortlists/assignment, and reviewed canon changes
with guarded reversal. Existing characters have an Explore names entry point.
Renames can deliberately retain the previous name as an alias.

Supported canon proposal cores: character, event, encyclopedia entry, relationship,
magic system notes, arc title/measure, knowledge truth, investigation question,
blank map/notes, alignment chart axes, and comparison criteria/notes. Existing rich
subrecords remain owned by their specialised editors. Map changes capture versions;
magic notes use the existing versioning behaviour. Finished manuscript context and
public prose are not mutated by sandbox acceptance.

Working summaries are generated on request and only used after author acceptance.
`summary_through_id` identifies covered messages; originals remain visible and
stored. Turn manifests retain included message IDs, world revisions, chapters and
reviewed conventions. Uncollected proposal batches are included during handoff.

### Provider runtime

My ChatGPT and My Claude are configured locally for author 1 using existing CLI
logins. `default` delegates model selection to the installed provider. Override
`STUDIO_CODEX_BINARY`, `STUDIO_CLAUDE_BINARY`, and `STUDIO_LOCAL_AUTHOR_ID` through
local environment configuration. API connections require an exact model ID and
an explicitly supplied key. No paid API account was used for verification.

A persisted turn is run by `studio:assistant-turn {id}` in a detached local process.
It claims queued status atomically, saves received text, observes cancellation and
has a five-minute provider timeout. The page polls saved state. Duplicate launches
cannot claim the same turn. Startup diagnostics go to the private application log
`storage/logs/assistant-worker.log`. No running request survives a disconnect as an
unlabelled success. Interrupted or malformed responses retain received text.

Local runtimes use private temporary directories away from the repository. Coding,
image, browser, plugin and external agent tools are disabled. Claude uses its
existing login with tools, hooks and inherited MCP disabled. Codex isolates its
configuration and carries only its existing authentication, preserving a provider
refresh only if the source login has not changed concurrently. Temporary credential
copies are removed on normal completion/failure. No provider tools write world data.

Local service: transient `dreamers-studio.service`, not enabled at boot. No service
was configured on Anubis. The existing local environment retains database settings,
mail logging, and the local encryption key. Future content refreshes must exclude
assistant connections, local credentials, active turns and runtime files; do not
copy production connection IDs into local exercises without clearing/remapping them.

### Verification completed

- Both actual local subscription logins returned a neutral connection-check reply.
- The detached naming workflow completed with validated proposals.
- A real Claude → Codex handoff recalled both uncollected names and their tentative
  status. No manuscript text or real world notes were sent by these probes.
- Transactional scratch execution rejected stale proposal/world changes, unsafe
  reversal after a newer edit, unresolved proposals, cross-author access and
  archived worlds. It verified duplicate acceptance/collection, naming draft
  exclusion and acceptance/reversal for supported record cores.
- A rolled-back summary probe verified coverage and retention of originals. A dummy
  credential probe verified encryption and hidden model serialization.
- Browser review covered editing, selection/review/acceptance, Undo, naming import
  and timeline reorder. Mobile 390×844, tablet 1024×768 and desktop 1280×720 layouts
  were inspected; composer/frame clipping was corrected.
- Disposable browser/execution fixtures were removed. Original populated tables
  match the captured Anubis SQL INSERT statements, excluding the intentionally
  advanced migration ledger. Original world count 1, people 5, manuscript chapters 20.
- PHP formatting, compiled Blade, focused JavaScript lint, Composer validation and
  production build passed. The design detector returned no findings for scanned
  sandbox templates/module. Full ESLint still reports pre-existing React problems
  in EditorPanel, CollectionEditorPage and HomePage, plus the existing Logo warning.
- No automated tests were added or changed. No push, deployment or Anubis mutation.

### Limits for review

Every request uses a DreamersPalette context handoff; native provider-session resume
is not implemented. Codex emits completed message chunks; Claude emits text deltas.
Live quota/reset counters are not yet displayed. API streaming is implemented but
unverified against paid accounts. Naming-source claims require author verification;
these tool-free assistants do not browse for etymologies. Detail forms retain
explicit Save and leave warnings, not a separate browser recovery archive.

The core charter is `docs/manual-testing/world-sandbox.md`. Rich map pins, alignment
placements, comparison cells, magic rules, arc beats, evidence and knowledge
perspectives use the existing tools; the sandbox does not invent those histories.

## Source-reading correction — 2026/11/04

The source-size guard previously rejected a legitimate first question when an
exercise selected more than 180,000 characters of prose. It also rolled back the
question, and conversation summarisation could not recover because it resent the
same prose. Source selection now defines a consultable collection, not full prose
included in every prompt.

Questions and captured exercise settings are committed before preparation. A
background run snapshots only authorised selected chapters, performs bounded
application-controlled read/search rounds, retains sourced notes, and assembles a
bounded final request. CLI tools remain disabled; the reading protocol cannot
access arbitrary files, other worlds or unselected chapters. This is shared by
all provider adapters. Accent-normalised name/alias search is a navigation aid;
model-directed reading expands around candidate scenes and qualifies coverage.

Focused reading has bounded investigation rounds. Whole-source reading traverses
all selected passages and can pause after a bounded run. Continue resumes completed
reading without discarding notes. A preparation error retains the question and
turn; retries reuse the question. Edit saved question restores it for a new scope
or connection. Each turn has its own source snapshots and derived reading notes.
No notes are automatically treated as canon or written into chapter summaries.

Sources consulted shows unread/partial/full ranges, saved revisions, and changes
since capture. Valid citations open the saved source inside the app; unknown or
unread citations do not become trusted links. Read & discuss makes room for prose
beside the conversation. Source collections are grouped by manuscript with whole-
book selection. Conversation summaries do not reread the manuscript collection.

Implementation: `SourceReading`, `AssistantSource`, chapter-owned passage splitting,
and forward migrations adding request context, reading progress, source rows and
worker attempt identities. A replaced worker cannot complete a resumed request or
overwrite the current attempt's response.
The conservative character guard still protects final/context requests; this work
does not claim to discover every provider's exact token capacity. Whole-source
reading uses multiple requests on the chosen account; no automatic fallback exists.

Verification: the real exercise 13 retains its 15 selected chapters and no agent-
submitted question. Its 313,346-character collection prepared a 2,955-character
brief without a provider call in a rolled-back probe. A live neutral 214,336-
character collection completed with sourced response and accurate unread coverage.
Live failure/retry and whole-reading pause/resume probes passed. Source ownership
was checked with real requests. Retest charter:
`docs/manual-testing/world-sandbox-source-reading.md`.

## Continuity after author review — 2026/11/04

Saved chapter readings now seed future questions, including across exercises in the
same author's world. Reuse requires the same chapter revision and identical saved
prose. Notes/citations are copied into the new turn's scoped source snapshots;
observations remain interpretations, not canon. Earlier notes may be incomplete for
a different question, so focused investigation can revisit the prose. Whole-source
reuse includes a focused follow-up pass for the new question. Existing readings are
eligible without a destructive conversion or provider request.

Whole-source reading automatically continues between bounded batches by default.
Authors can choose manual batches instead; pauses explicitly retain a pending
question and Continue action. Stop, provider errors and usage limits still stop the
run. No automatic provider/model fallback is enabled. Retrying an old paused request
uses the current continuation preference while retaining completed reading.

The composer accepts a model override and default/low/medium/high local thinking
effort. The selected turn model is independent of the connection's saved default;
preferences are recovered when reopening the exercise. CLI model access/effort vary
by account/model. API thinking effort remains provider default. Codex effort uses
`model_reasoning_effort`, per the official configuration reference:
https://learn.chatgpt.com/docs/config-file/config-reference . Claude's installed
CLI advertises `--effort`; provider tools/MCP remain disabled.

Source citations open within the current workspace; disclosures retain native
open state across polling. Details has a matching reopen control. Empty Timeline
and List views communicate the selected view and next action. Loading indicators
respect reduced motion. Studio-wide AssistantActivity polls independently and
persists terminal acknowledgement on the turn; it shows ongoing work, ready answers,
pauses and failures while authors use other studio pages. This is in-app activity,
not email or operating-system push. All activity actions are author/permission scoped.

The local-only source/worker migrations were amended rather than growing unused
pre-deployment migration history. Matching additive column/index changes were
applied directly to the current local database, retaining all writing and 33 saved
reading sections. No reset, Anubis change or deployment occurred. Retest:
`docs/manual-testing/world-sandbox-continuity.md`.

## Encyclopedia conversations and usage — 2026/11/05

Build encyclopedia now supplies the application's entry types, scoped record
content/revisions and structured create/update/refinement contract. Proposals show
evidence attribution, preserve omitted existing fields, refine captured board ideas
and pass through existing reviewed canonisation. History opens resulting records.
Per-request usage tracks reading and final responses, including reported tokens
and partial failures. Connections have author-controlled request budgets and
optional model cost estimates. OpenRouter key-cap lookup is explicit; unsupported
allowance stays unavailable with provider links. Hosted choices use own API keys;
local subscriptions remain restricted to their configured owner. No shared login.

See ASSISTANT_ENCYCLOPEDIA_AND_USAGE.md and the assistant-encyclopedia-usage charter.
Two-author runtime/request checks, a real neutral Codex request and browser workflow
passed. No automated tests, paid API verification, Anubis update or deployment.
