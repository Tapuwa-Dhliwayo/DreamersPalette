# Assistant structured-output failure: findings and critique request

Date: 2026/11/06  
Scope: the complete exercise-to-worldbuilding output contract, across all tools.  
Prepared by Codex for independent critique by Claude. Revised after Claude’s critique; the blanket claim about timeline representability has been corrected. This is an investigation, not an implementation or a claim of readiness.

## 1. Finding

The assistant was given a JSON envelope, not a sufficient domain contract. The envelope identifies a record kind, title, notes and an optional generic fields object. It does not define the complete structures required to fulfil each supported task. The decoder and acceptance mapping then support only a subset of the structures the application already exposes through its manual tools.

Consequently, the system can label long prose as an arc, character sheet, comparison or other record, validate the envelope, stage it for review and accept it without creating the structure the author requested.

This is an application-contract failure with two distinct causes. Basic timelines are already representable using event proposals, collection order, participant links and before links. The saved prompt did not teach that mapping, and validation did not reject the arc substitute. Arcs with beats are different: their required child data cannot pass the current decoder or acceptance mapping. More source context cannot repair that unsupported structure. A better-directed model could have produced a basic timeline, although linking it to existing characters currently involves an unnecessary board-reference/update workaround.

Codex owns the implementation failure. Earlier correction passes verified context limits, evidence disclosure, citation scope and retry mechanics. Calling the system ready without verifying the requested authoring outcome was too broad. Structural delivery was treated as deferred work even though the author expected the assistant to populate the actual tools.

## 2. Confirmed outcome of the latest request

Exercise 13: **The heroic trio relationship**. Turn 53 asked:

> Create timelines for each of the heroic trio members based on the current story line

The saved response said three parallel timeline drafts were ready. Its actual proposal payload contained:

| Proposal | Kind | Structured fields | Notes length |
|---|---|---|---:|
| Tiberius — journey timeline | arc | Empty | 5,590 characters |
| Tamarius — journey timeline | arc | Empty | 4,378 characters |
| Samuelá — journey timeline | arc | Empty | 4,579 characters |

It returned three generic board connections, not chronological event relationships or arc beats. Acceptance explicitly skips `connection` links; these connections remain on the board and were not saved as world relationships.

The proposals were collected as ideas 60–62 and accepted as arcs 3–5. The current stored records confirm:

- Zero beats in each arc.
- No character reference (`person_id`) on any of the three arcs.
- No manuscript reference (`manuscript_id`) on any of the three arcs.
- Default measure `Pressure` on all three.
- Zero events in world 1 at inspection time.

The names of the characters exist in titles and prose. Those names are not structural links.

The response had partial evidence and stopped retrieval at its step limit. Those limitations explain uncertainty about the story; they do not explain or excuse returning the wrong data structure. Even a partial timeline should contain actual supported timeline elements and identify what remains incomplete.

## 3. Character-sheet precedent

Turn 48 requested character summaries to fill the trio's sheets. Turn 49 converted that response into three character proposals targeting people 2, 3 and 4.

All three generated proposals had empty `fields` objects and approximately 4,800–5,500 characters of Notes. They were accepted as character updates. Existing aliases were preserved; current roles remain empty and domain is null or empty. This does not establish that every such field could have been filled from the text. It establishes that the generated updates primarily delivered prose rather than a structured sheet.

The saved prompt explicitly instructed the model:

> appearance, strengths, vulnerabilities and relationships can be clearly headed sections within Notes, not invented database fields.

That instruction reflects the current character model, which exposes name, aliases, roles, domain and Notes. It also confirms that the prose-heavy result was encouraged by the contract. If richer character sheets are the intended product, those sections need an agreed application representation. Prompting the model to invent unsupported fields would merely produce rejected or discarded data.

## 4. Exact contract defect

The saved prompt supplied this common shape:

