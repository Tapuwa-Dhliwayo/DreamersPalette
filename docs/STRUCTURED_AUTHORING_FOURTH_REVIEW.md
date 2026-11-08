# Structured authoring — fourth review

Completed: 2026/11/07. Implementation began: 2026/11/06.

## Verdict

The structured authoring implementation is complete locally, with three review/update loops followed by this fourth audit. The app now asks for native structures, validates them, stages them as editable drafts, displays their structure in review, persists them through acceptance, and restores their children through Undo.

The exercised paths have no known unresolved blocking defect. This is **not** a claim that the real Trio exercise has been successfully converted or that every future model response will be correct. The original accepted arcs and character Notes were preserved. No conversion, acceptance or regeneration of the real Trio response was performed.

All four reviews were Codex’s own passes. They were not independent Claude reviews. See [the implementation ledger](STRUCTURED_AUTHORING_IMPLEMENTATION_REVIEW.md) for the three update loops.

## What changed

### One contract across the authoring flow

`ProposalTypes` owns the per-kind field rules, child collections, native relation, structure checks and illustrative field shapes. The prompt, decoder, manual idea form, reference operation and acceptance use these definitions. There are no new child staging tables or generic schema framework: proposed children remain in the existing idea JSON until acceptance.

New assistant turns use response contract 3, including the declared deliverable, per-proposal completeness and concrete missing work. A timeline must contain events; an arc must contain beats when claimed complete. Notes-only parents cannot silently stand in for completed structural tools. Honest partial proposals remain possible and are visibly marked. Unsupported proposals cannot be accepted until corrected.

The model receives explicit instructions and examples for every proposal kind. Examples are described as shapes, never evidence. Unknown facts and beat ratings may remain unknown. On invalid structure or unavailable references, the runner makes one bounded correction request using the saved question, validation errors and previous output. If that also fails, no drafts are staged; the question and raw response remain available.

### Native structures now supported

| Record | Structured proposal support |
| --- | --- |
| Characters | Aliases, roles, domain, summary, appearance, background, personality, motivations, strengths and vulnerabilities; supplementary Notes remain separate |
| Timeline events | Narrative position, fictional date label, existing calendar/day, participants and existing-character endpoints |
| Arcs | Existing character/manuscript assignment, measure and ordered beats, linked events, nullable intensity and observations |
| Encyclopedia | Existing entry-type assignment and aliases |
| Relationships | Scoped subjects, direction, mutual/dependency flags and period |
| Magic systems | Rules, conditions, consequences, exceptions, reasoning, existing decisions, cases and case/rule links |
| Knowledge | Established truth, knowers/beliefs/events, manuscript/reveal chapter and separate commentary |
| Investigations | Question, subjects, evidence with source/support flag, open questions, conclusion and resolved state |
| Alignment charts | Axis labels and native subject placements with reasons |
| Comparisons | Criteria and subject cells aligned to those criteria |
| Calendars | Months, weekdays and leap rules |
| Maps | Dimensions/scale, UUID pins, existing entry references and ordered journeys within an author-supplied coordinate frame |

Thoughts, questions and name shortlists stay on the board. They are not silently treated as canon records. Global lookup creation, manuscript magic variations, naming-convention changes, image uploads and history editing remain outside this proposal path. Native tools still own those operations.

### Review and manual editing

Typed editors and canon review use the full workspace width. Review leads with counts of the proposed changes, record jump links and actual beat tables, comparison cells, rule/case lists and other structures. Existing updates show changed current/proposed fields. Supplementary Notes are collapsible rather than the main presentation.

Connections can use an existing scoped character or other supported subject without manufacturing an update just to reach it. The connections panel resolves those names. Ordinary board connections are explicitly identified as board-only; they do not become world relationships on acceptance. Accepted/pending counts no longer depend on whether the board is showing archived drafts.

Saved legacy output has an explicit restructuring action. It carries the full saved proposals, original task, citation lineage and coverage limitations into a new versioned, idempotent request. It does not accept anything automatically. Bounded batches expose their completed and next scopes; continuing is an explicit author action.

### Preservation and Undo

Omitted fields preserve current writing. Explicit clears retain their meaning. Character profile patches preserve unrelated sections. Knowledge commentary cannot overwrite established truth, and investigation updates cannot erase their question through a Notes fallback.

Child updates check their owning parent and captured revisions. Merge preserves omitted children; intentional replacement archives omitted children from the supplied collection. Acceptance is transactional. New structured history captures children, archived state, case/rule pivots and participants. Undo restores the prior state, archives newly created records and rejects newer parent/child work. Reopened existing-record proposals receive current restored child revision tokens so they can be reviewed and accepted again.

## Findings corrected during the fourth audit

1. Investigation uncertainty previously risked writing a nonexistent Notes column. It now appends to Open questions. Other records retain it in Notes, preserving prior text when omitted and respecting explicit clears.
2. Empty replacement lists could borrow the old collection’s count and appear structurally complete. Validation now distinguishes replacement from merge. All-null new character profiles also count as empty.
3. Existing-record links could be persisted yet disappear from the connections panel because its query required two board IDs. The query and labels now support scoped external endpoints. The canvas draws only connections with two visible board nodes.
4. The new Back to workspace text inherited the old 32-pixel close-button style. It now has an appropriate text-button size.
5. Final review clarified that structural counts describe changes in this review. Open-question copy no longer promises a Notes field for investigations.

