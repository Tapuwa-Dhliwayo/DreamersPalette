# Encyclopedia conversations and private assistant usage

Delivered locally 2026/11/05. No push, deployment or Anubis changes.

## Six-stage delivery

1. Account isolation: connection, exercise, source, proposal and usage boundaries
   remain author-owned. Per-request workers recheck owner permissions, connection
   revision/enabled status and worker identity. Local subscriptions remain restricted
   to the configured local author; hosted provider choices exclude them. The personal
   naming-chat import is also restricted to that local author.
2. Agent contract: Build encyclopedia mode supplies entry types, existing character,
   entry and relationship content/aliases/revisions, allowed fields and a versioned
   contract. Supported proposals can create records, update supplied existing targets
   or refine a captured unaccepted board idea. The catalogue is bounded to 200
   records and 40,000 content characters, prioritising selected/mentioned subjects.
   Omitted content must be explicitly selected before updates; partial catalogues
   are labelled and cannot establish that a subject does not exist. Other specialist editors retain their
   richer subrecords. Unknown proposal fields/types and foreign references are rejected.
3. Review: preview proposals alongside discussion, collect onto the existing board,
   edit or discuss them, select changes and Review canon. Evidence basis distinguishes
   textual evidence, interpretation and author decisions, as attributed by the
   assistant; the author still verifies it. Missing evidence is explicit. Existing
   aliases/types and other omitted fields survive updates. Collection/refinement is
   replay-safe, stale revisions are refused, and accepted history retains provenance.
   Similar-name warnings and an explicit link-to-existing control support deliberate
   duplicate resolution. History opens resulting records. Acceptance remains atomic.
4. Usage: each reading/investigation and final response request has a private ledger
   row, including turn, worker attempt, provider/model, purpose and completion state.
   Stream usage snapshots replace cumulative totals rather than adding duplicates.
   Missing token/cost figures remain null. Failed/cancelled usage survives when the
   provider reported it; interrupted workers are reconciled by activity polling.
5. Allowance: OpenRouter can fetch a key spending-cap balance on explicit Check key
   allowance. This is not a total account balance or a guarantee of remaining model
   requests. Other connections explicitly show unavailable with a provider usage link.
   Checked timestamps are UTC and stale balances are labelled. No subscription quota
   is inferred from token totals, and no private provider endpoints are scraped.
6. Verification: transactional two-author runtime probes, actual second-author route
   requests, one real neutral Codex request, desktop/mobile browser workflow and
   engineering checks. See the focused review charter.

## Account connection choices

Hosted authors supply their own encrypted API keys and exact model IDs for OpenAI,
Anthropic or OpenRouter. Saved credentials are excluded from model serialization
and never prefilled into the edit form. No shared application key, customer billing,
provider OAuth registration or hosted subscription sign-in was added. Existing
local Codex/Claude logins were preserved. Disconnect stops future work; every next
reading request rechecks availability. Account usage outside DreamersPalette is
outside this ledger and its budgets.

Provider research:

