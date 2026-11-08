# Choose a model from your assistant connection

Prepared 2026/11/05. Local author review. Timebox: 30–40 minutes.

## Goal, risk and stop condition

Choose an available conversation model without knowing or entering its identifier.
Check both connection defaults and per-question choices. Stop on another author's
catalogue, changed saved credentials, an unrequested model fallback or lost writing.
No database reset or original question resubmission is required.

## Account and data

Use the existing author and their local My ChatGPT/My Claude connections. A second
ordinary author with dashboard.access and worlds.update is needed for the ownership
probe. API discovery requires that author's own configured provider key; metadata
listing does not generate a response. Do not put keys into findings or screenshots.
Use a disposable exercise for sending a verification question; keep exercise 13,
its board, saved reading and source chapters unchanged.

## Phase 0 — shape review

Inspect connection ownership, provider, saved model and revision without reading
credentials. Model catalogues must be scoped by author, provider and credential
fingerprint, with no account email/token in their cached payload. Local model and
effort choices come from client discovery, not an application-maintained list.
A discovered model is not a guarantee of future quota or provider access. Confirm
that a failed refresh preserves the visible saved selection and does not substitute
another model. API discovery must list only conversation candidates, not embeddings,
image or audio-only models. No catalogue request belongs in the inference ledger.

## Phase 1 — intended journey, notes mode

| Step | Action | Expected result / observe |
| --- | --- | --- |
| 1 | Open Connections and edit My ChatGPT. Open Model. | Named, searchable model choices, including provider default; no free-text model identifier input. |
| 2 | Search for part of a returned name. Clear the search. | Results narrow and recover, keyboard navigation remains available. |
| 3 | Edit My Claude without saving ChatGPT changes. | Claude's own list and default label replace the previous list; no stale ChatGPT label. |
| 4 | Start adding an API connection without a key. | Disabled model picker explains that a key is needed. Adding an own key and leaving the field loads its models; no response is generated. Skip if no API key is configured. |
| 5 | Open an exercise's composer controls. Change Connection. | Model choices update for that connection. Typed message, reading controls and discussion remain in place. |
| 6 | Choose a returned model and inspect Thinking effort. | Only reported effort levels are offered. A model without adjustable effort uses provider default. |
| 7 | In a disposable exercise only, send a short neutral question with the chosen model. | Turn records that selection; the connection's global default is unchanged. Provider refusal is clear, without silent fallback. |
| 8 | Repeat list/search/selection at mobile width. | Menu fits the screen and escapes panel clipping; selected names remain readable. |

## Phase 2 — risk map, fix loop only when requested

| Priority | Probe | Required behaviour |
| --- | --- | --- |
| 1 | Another author's connection ID or local-login provider | Server refuses before model lookup; no shared login or catalogue leakage. |
| 2 | Missing/revoked key, failed lookup or disconnected local client | Clear status and Refresh models; existing saved selection unchanged. |
| 3 | A saved/previous model is absent from the refreshed list | Explicit previous-selection label; validation refuses unknown choices in a successful catalogue. No silent substitution. |
| 4 | Switch connection or model after selecting a high effort | Per-question effort resets to default; offered levels follow the new model. |
| 5 | Forge a model or unsupported effort in a request | Server validation refuses it before starting inference. |
| 6 | Refresh while typing a message or editing connection details | Only model choices update; unsent text, key field and unrelated preferences survive. |
| 7 | Change the actual connection model while optional price estimates exist | Verify entered rates describe the new exact model before saving. Rates are author supplied. |

## Phase 3 — read-only verification

Compare connection model/revision before and after unsaved browsing. Compare the
inference ledger count before and after discovery. For a deliberately sent disposable
question, compare turn.model/request_context.effort against the selected options.
Confirm temporary discovery processes and private directories are removed. Keep
catalogue caches private and separated between authors and credential identities.

## Findings and execution evidence

| Finding | Resolution / retest |
| --- | --- |
| Model required knowledge of identifiers | Searchable fetched options in Connections and composer. |
| Default label could remain from the prior provider | Key the entire model field by provider/connection and catalogue; browser switch retest passed. |
| Long default labels widened the mobile popover | Shortened connection-default option and bounded menu width; 390px viewport/page-width check passed. |

Actual local discovery returned 9 Codex options (including provider default) and 13
Claude options. No generation request was sent. Runtime probes accepted discovered
models/efforts, refused unknown selections and another author's connection/local
login, and confirmed saved connections and inference count unchanged. Browser checks
covered both lists, search, provider switching, model-dependent effort and mobile fit.
No automated tests added. API lookup branches are implemented from provider APIs but
remain unverified against paid-account keys not configured in this local installation.