```json
{
  "intent": "discuss|propose|refine|clarify",
  "reply": "Your conversational response",
  "ideas": [
    {
      "kind": "thought|question|person|event|relationship|entry|system|arc|knowledge|investigation|name|map|alignment|comparison",
      "title": "Short name",
      "target_key": "Optional existing record key",
      "notes_md": "Details and reasoning",
      "fields": {}
    }
  ],
  "links": [
    {"source": 0, "target": 1, "kind": "connection|before|participant|relationship|depends", "label": "Authored relationship"}
  ]
}
```

This excerpt omits common provenance and presentation fields to isolate the problem. The actual schema also includes evidence, uncertainty, alternatives, lane and date label, but no typed arc-beat structure.

Specific defects:

1. There is no per-kind schema defining required scalar fields, child collections and relationships.
2. The global allowed-fields list excludes `beats`, arc `person_id` and `manuscript_id`, among other structures already used by manual tools.
3. An in-memory decoder check rejected both an arc containing `fields.beats` and an arc containing `fields.person_id`.
4. Links can address only idea indexes. They cannot directly reference an existing character as a participant without involving another board proposal/reference.
5. The acceptance mapping for an arc writes title, Notes and measure. It does not create beats or assign its character/manuscript.
6. No task-level check rejects “timeline requested, three empty arcs returned.” Valid proposal JSON is treated as sufficient structural success.
7. Empty fields are allowed across kinds. General prose can therefore satisfy the envelope while missing the requested functional content.

Basic timeline guidance can improve the current supported path immediately. It must map chronology to events rather than silently choosing arcs. However, changing the prompt alone cannot add beats or other fields the decoder refuses or the save path cannot persist. The prompt structure, validation, staged representation, review and persistence must agree.

## 5. Coverage across every output family

This table is a code-level capability assessment. Only the Trio timeline and character outcomes above were checked against actual generated records; the other rows are confirmed schema/mapping gaps, not claims that every tool has been exercised live.

| Output family | Native application structure or intended distinction | Current assistant gap |
|---|---|---|
| Characters | Name, aliases, roles, domain, Notes; separate relationships and event participation | Limited scalar support exists, but the prompt explicitly channels richer sheet content into Notes. Richer sheet sections need a product/schema decision, not invented fields. |
| Timeline/events | Events, sequence positions, date labels, calendar dates, character participants | Basic chronology already works through events, collection order, before links and participant links. Missing: guidance to use that representation, calendar fields and direct existing-character endpoints. Lanes are board-only; they are not a persisted character-timeline relationship. |
| Arcs/beats | Arc owner/manuscript, measure, ordered beats, intensity, optional linked events | Arc proposals can save only title, Notes and measure. Beat data and ownership are not representable through the allowed payload. |
| Relationships/dependencies | Existing endpoints, direction, period, dependency and mutual relationship behavior | Some fields work, but the contract is not a complete representation of current relationship capabilities; for example, `is_mutual` is not in the allowed fields. Narrative board connections can be mistaken for domain relationships. |
| Encyclopedia entries | Entry type, identity, aliases, Notes | Basic scalar support is present. The existing model is itself mostly prose. Richer typed entry attributes must not be advertised without application support. |
| Magic systems | Systems, rules with conditions/consequences/exceptions/reasoning, cases and rule links | The system proposal is a name/Notes shell. There are no typed rule or case proposals and no supported child-creation contract. |
| Knowledge/reveals | Truth, who knows/believes it, associated events, manuscript and reveal chapter | The full truth/knowers/reveal structure is not exposed. There is also a confirmed mapping defect: referencing knowledge with both truth and Notes can overwrite truth with Notes on acceptance. Knowers are existing JSON and reveal fields are existing scalar columns; no new domain tables are required for those additions. |
| Investigations | Subjects, question, structured evidence, open questions, conclusion, resolution | Subjects, evidence, open questions, conclusion and resolution already exist as JSON/text/scalars but are not fully exposed. An assistant update omitting Notes can also map the existing question to an empty string. That conditional mapping has been reproduced in memory, not against a saved author record. |
| Maps | Dimensions, scale, pins, linked entries, ordered journeys and image assets | New proposals intentionally create blank maps with Notes/default dimensions. Pins, scale and journeys are not returned through the contract. A real asset requirement must be explicit; it cannot be solved by fabricated coordinates or file paths. |
| Alignment charts | Axis labels and positioned subjects with reasons | Axis labels are supported. Placement records are not represented by the proposal contract. A chart shell can be accepted without the requested plotted subjects. |
| Comparisons | Criteria, subject references and per-criterion cells | Criteria are supported; subjects and their nested cells are not exposed through the allowed fields. Both already live in the subjects JSON column. This is an adapter gap, not a need for a new cell table. |
| Calendars | Month/week structure, leap rules and conversion to event dates | No calendar proposal kind or complete calendar/date-edit contract exists despite native calendar tools. This must be declared unsupported or implemented deliberately. |
| Thoughts/questions | Deliberately unstructured exploration | Prose is appropriate here. These must not become silent substitutes for requests to create functional domain records. |
| Naming | Shortlists, origins, conventions, uncertainty and assignment to subjects | A name proposal exists. The contract should distinguish a shortlist from a requested assignment/update and expose only operations the app can actually complete. |