## Evidence from execution

No automated tests were written or changed. No testing charter was created. Verification used application execution and disposable scratch scripts outside the repository, with synthetic database writes rolled back.

| Check | Observed result |
| --- | --- |
| Authenticated workspace request | HTTP 200 |
| Actual Livewire edit/save handler | All 12 native record kinds rendered and saved; each draft revision incremented |
| Connections panel | Existing-character participant connection rendered with its name |
| Archived owned subject | Explicit participant link accepted and reversed without restoring or rewriting the archived character |
| Typed canon review | Rendered the structural tables and native field values |
| Creation round trip | Persisted event/participant, linked arc beat, comparison cells, knowledge truth/commentary, investigation evidence, placement, rule/case links, character profile, entry, mutual relationship, leap calendar and map journey |
| Native accepted pages | Character profile, arc beat and calendar rendered successfully in their native screens |
| Existing-record updates | Original Notes, roles, unrelated profile summary, truth, investigation question and omitted beat event survived; explicit null rating persisted |
| Child replacement | Omitted beat archived rather than deleted |
| Undo conflict | A newer child edit blocked Undo |
| Undo restoration | Prior beat content/event/rating/archive state, profile, commentary, rule, case and pivots restored; new beat archived |
| Reacceptance after Undo | Re-review and acceptance succeeded with restored revision tokens |
| Invalid structures | Empty complete arc, wrong timeline kind, unknown field, unexplained partial output, missing contract version and empty complete existing-character sheet rejected |
| Scope/shape validation | Foreign-world person, misaligned comparison cells, nonexistent journey pin and empty resolved conclusion rejected |
| Honest partial structure | Staged successfully with an explicit missing-work reason |
| Bounded correction | Exactly one repair call; valid correction completed, invalid correction failed with zero drafts and retained raw output/question |
| Recovery capture | Full earlier proposals, original question, citation-turn lineage and repeat-request idempotency retained |
| Final real local-model prompt | Returned two event proposals, two participant links to the existing synthetic character and an ordering link; no character rewrite; both events marked complete |
| Build/static checks | Frontend build, PHP syntax on changed classes/migrations, Blade compilation, Pint, Composer validation and whitespace checks passed |

The model check used the configured local Codex provider with a neutral two-event Mara fixture. It did not send manuscript content, run the Trio exercise or switch providers. The final prompt was approximately 19,000 characters before substantial manuscript evidence; that is a cost/space consideration for future optimization, not a claim of zero context overhead.

## Deviations and attention

- The browser session was signed out. Authenticated HTTP/Livewire paths were exercised through the application, and visual review used a separately served **read-only synthetic render**. This does not verify a signed-in browser’s full click sequence, Alpine state transitions, every mobile layout or assistive technology behavior. The temporary preview server was stopped afterwards.
- No automated regression suite was added, per repository instructions. The evidence above reports concrete runs, not exhaustive coverage of every field combination or race condition.
- Two additive migrations were applied to the local database: character profile/link endpoints/nullable beat rating, and magic-case revisions. No database reset or production deployment occurred. Original migrations and author records were retained.
- Native timeline order remains event position; `before` validates that order rather than creating a separate chronology graph. Board lanes remain a presentation aid. These are not three interchangeable representations of an arc.
- Newly proposed event references in beats and newly proposed rule references in cases are supported. Other scalar references use existing scoped record IDs. Arbitrary references between every kind of newly proposed record are not supported.
- Large results remain bounded to 20 ideas and 40 links per response, with explicit continuation. Semantic completeness still depends on evidence coverage, author intent and model judgment; structural validation cannot establish literary correctness or prove that the model classified the request correctly.
- Existing notes-only Trio arcs remain notes-only. Final read-only checks found arcs 3–5 and people 2–4 present, with their Notes retained and original character profiles still null. They were intentionally not backfilled or rewritten.

## Advisory prompt for Claude

```text
Review the structured authoring implementation in the current working tree and docs/STRUCTURED_AUTHORING_FOURTH_REVIEW.md against the original critique. Make no edits or model requests, do not restructure the real Trio response, do not accept proposals, and preserve author data. Do not create automated tests or a testing charter.

Trace the per-kind definitions through prompt, decoding/repair, manual editing, reference, review, acceptance and Undo. Focus on actual data-loss risks, incorrect native shapes, scoped ownership, stale revisions, child replacement/restoration, direct existing-record endpoints, recovery and continuation. Distinguish supported behavior from prompt promises and unverified paths. Check the runtime evidence against what the code actually proves.

Report actionable findings with severity, file/line and a concrete trigger/impact. Separate confirmed defects from plausible risks and product preferences. Do not manufacture findings to satisfy a quota. If no serious or concrete defect remains, say so. Do not describe the Codex passes as independent reviews or the synthetic preview as a completed authenticated browser journey.
```
