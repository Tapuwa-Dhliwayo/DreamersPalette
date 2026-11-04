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
