# Structured authoring implementation and review ledger

Started: 2026/11/06. Local work; preserve accepted Trio records, original Notes and manuscript sources. No automatic acceptance, provider switch, deployment, database reset, automated tests or testing charter.

## Scope

Repair preservation bugs; align the prompt, assistant decoder, manual board, record reference, review and acceptance around per-kind definitions; support existing-record endpoints, real events and beats, existing JSON/scalar structures, typed child records and Undo. Add approved character profile sections while preserving Notes. Make unsupported operations explicit. Retain native permission/revision/version rules.

## Review sequence

1. Implement the coherent end-to-end change.
2. Review/update loop 1: contract, preservation, wrong-kind/incomplete structures and scalar/JSON round trips.
3. Review/update loop 2: child records, scoped references, stale revisions, atomic acceptance and Undo.
4. Review/update loop 3: prompt/runtime repair, manual authoring, review usability, browser and compatibility.
5. Fourth review: final audit, remaining findings, runtime evidence and honest readiness report.

These are Codex review passes unless an independent reviewer is explicitly recorded. Scratch execution verifies real application paths without adding automated tests. Product choices use recommended defaults; no further questions requested by the author.

## Review/update loop 1 — contract and preservation

Reviewed the complete native field shapes against the shared definitions and exercised decode → stage → review → accept → Undo on rollback fixtures. Initial execution caught native system Notes being non-nullable and magic cases lacking an independent revision. Corrected creation defaults, added additive case revisions and bumped them in native case edits. Aligned investigation evidence with its actual text/source/supports structure. Preserved omitted truth/question/Notes, profile sections and omitted beat event/intensity fields; null intensity is explicitly unrated, not fabricated.

The first successful round trip persisted an event linked directly to an existing character, an arc with a linked beat, comparison cells, separate knowledge truth/commentary, investigation evidence/question, an alignment placement, a rule and a case. Undo left zero active created event/arc/system/alignment parents. All fixture writes rolled back. Subsequent review corrected character-profile loading/display, enabled response contract 3 on newly sent turns, and added calendar scope/route support. Further boundary and UI checks follow in loops 2 and 3.

## Review/update loop 2 — graph integrity and Undo

Exercised existing-record updates, merge/replacement, scoped endpoints and reversal. Found and corrected omitted beat references being cleared, explicit null intensity not clearing an old rating, possible truth/question fallback from Notes, incomplete scalar objects claiming completeness, and dependency checks missing newly structured records. Consolidated kind/relation lookup into the shared definitions and endpoint-kind validation into one method. Parent revision checks now accompany full child/participant snapshots, including archived children, for new acceptances.

Rollback update evidence: original Notes, roles, unrelated profile summary, knowledge truth and investigation question survived partial updates; an old beat's event survived omission; explicit unknown intensity persisted as null; replace mode archived an omitted beat; case/rule links persisted. A newer child edit blocked Undo. After removing only that synthetic edit via a nested rollback, Undo restored prior beats, rating, archived state, profile, commentary, rule text, case outcome and case links while archiving the newly created beat.

A broader rollback creation pass also persisted approved profile sections, mutual relationships, entry type assignment, calendar leap behavior and an author-positioned map journey. Native schema extensions are additive and local. No accepted Trio record was changed. UI/provider and compatibility review continues in loop 3.

## Review/update loop 3 — real provider, manual UI and recovery

A neutral configured-local-Codex request returned exactly two event proposals, two existing-character participant links and no character rewrite. The real runner's scripted failure path made exactly one repair request: a valid correction completed and staged one event; an invalid correction failed, staged nothing and retained the raw response and original question.

The browser session was signed out. Rather than altering authentication, verified an authenticated Laravel GET returned 200, then exercised Livewire's actual update handler: all 12 domain editors rendered and saved, and typed canon review rendered. Visually inspected a separately served, read-only synthetic render. That exposed the narrow inspector as inadequate; changed typed editing/review to full width, added record jump links and changed-value comparisons, and put structural tables before collapsible supplementary Notes. This is not a claim that the signed-out browser completed authenticated UI actions.

Additional corrections: nullable numeric normalization; kind/deliverable consistency; preserving archived owned references; investigation/reveal required-state checks; matching event positions through collection/manual editing; catching unavailable-reference failures in the bounded correction path. Undo now refreshes only restored child revision tokens on reopened proposals, preserving proposed content; an actual re-review/re-acceptance succeeded. Saved-output restructuring captures full previous proposals and the original task, uses contract-versioned idempotency, and is explicit—accepted Trio records were not regenerated or modified. Added explicit continuation scope and an idempotent continuation action for bounded batches.

Fourth review follows after final boundary, native-tool, rendering and formatting checks. No automated tests or charter were added.

## Fourth review — final audit

Completed: 2026/11/07. Corrected investigation uncertainty targeting, explicit-clear preservation, empty replacement completeness checks, all-null profile detection, existing-record connection visibility, archived-subject endpoint resolution, archive-independent acceptance counts and the full-width Back button sizing. The repeated actual Livewire save/review and native accepted-record renders passed. Recovery capture retained full proposals, original task, citations and repeat-request identity. The final local-model prompt returned two events with existing-character participant links and no character rewrite. Boundary/update/Undo/reacceptance checks passed; build, syntax, Blade, formatting and whitespace checks passed.

The signed-out browser limitation remains explicit: visual inspection used a read-only synthetic render. No real Trio recovery or production deployment occurred. Original arcs 3–5 and people 2–4 remain present with Notes retained. No automated tests or charter were created. See [the fourth-review report](STRUCTURED_AUTHORING_FOURTH_REVIEW.md) for scope, evidence, remaining limitations and an advisory Claude prompt.