- [OpenAI authentication](https://learn.chatgpt.com/docs/auth) distinguishes local
  ChatGPT subscription sign-in from API billing and cautions against exposing Codex
  execution publicly. No general hosted subscription integration was assumed.
- [Anthropic Agent SDK](https://code.claude.com/docs/en/agent-sdk/overview) requires
  prior approval for third-party products offering claude.ai login/rate limits;
  hosted authors therefore use API keys.
- [OpenRouter key limits](https://openrouter.ai/docs/api_reference/limits) documents
  GET /api/v1/key and limit_remaining/limit_reset. A null key cap is not rendered
  as an unlimited account balance; the account dashboard remains authoritative.
- [OpenAI streaming usage](https://developers.openai.com/api/reference/resources/chat)
  documents include_usage and missing final usage on interrupted streams.
- [Anthropic streaming](https://platform.claude.com/docs/en/build-with-claude/streaming)
  reports initial input usage and cumulative output updates.

## Budgets and cost estimates

Connections default to 200 started requests per UTC day and 100 per saved question,
including retries. Authors can change these within validated bounds. A question
can consume many requests. Concurrent queued/running questions are limited to two
per author across exercises. User-row locks serialize concurrent admission; each
provider request reserves its budget before starting. Failed startups conservatively
count against the request budget. A reached budget retains the saved question and
completed reading; increase the budget or await the daily reset, then retry. A
per-question limit only resets with a new question or by raising its budget.

Optional input/output USD prices per million tokens belong to the connection's
exact default model. Each request captures those rates. Overrides do not inherit
another model's prices. Estimates require both reported input and output totals;
subscription requests receive no API cost estimate. Estimates apply supplied input
prices to cache tokens too, excluding cache discounts/premiums and non-token fees.
They are not bills or hard spending caps. Provider-reported API costs are separate.
No provider prices are guessed or silently fetched. No automatic paid fallback.

## Local schema and preservation

New migration: 2026_11_05_000001_add_assistant_usage. It adds connection budgets,
nullable allowance/pricing fields, nullable sandbox provenance and the indexed
studio_assistant_requests ledger. No foreign keys/cascade deletion. Applied locally
without reset. Nullable pricing columns added later in the same undeployed migration
were mirrored with additive ALTERs on the existing local database.

No existing writing, accounts, permissions, connections or the original exercise 13
were modified by the runtime/browser probes. Runtime fixtures used rolled-back
transactions; a clearly named lighthouse browser fixture was removed explicitly.
Normal provider token refresh may occur during real local connection verification.
No original question was resent. Earlier requests cannot be backfilled accurately;
the interface explicitly excludes them rather than showing invented usage.

## Verification and findings

- Credential encryption/hidden serialization; second-author connection/exercise
  refusal; cross-world source/target refusal; no usage crossing accounts.
- Actual second-author Connections route: 200 with no first-author connections or
  local subscription options. First-author sandbox route: 403.
- Creation, relationships, preservation of omitted fields, repeated collection,
  repeated acceptance, chat refinement/replay, stale review rejection and guarded
  Undo passed through actual domain methods, with writes rolled back.
- A real neutral local Codex request returned a lighthouse proposal and usage:
  12,085 input and 72 output tokens. Validation, collection, acceptance and ledger
  completion passed. No original writing was supplied; fixture data rolled back.
- Browser: preview/collect/accept, actual encyclopedia entry/alias rendering,
  navigation to the result, mobile Undo and history update. Connections and usage
  inspected at 390px; no page-width overflow. Screenshot uses disposable prose.
- Found existing Undo defect: reversed_at was not fillable, so normal update silently
  omitted it. CanonChange::markReversed now explicitly records it. Content restoration,
  stored marker and repeated Undo passed; browser history now changes to Reversed.
- Found activity polling's broad joined UPDATE could deadlock against long-running
  local verification transactions. Replaced with bounded stale-ID selection and
  per-turn locked/rechecked updates, matching the worker attempt for ledger cleanup.

## Attention required

No automated tests were added or modified, per project instructions. Permission,
idempotency, pricing and historical protections were checked with disposable runtime
probes instead. Real paid API streaming, OpenRouter live allowance and API billing
remain unverified without an author-supplied API connection. They are implemented,
not claimed as live-account verified. Complete the author review charter before
release. Hosted subscription OAuth and shared/collaborative world credentials are
outside this delivery, as specified in the approved plan.

Final checks: PHP syntax on all changed/new files, Pint, compiled Blade, Composer
validation, focused sandbox JavaScript lint, production build and diff whitespace
checks passed. The build retains the existing runtime global_atmosphere asset
warning. The design scan retains existing sandbox palette/radius documentation
discrepancies; new usage styles use the shared studio tokens.

## Model selection follow-up — 2026/11/05

Model identifiers are no longer typed into Connections or the chat composer. Both
use searchable fetched choices, with loading/failure/missing-key states and explicit
Refresh models. The whole model field is replaced on catalogue/provider changes so
its selected label and accessibility association cannot retain another provider.
Composer effort choices come from the selected local model's reported capabilities.

AssistantModels scopes discovery/cache by author, provider and credential identity.
Codex uses the installed client's initialize/model/list protocol with isolated login
configuration; Claude uses its initialization control response, without a user prompt.
Tools/hooks/inherited MCP remain disabled. Temporary processes/directories are cleaned
up. Local login refresh is retained only if the source login is unchanged. No model
inference or conversation-history read is used for discovery. Hosted authors cannot
use the installation owner's local client. Supported API discovery uses own-key
OpenAI /v1/models, Anthropic /v1/models (pagination), and OpenRouter /models/user
(account preference-filtered). Non-conversation candidates are excluded; final model
access/quota is checked by the provider when a question is sent.

Catalogues cache for ten minutes; failures for twenty seconds. A refresh failure
retains the saved model visibly rather than changing it. A successful catalogue is
also checked server-side when saving a connection or sending a new question; unsupported
thinking efforts are refused. Retrying historical questions retains their captured
connection/model behavior. No schema change or original content edit was needed.

Primary protocol references: [Codex model/list](https://learn.chatgpt.com/docs/app-server),
[Claude supportedModels](https://code.claude.com/docs/en/agent-sdk/typescript),
[OpenAI models](https://developers.openai.com/api/reference/resources/models/methods/list),
[Anthropic models](https://platform.claude.com/docs/en/api/models/list), and
[OpenRouter account models](https://openrouter.ai/docs/client-sdks/typescript/api-reference/models/models).
Local discovery, browser and permission probes passed. Real API keys were not
available for API-branch verification. Review: manual-testing/assistant-model-selection.md.
The conversation-intent/character-sheet routing finding is a separate unresolved
review item; model pickers do not claim to repair that behavior.

## Conversational intent and recovery — 2026/11/05

The previously unresolved response-mode finding is implemented. The composer has no
Response selector. New ordinary turns use response_contract=2: the provider returns
natural reply text plus discuss/propose/refine/clarify intent and any structured
proposals. Conversation words determine the requested operation. A pure discussion
has no drafts; explicit update requests produce linked record proposals; follow-up
corrections target captured board IDs/revisions. No keyword-router or separate model
classification request was introduced. Existing in-flight/paused legacy turns keep
their prior protocol. Discussion summaries remain an explicit toolbar action with
review before adopting a working summary.

Structured output is decoded before display rather than streaming raw JSON. The
complete draft batch is staged transactionally alongside turn completion, with the
worker's author permissions and run identity rechecked. No canon write happens there.
Reply-local targets, destination labels, proposal evidence and Review updates replace
the old extra Collect step. Thoughts/shortlists remain non-canon pieces. Canon review
can inspect qualified drafts together: unresolved questions are visibly retained in
the proposed Notes, and accepting them requires acknowledgement. Snapshot/revision,
permission, idempotency and Undo protections remain.

Prepare linked updates is available on legacy replies and thoughts. It captures the
saved content and original request, uses current scoped record definitions and reuses
original saved citations. Recovery makes no manuscript-reading requests and does not
copy/send whole chapter prose. Its model request and usage remain visible. Repeated
preparation with the same content, connection/model and effort returns the existing
turn. Original thoughts/answers offer Review linked updates when already prepared.
Standalone author thoughts are supported too; unclear destinations should elicit a
focused question. Source thoughts and answers are not overwritten or archived.

The actual reported trio failure was recovered as turn 49: Tiberius/person:2,
Tamarius/person:3 and Samuelá/person:4, three pending drafts with 55 source links.
One provider response request, zero reading requests; original people/notes/aliases/
roles/revisions, response 72, thought 47 and saved reading were verified unchanged.
The real drafts remain unaccepted for Tapuwa's review. A separate real-provider
neutral journey exercised ordinary discussion, three-sheet preparation, one-sheet
refinement and grouped acceptance; all neutral writes rolled back. Owner/message/
citation boundaries and uncertainty acknowledgement were exercised separately.

Review: manual-testing/conversational-world-updates.md. No migrations, automated
tests, push, deployment or Anubis changes accompanied this correction.