The recurring defect is **support for a record label being confused with support for the record's complete behavior and child data**.

The inventory must additionally address magic variations, decisions, rule/system versions, map versions, naming conventions and entry-type assignment. For each, distinguish a supported editing operation from server-owned audit/version history or an unsupported capability. Do not let the model create or rewrite history as ordinary content. Most identified structures already have application storage; richer character sections and multi-character arc ownership remain product/model decisions.

## 6. Shared board-layer defects and preservation risks

The flat proposal representation is shared by the manual board and the assistant. Fixing only model instructions would leave the manual board and acceptance path inconsistent. Five definitions currently overlap or drift:

1. The manual board form's validation, extracted fields and excluded fields.
2. The assistant proposal allowed-fields list and decoder rules.
3. The acceptance field mapping.
4. The existing-record reference/import mapping.
5. The prompt's advertised field map.

A shared, ordinary per-kind definition should inform these existing boundaries. Staged JSON children can remain in the board idea's existing fields column. New generic schema frameworks, staging tables and a separate deliverable entity are not established requirements.

### Knowledge truth overwrite: confirmed mapping defect

The existing-record reference path takes `notes_md` before `truth_md`. Acceptance then uses the board's Notes as the knowledge truth because `truth_md` is not in the allowed fields. A knowledge record with separate truth and commentary can therefore have its truth replaced by commentary through an apparently ordinary reference/accept operation.

An in-memory execution of the actual acceptance mapper changed synthetic `Established truth` into `Separate commentary`. No record was saved. This is an existing preservation bug, not merely missing assistant functionality, and belongs before expanding knowledge support. Claude reported zero existing knowledge records during its database inspection; this investigation did not independently recount them.

### Investigation question clearing: conditional mapping failure

When an assistant update omits Notes, collection has no native `notes_md` to copy from an investigation's snapshot. Acceptance maps the resulting null Notes to an empty `question_md`. In-memory execution of the mapper changed a synthetic existing question to an empty string. The full collection-to-save path has not been executed against a real investigation; no author data was changed.

### Undo must grow with child writes

Snapshots already include some related structures, including arc beats, system rules and event participants. Undo primarily restores fillable parent attributes and specially handles participant links. Capturing child data in a snapshot does not mean Undo restores it.

Adding beats, rules, cases or placements requires corresponding before/after capture, creation/update/archive reversal and revision/conflict protection in the same implementation slice. Otherwise, expanding acceptance would create changes its advertised Undo cannot reverse. This is a requirement for new child-writing support, not a claim that the current notes-only Trio acceptance already created undeletable beats.

## 7. Why review did not expose or resolve the problem

The review workflow largely mirrors the generic proposal shape: title, kind, long Details/Notes, a few scalar fields and before/after content. Arc review has no proposed-beat editor because no beats were staged. The accepted arc consequently has no beats to display.

A richer reader alone cannot recover structures that were never returned. However, review should have made the mismatch obvious before acceptance: “3 arc shells, 0 beats, 0 events, 0 character links” is materially different from “three timelines ready.”

Showing headed Markdown under Notes may make text easier to read. It does not turn headings into events, beats, participants, placements or comparison cells.

## 8. Required correction: one complete contract per supported task

The primary fix is to provide and enforce the structure the author originally requested across the shared board and assistant pipeline. Prefer a simple per-kind map consumed by the existing components, not a new generic schema framework.

For each supported output family, define:

- The actual deliverable: timeline, arc, character update, populated comparison, etc.
- The permitted create/update/refine operations.
- Typed fields, required fields and explicit unknown/unchanged semantics.
- Child collections and how they attach to their parent.
- Existing-record references and temporary references to new proposals.
- Evidence, interpretation and uncertainty associated with the relevant fields/elements.
- What a complete, partial, unsupported or clarification result means for that task.
- The matching review presentation and persistence behavior.

The relevant machine-readable definitions and clear examples must be sent to the model. A single unconstrained `fields: {}` example is insufficient. Supported definitions should match the actual validator and save path; avoid multiple independently drifting lists.

This does not require sending every tool's full schema on every turn. Send the relevant per-kind field definitions, validation requirements and examples for the task, with explicit capability information for other tools. Do not claim support based only on a kind name.

### Timeline requirement

A request like turn 53 should use the already available ordered-event representation, with explicit participant links to existing characters once direct endpoints are supported. Before that extension, disclose the board-reference/update workaround rather than pretending no basic timeline path exists. Shared events should be reusable across the character views. Unknown dates remain unknown; relative ordering and uncertainty must be expressible. Returning an arc title and several prose sections must not count as fulfilling it.

A character's personal chronology and an arc's dramatic development are different structures. If the author requests both, return both with explicit links. Do not silently substitute one for the other.

### Arc requirement

When an arc with beats is requested, return its applicable owner/manuscript, measure and structured ordered beats with supporting evidence and event references where applicable. Do not invent intensity values merely to satisfy a required field; handle unsupported or unknown values explicitly.

### Character requirement

Populate supported structured fields when evidence warrants it, preserve unrelated author content and keep linked relationships/events as real links. Decide which additional sheet sections are actual structured application data. Do not claim to have populated unsupported sections while placing everything in one Notes string.

### Every other tool

Apply the same standard to rules/cases, knowers/reveals, investigation evidence, map pins/journeys, chart placements, comparison cells and calendar structure. Deliver functional records rather than empty parents with descriptive Notes. Where the application lacks a capability, report that limitation before representing the task as ready for acceptance.

## 9. Validation and review must enforce that contract

1. Validate each kind against its own structure, not a shared bag of optional fields.
2. Require a declared deliverable and validate structural consistency: a declared timeline needs events; an arc needs its requested beats or explicit missing items. Avoid brittle keyword-only routing. A model declaring its own answer to be an arc still does not prove that it fulfilled the author’s timeline request; review must make that distinction visible.
3. Verify child records, references, parentage and ordering before presenting a completed proposal.
4. Allow links to existing scoped records without rewriting those records merely to create an endpoint.
5. Preserve author content with explicit update semantics: absent, unchanged, unknown and intentionally cleared are different states.
6. Handle output limits with declared batches/continuation when necessary. The current 20-idea limit and API output-token cap must not encourage compressing an entire timeline into three prose parents.
7. When output is structurally wrong, retain the author's request and allow bounded correction; do not stage it as the requested finished result.
8. Render typed review: event order/participants, arc beats, field-level character changes, matrix cells, placements, rule/case collections, etc. Show missing required structures clearly.
9. Keep author approval, ownership, revisions, citations, atomic saving and Undo that actually restores every changed structure. Those safeguards remain necessary; they are not proof that the requested content was delivered.

Structural validation cannot prove literary accuracy. It can and should prove that the requested kind of data actually exists before the author is asked to accept it.

### Smallest coherent implementation order

1. Fix field-preservation defects and make shell-only capabilities explicit. Show a structure headline in review.
2. Align the five existing boundaries around simple per-kind definitions.
3. Add scoped existing-record link endpoints and explicit timeline/event guidance; handle calendar fields and output batching.
4. Expose existing JSON/scalar structures: comparison subjects/cells, investigation fields, knowledge/reveal fields and mutual relationships.
5. Add child-table writes for beats, placements and system rules/cases, each with its editor, review and Undo support. Reuse existing domain forms/rules where appropriate.
6. Resolve product decisions separately: richer character sections, multi-character arcs, meaningful map coordinate/image requirements and the scope of calendar/naming/variation operations.

## 10. Existing failure recovery

Do not delete, overwrite or silently reinterpret the accepted Trio records. They contain useful source-linked prose even though they failed the requested outcome.

Do not run the current generic recovery path on response 53 and expect it to invent missing capabilities. After timeline guidance and endpoint support are in place, a later repair should explicitly show what can be converted into real events and links, what remains uncertain, and which records will be retained. Add arc beats only after their complete write/review/Undo path exists. Keep the arcs only if the author wants dramatic arcs rather than chronologies. Reuse the saved request, response and citations where sufficient. Further reading must address a real evidence gap, not compensate for a missing output schema.

Apply the same preservation rule to existing character Notes and all other tools.

## 11. Evidence and limits of this investigation

Work performed: read-only inspection of saved turns 53/49/48, their saved prompts/proposals, collected ideas 48–50 and 60–62, current arcs 3–5, people 2–4 and world 1's event count; inspection of proposal validation, acceptance mapping, native models/forms and review templates; two in-memory decoder checks for unsupported arc fields; and subsequent in-memory executions of the acceptance mapper showing knowledge truth overwrite and conditional investigation question clearing.

No author records, application code or accepted content were changed. No real model request, deployment, automated test or testing charter was created. This Markdown report is the only repository artifact added for the investigation.

Key code references:

- [Prompt instructions and generic response shape](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/app/Services/Worldbuilding/ExerciseContext.php:428)
- [Allowed proposal fields and kinds](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/app/Services/Worldbuilding/SandboxProposals.php:18)
- [Decoder's global fields restriction](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/app/Services/Worldbuilding/SandboxProposals.php:63)
- [Actual acceptance field mapping](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/app/Services/Worldbuilding/Canonisation.php:258)
- [Native arc model and beat relationship](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/app/Models/StoryArc.php:16)
- [Native beat structure](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/app/Models/ArcBeat.php:15)
- [Current character model](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/app/Models/WorldPerson.php:15)
- [Generic proposal editor](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/resources/views/livewire/studio/worlds/sandbox-idea.blade.php:5)
- [Canon review presentation](/home/tapuwa-dhliwayo/Documents/Projects/DreamersPalette/resources/views/livewire/studio/worlds/sandbox-canon.blade.php:1)

## 12. Request to Claude

Critique this report against the actual current code. Do not implement changes yet. Do not add tests or a testing charter, resubmit the real request, alter accepted content or reset data.

Please answer:

1. Are the observed failure and causal explanation correct? Separate confirmed defects from inference and product decisions.
2. Does the capability table miss any output family, child structure or actual supported path? Correct any overstatement of what is impossible.
3. What complete per-kind structures must be sent to the model, validated, staged, reviewed and persisted? Identify application-model gaps separately from assistant-adapter gaps.
4. Where can the existing contract be extended simply, and where would a proposed schema require unnecessary redesign?
5. How should task-level validation reject a notes-only substitute without forcing invented data or rejecting legitimate partial work?
6. What must change in review so the author can inspect and edit the actual structures before acceptance?
7. What is the smallest coherent implementation order across all tools, with explicit support boundaries during rollout?
8. What is the safest repair path for the already accepted Trio arcs and character updates?
9. Does the proposed Undo coverage fully restore child changes and protect later edits?
10. Do the knowledge/investigation preservation findings require any additional safeguards before expanding the contract?

Do not conclude readiness merely because JSON validates or a record can be accepted. Judge success against the author's requested functional outcome.
