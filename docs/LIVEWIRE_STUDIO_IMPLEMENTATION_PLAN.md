# Livewire creative studio: delivery plan

Current delivery and remaining review: [completion ledger](STUDIO_REMAINING_WORK.md), updated 2026/10/26. Historical scope/status statements below do not supersede that ledger.

Date: 2026/10/24.
Status: first private manuscript increment implemented locally on dev; design review and production release remain pending. Later worldbuilding and publishing sections remain proposals.

Implementation update, 2026/10/24: Tapuwa authorised the first Livewire manuscript increment. Work now implements private manuscripts, chapters, summaries, saved draft locking, comparison, and Markdown export. These records do not take over existing published novels; public-release onboarding remains a later reviewed increment. The first private manuscript has no Book association, avoiding duplicate editable ownership of existing public chapter rows. The working implementation and review evidence are recorded in `docs/STUDIO_IMPLEMENTATION_STATUS.md`.

## 1. Decisions, recommendations, and unanswered questions

### Decisions from Tapuwa

- Retain the existing React public reading, publishing, and poetry experience.
- Build the new private manuscript and worldbuilding studio in PHP, Laravel, Livewire, and Blade. Do not embed a React application inside it.
- Markdown is the writing source. Preserve the existing editor's useful behaviours and Assist backend, rather than introduce a word-processor document format.
- Begin with a comfortable manuscript space: visible chapters, optional chapter summaries, quiet writing, and dependable saving.
- Worldbuilding is a primary purpose. Include worlds shared by stories, aliases, quick entry creation while writing, and real reusable thinking tools.
- Alignment charts are reusable tools, not a character-specific module. Tools must have meaningful behaviour rather than arbitrary blank panels.
- Include timelines, calendars, arcs, relationships, geography, and specialised worldbuilding workflows in the product scope.
- Permit current Codex and Claude accounts to read material when instructed; do not add model API analysis charges inside the application.
- PC and tablet are primary novel-writing devices. A native iPhone app is not a dependency.
- Draft lifecycle confirmed subsequently: one active draft; finishing Draft N saves and locks it, then starts Draft N+1 as a copy. See [STUDIO_DRAFTING_DESIGN.md](STUDIO_DRAFTING_DESIGN.md) for this refinement and future multi-user ownership. This supersedes arbitrary named snapshots as the primary author-facing draft action.

### Recommendations that may be challenged

R1. Give the studio private manuscript/chapter records rather than making private autosave modify the existing public chapter rows.

R2. Use a separate `/studio` Blade/Livewire layout and asset entry. React remains responsible for its existing routes and is not loaded by studio pages.

R3. Port the existing textarea-based Markdown interactions to Alpine/plain JavaScript initially. Make inline reference highlighting a later editor-input enhancement; do not choose an elaborate editor prematurely.

R4. Give an active chapter one save boundary containing title, Markdown, and summary. Distinguish that from manuscript-wide ordering and publication actions.

R5. First ship standalone private manuscripts with finished drafts. Before importing an existing public book, introduce immutable reader releases and disable competing legacy mutations for that book.

R6. Deliver manual Save first as a complete slice, then autosave, recovery, and conflicts before the first milestone is accepted.

R7. One manuscript holds one novel’s working text. It need not have a public Book or world to begin. Attach shared worlds and an optional public identity later.

### Must be answered during the first prototype

- Tablet model, browser, keyboard, and preferred orientation.
- Comfortable editor font, line width, and whether Split should be remembered per device.
- Whether chapter summaries are normally hidden or shown in a compact outline mode.
- Whether full offline writing is actually required. The proposed first version handles interruption and recovery, not fully offline multi-device merging.
- Which Codex and Claude surfaces must connect, and their actual connection configuration.
- Whether map creation means schematic planning plus Wonderdraft or an eventual in-app painting/terrain engine.

## 2. Existing code and why the new boundary is necessary

Composer includes Laravel 13, Fortify, Sanctum, and Spatie permissions. Preparation on 2026/10/24 installed Livewire 4.4.8, Flux 2.21.0, and Flux Pro 2.21.0 using existing global Composer credentials. Anubis's forge account also has global Flux credentials configured, checked read-only. Credentials remain outside the repository. React 19 owns the existing interface. The separate Livewire layout and Vite entries now implement the private studio.

`ChapterEditorPage.jsx` loads books and one chapter, validates title/number, saves through `/api/studio/chapters`, and navigates back to the chapter list. `EditorPanel.jsx` supplies formatting controls, a textarea, rendered Markdown preview, selected-text Assist, and replacement handling. Local recovery uses browser storage. These behaviours are useful but need adapting to Livewire.

`chapters.content_md` is currently both editable studio text and public reader text. `chapter_number` is unique per book, includes archived rows in application checks, and forms part of public URLs. Existing controller operations also permit changing a chapter's parent and publication state.

Simply adding autosave would therefore make private revisions public. Reordering chapter numbers could change existing links. Maintaining independent React and Livewire editors over the same working text would create competing mutation paths.

Proposed ownership:

| Surface | Owner |
| --- | --- |
| Existing poems, collections, public reader, account security | Existing React application and Laravel APIs |
| New manuscript/worldbuilding screens | Livewire classes, Blade, Form Objects |
| Business behaviour and persistence | Laravel models, policies, focused cross-model services |
| Cursor, text buffers, selection, preview presentation, diagrams/maps | Browser-local JavaScript/Alpine controlled by the studio |
| Grammar provider and credentials | Existing Laravel backend; reuse its HTTP endpoint initially |

Livewire is the application framework; it does not mean every keystroke or diagram drag must make a PHP request.

## 3. Route, layout, and asset design

Implemented first routes:

| Route | Purpose |
| --- | --- |
| `/studio` | Private studio landing listing available manuscripts and recent work |
| `/studio/manuscripts/{manuscript}` | Writing workspace; selected chapter represented by `?chapter={id}` |
| `/studio/manuscripts/{manuscript}/drafts/{draft}/export` | Download a finished draft as Markdown |

Write and Drafts are views within the workspace for this increment. Compile, publication, and dedicated draft URLs can follow when their workflows need them.

Later add `/studio/worlds`, world entries, saved tool views, and maps under the same studio layout. Exact names are implementation proposals, not fixed public URLs.

Register explicit studio routes before the React catch-all. Add `studio` to the catch-all reserved paths so an unknown studio URL returns a proper failure rather than the React app. Ensure Livewire's registered update/script paths are not swallowed either; use the paths selected by the installed version rather than guessing them.

Reuse the existing session, login, email verification, password-change session validation, and no-cache behaviour. No second account system. Studio pages use `auth:web`, `auth.session`, `verified`, dashboard capability, and relevant studio permissions. Recheck authorisation inside all actions. Confirm the middleware remains enforced on Livewire updates, not just initial page loads.

Create `resources/views/layouts/studio.blade.php`, `resources/css/studio.css`, and `resources/js/studio.js`. Add these as separate Vite entry points; keep React's existing entry. Reuse deliberate brand tokens without importing React-only layout/reset rules blindly. Load Alpine once through the chosen Livewire setup. Flux handles standard controls; the existing Pro licence is configured and its components are available without a new purchase.

Use ordinary full-page navigation between React and Livewire surfaces initially. Inside the manuscript workspace, chapter selection is a coordinated component action. Do not adopt global Livewire navigation until lifecycle cleanup, browser Back, and recovery behaviour have been proven.

## 4. Manuscript screens and interactions

### Studio landing

Show the author’s private manuscripts, titles, descriptions, chapter and finished-draft counts, and last activity. New manuscript creates only a private manuscript and its first empty chapter in one transaction. Existing public novels are not copied or changed. Later onboarding must be explicit. A world shelf arrives with worlds, without blocking writing.

### Workspace layout

Desktop has a collapsible chapter column, a large writing surface, and an optional details area. Standard starting dimensions are a roughly 16–20rem chapter column and a reading width around 65–85 characters, validated against real writing rather than treated as final design.

The top bar shows manuscript identity, current chapter, server/local save state, Save, Assist, Write/Preview/Split, Focus, and an overflow menu for less frequent actions. Chapter details are not a permanent multi-field form above the prose.

Tablet uses a chapter drawer. In portrait, summary/details opens as an alternative surface rather than squeezing three narrow columns together. In landscape, a reference area may sit alongside writing if comfortable. Touch and hardware-keyboard selection must both support Assist. The virtual keyboard must not make essential controls unreachable.

Focus mode hides chapter navigation and optional areas. Escape or a visible control restores the layout. Do not hide the only indication that saving has failed.

### Chapter navigation

List active chapters in working order; identify current chapter, saving/conflict state, and whether a summary exists without showing publication badges everywhere. Show the current title as prose, not a truncated form label. Provide chapter search if the list warrants it, not a paginated ten-row management screen.

Create Chapter uses a suggested `Untitled chapter` name, empty Markdown/summary, and appends to the working order. It does not publish. It returns a stable chapter ID before local recovery begins; failed creation retains text locally under a temporary draft identity and offers retry rather than silently losing it.

Rename in details or inline with explicit acceptance. Move Up/Down is available on keyboard/tablet; optional drag handles use the same PHP reorder operation. Archive rather than permanently delete. Previous/Next uses working order. Moving between manuscripts is a normal navigation action with the same save guard.

Browser Back/Forward and deep links must select the intended chapter. Invalid or foreign chapter IDs are rejected, not silently replaced with a different book's chapter. For no selected chapter, use last local position if valid, then the first active chapter. Empty manuscripts display a purposeful Create Chapter state.

### Chapter summaries

Private nullable Markdown, not an excerpt or part of chapter prose. Author-written; no automatic AI generation. Show in details, with an optional Outline view containing chapter titles and summaries. Empty summaries do not display filler paragraphs.

Use summaries to plan before drafting or describe existing prose. They share chapter saving/recovery/conflict behaviour and are captured by named drafts. Reader projections exclude them. Exports exclude them unless an author explicitly chooses them. They can contain spoilers.

### Scenes

Do not introduce scene records during the first milestone. Support scene breaks in Markdown. Later, add optional structured scenes with stable identities when scene-level references/timelines need them. A future split must preserve the original chapter Markdown and have an explicit reversible conversion; it is not an excuse to migrate every paragraph into a record.

## 5. Implemented schema and forward migrations

The studio migrations dated 2026/10/24 and 2026/10/25 create four private tables and grant five studio capabilities to existing author/admin/superadmin roles. They have run only against the isolated local rehearsal database. Production history is unchanged. There are no database cascades or foreign-key constraints.

| Table | Business record and columns |
| --- | --- |
| `studio_manuscripts` | Author ID, title, optional description, active draft number, based-on draft ID, manuscript revision, timestamps. Author/activity index. No Book association in this increment. |
| `studio_chapters` | Manuscript ID, position, title, Markdown prose, optional private Markdown summary, chapter revision, last updater, last save UUID, timestamps and soft-delete timestamp. Active ordered-list index. |
| `studio_drafts` | Manuscript ID, draft number/name/note, captured manuscript title and revision, creator, request UUID, creation time, cancellation timestamp/actor. Unique manuscript/request UUID; indexed manuscript/cancellation/number. Cancelled completions remain recorded, allowing a fresh completion of the same draft number. |
| `studio_draft_chapters` | Draft ID, source chapter ID, position, captured title/prose/summary and source revision. Unique draft/source chapter; ordered-read index. |

The manuscript revision increments on writing and structural changes. A chapter revision increments on accepted writing saves. No content hash is needed: retries compare the accepted UUID and exact title/prose/summary. Order changes swap neighbouring positions under the parent lock; there is no temporary unique-position conflict. IDs break any unexpected position tie deterministically.

Completed drafts render entirely from captured rows. Working chapter IDs stay stable across draft generations, while the locked copies are independent records. Editing and deleting a locked model is rejected; there is no application route for either action. SQL access remains an operational privilege, not a database-level immutable ledger.

Future release tables, Book/legacy identifiers, archive controls, and world references must be introduced only with their reviewed workflows. Existing published books must receive an immutable public projection before their text is imported for private editing.

## 6. Future Book onboarding and legacy compatibility — not implemented

Onboarding is explicit and per book, not an uncontrolled copy of everything at deployment:

1. Authorise Book update and studio manuscript creation.
2. Lock the Book; check no manuscript exists; include appropriate source locks.
3. Copy all existing chapter text/title/order/archive states into working records, including unpublished writing. Preserve legacy IDs as provenance. Summaries begin empty.
4. Create the initial release from only currently public active chapters with the current title/prose/preview state and exact existing URL numbers. Snapshot public book metadata too.
5. Store the manuscript/current release linkage and mark the book managed through that linkage in the same transaction.
6. All subsequent managed-book prose, metadata, chapter order, archive/restore, and publication mutations use the new studio paths. Old React/API mutations are rejected with a useful Open in Studio response. Studio onboarding and legacy mutation checks share the Book locking boundary so a late request cannot bypass onboarding.
7. React Books links to Open Manuscript for managed books. Legacy chapter editing routes redirect or explain the move. Unmanaged books retain current behaviour during rollout.

The managed reader path builds an explicit public projection from the release; unmanaged books still use existing tables. Preserve existing public URLs and fields relied on by the React reader.

Stable routes and display order differ: existing route numbers are retained as permanent addresses, while release display order may change. Give the reader a separate route key/URL field and display number so navigation does not accidentally build URLs from reordered labels. Audit all chapter-link callers; a database change alone is not enough. New public chapters receive unused route numbers, never reuse an old retained address for another chapter. Moving a chapter out of a release leaves its old URL unavailable for that release rather than redirecting it to unrelated text. Restoring the same chapter can restore its route.

Book metadata/archive/publish operations also need managed-book guards. Existing React poetry/collections/security and the blog remain unaffected. Do not remove useful authorisation from any old endpoint.

Before deploying onboarding, compare old and new reader responses and rendered pages on a private database using a representative book. Then back up the shared production database before any authorised onboarding. If reader verification fails, stop before enabling studio editing; do not overwrite working changes by attempting a casual reverse import.

## 7. Implemented responsibility map

```text
app/Livewire/Studio/Manuscripts/Index.php       library and creation
app/Livewire/Studio/Manuscripts/Workspace.php   chapter navigation, writing, draft views
app/Livewire/Forms/Studio/ManuscriptForm.php    manuscript input
app/Livewire/Forms/Studio/ChapterForm.php       complete writing request validation
app/Livewire/Forms/Studio/FinishDraftForm.php   finish confirmation input
app/Models/Manuscript.php                     chapter creation/order, active-generation guard
app/Models/ManuscriptChapter.php              revision-aware writing save
app/Models/ManuscriptDraft.php                locked draft metadata
app/Models/DraftChapter.php                   locked chapter content
app/Services/ManuscriptDraftService.php       coherent manuscript-wide finish transaction
app/Policies/ManuscriptPolicy.php             capabilities and author ownership
app/Http/Controllers/Studio/ExportDraftController.php  finished Markdown delivery
resources/views/livewire/studio/manuscripts/  Blade screens
resources/views/layouts/studio.blade.php      separate application shell
resources/css/studio.css                     studio presentation
resources/js/studio.js                       Alpine editing buffer and save queue
```

Keep one Workspace component while its interactions remain cohesive. The editor DOM uses `wire:ignore`; Alpine owns text, selection, undo, and recovery. Save acknowledgements update revision state without replacing the buffer. A separate nested ChapterEditor is not needed yet. Extract a boundary when actual complexity or reuse justifies it.

Forms own validation, models own entity behaviour, and the draft service coordinates the whole-manuscript snapshot. No repositories, generic workflow layer, or empty future services. Middleware protects the initial page and persists session/verification checks on Livewire updates; every action also authorises the manuscript. Update responses and downloads use private no-store caching.

## 8. Exact saving and browser state contract

### Editable unit

Title, chapter prose, and optional summary are saved atomically. All three use one base chapter revision. This avoids pretending a summary conflict can never be related to a prose change. Details controls share the editor session's buffer, not a separately racing autosave form.

### Browser state

Track chapter ID, chapter and manuscript revisions, active draft generation, local change sequence, in-flight UUID/payload, save status, and pending recovery record. Browser-local state is not authorisation.

Proposed states: Clean, Changed locally, Saving, Saved to server, Connection problem, Conflict, Session expired, Validation failure. Show a short message with action rather than a technical state name in the interface.

Implemented autosave timing: local recovery on change; server save after 1500ms idle; if continuously typing, attempt a queued server checkpoint after 15 seconds. These are tuning defaults, not committed responsiveness guarantees. Only one save per editor session is in flight. Later changes queue the newest buffer.

### Save request

Call a Livewire action with active chapter identity, expected active draft number, expected revision, UUID save request, title, Markdown, and summary. The local sequence stays browser-owned. Fill and validate the Form Object once at this boundary; do not stream full chapter text to PHP on each key. Check the active draft number before accepting a save, so a tab left on a finished draft cannot unknowingly write into the next generation.

Suggested limits: title 255 characters; prose at most 1 MiB UTF-8 bytes; summary 50 KiB UTF-8 bytes. Confirm against real manuscripts and align web-server/PHP/Livewire request limits to actual encoded payloads before deployment. Existing React validation uses a different character-based bound; reconcile rather than silently truncate during import. Rejected payloads stay recoverable locally.

### PHP save transaction

1. Verify authentication/capabilities and fetch chapter through its authorised manuscript.
2. Validate fields and request identifiers through ChapterForm. Derive author/parent rather than trusting input.
3. Lock the manuscript then chapter, in a consistent order also used by structural/snapshot actions.
4. Reject a missing or archived chapter and a stale draft generation.
5. If the same accepted request UUID and payload are retried, return the prior acknowledgement without applying twice.
6. Compare expected revision to stored revision. A mismatch yields Conflict with current server revision and a route to retrieve the server text; no overwrite.
7. Persist title, Markdown, summary, incremented revision, UUID, updater, and timestamps; increment manuscript revision.
8. Return the accepted chapter revision, active draft number, and manuscript revision. The browser associates this with its in-flight request. Do not replace the current local buffer.

Exact payload retry comparison never replaces revision checks for divergent content. New typing during a request remains locally dirty; an older successful response acknowledges only the text it actually saved.

### Recovery

Use account/manuscript/chapter/draft-scoped localStorage recovery with title/prose/summary, base revision and time. Network retries retain their UUID while the page stays open; after reload revision checks safely handle any uncertainty. Prefer IndexedDB for asynchronous storage once volume warrants it; retain the existing recovery logic as behavioural reference, not a drop-in overwrite-on-load mechanism.

On load, compare recovery title/prose/summary against the server content. If identical, clear redundant recovery. If recovery contains newer unsynchronised work, offer Restore local / Keep server / Compare and copy. If both diverged, use conflict review. Do not silently replace server writing based only on local timestamps. Expose storage failure and offer immediate file download/copy recovery. On logout, stop work and do not expose another account's recovery; offer deliberate local removal when work is safely saved.

### Switching and navigation

Selecting another chapter requests a save checkpoint. On success, switch and update URL/restore the target's local position. On failure, retain the current editor and offer retry or an explicit decision to keep local recovery and leave. Never label that departure server-saved. Browser close prompts are best effort; local checkpoints and server saves carry the actual safety responsibility. The current chapter query replaces the URL rather than creating chapter-by-chapter browser history. Explicit chapter navigation is guarded; richer Back/Forward behaviour remains follow-up work.

### Conflict resolution

Stop autosave. Preserve the local candidate and current server text separately. Show differences and copy/export choices. Initially, download the local candidate, reload the latest server version, then deliberately use local recovery or combine the text. The subsequent save checks the freshly loaded revision again. An in-place merge UI remains future work.

## 9. Markdown and writing assistance port

The first toolbar implements heading, bold, italic, quote, and list, with ordinary Markdown available for links, rules, and line breaks. Preserve Markdown punctuation and user-authored whitespace. Add Ctrl/Cmd+B, I, and S where supported without breaking browser/editor behaviour. Tab indentation is only introduced after checking keyboard accessibility.

Implement Write/Preview/Split. Preview requests use an authenticated Laravel rendering boundary with the same Markdown dialect and escaping policy as export/reader rendering. Explicitly disallow unsafe raw HTML and unsafe URL schemes. Debounce preview; discard stale preview results using the editor sequence. Preview failures do not stop writing/saving. First slice may use explicit Preview rather than a continuously refreshed split if that gives a safer small increment.

The first editor is a textarea, so it cannot contain inline coloured world-reference decorations. That enhancement requires a different input implementation later. Pick one only after testing Markdown source fidelity, IME composition, tablet selection, undo, UTF-16 offsets, performance, and Livewire lifecycle. No rich-text editor migration that makes HTML/JSON the new source of truth.

Retain `/api/studio/writing-assist` initially because the existing PHP controller already handles provider credentials, timeout/errors, and UTF-16 offsets. The new JavaScript adapter sends selected text using the current session/CSRF mechanism and existing validation constraints. It is not a React dependency.

Save selected text, exact range, chapter ID, and editor sequence before requesting. Show provider feedback beside the editor or in a focused drawer, never replace the manuscript with results. A suggestion may modify only a matching still-current selection; typing, switching chapters, or changing selection invalidates stale replacement actions. Keep replacement in the editor's undo history and mark it dirty for normal save.

Provider errors are displayed locally; no manuscript mutation. Existing assistance remains an external service as currently configured; it does not become offline or subscription-backed Codex/Claude analysis by changing the UI.

## 10. Structural operations, drafts, and releases

Current creation checks the expected active draft; movement checks the manuscript revision. Both lock the manuscript. Future archive/restore must follow the same lock discipline. Content saves and snapshots use the same manuscript lock discipline to produce coherent state. Lock duration stays short; do not render exports or call external providers inside database transactions.

Creation returns the new chapter payload. Move Up/Down resolves siblings inside the authorised manuscript and swaps their positions under the parent lock. A stale manuscript revision rejects the move. Complete-set drag reorder, archive, and restore are not implemented.

Finishing the active draft first checkpoints local work. Then one PHP transaction locks the manuscript, captures a coherent ordered set of chapter rows and manuscript metadata into immutable finished-draft rows, and advances the active number/basis. The active chapter content remains an identical starting copy for the next draft; stable working chapter IDs need not change. Record manuscript revision and source chapter revisions. Increment manuscript revision on the finish transition so concurrent stale finish/structure operations cannot advance the number twice. Unsaved changes on other devices are not magically included; state clearly that the finished draft captures server-saved work.

Draft labels need not be unique; a generated ordinal and ID identify the draft. Prevent accidental double-submit with a request identity. Autosave updates the one active draft and never finishes it. Reading a finished draft loads its own data, not live chapters. Its text and captured metadata remain locked. Future restoration must preserve pre-restore work as a separate recovery checkpoint, then starts active work from the chosen content/order without unlocking the historical draft or changing the public release. Distinguish recovery identities from the normal completed-draft numbering sequence.

Future publishing must checkpoint and preview an exact candidate. At final confirmation, verify the same manuscript/structure revisions; if anything changed, require a refreshed preview. Insert a complete immutable release manifest, even for a one-chapter update, then switch the current pointer atomically. Unpublish stops reader visibility without deleting drafts/releases. Working changes remain private. An earlier release can be selected through an explicit rollback flow, with reader URLs evaluated against that release's manifest.

Do not make irreversible chapter deletion part of normal studio UI. Archive does not remove content from retained drafts/releases. Explain the difference between archiving working material and withdrawing a public release.

## 11. Later data and component boundaries

These are the later responsibility decisions needed to avoid painting milestone 1 into a corner. Do not create all the tables upfront.

### Worlds and reference material

World owns entries; manuscript attaches to a world through an explicit association. Entry has stable ID, world, type, name, summary/body Markdown, lifecycle, revision, and authorship. Alias is its own record with normalised lookup text; uniqueness is per entry, allowing the same phrase to refer to multiple entries. References store source manuscript/chapter identity, target entry, confirmed text/anchor context, and status. Names and aliases never define identity.

Use entry-level semantic status by default and explicit status-bearing notes where canon, intent, analysis, alternatives, and questions coexist. Statements important enough for knowledge/continuity become identifiable claims. Normal prose stays prose. Do not create a claim row for every sentence.

World revision history and named world snapshots preserve old drafts' context. Start with complete snapshot manifests and immutable entry versions when needed; avoid global event sourcing. Attach snapshots explicitly to manuscript drafts. Current lore and historical lore must be distinguishable in assistant responses.

Selection-to-entry is a small Livewire form/dialog with the selected text retained by the editor. Entry creation uses a transaction, then the editor creates a reference. If entry creation succeeds and linking fails, retain the entry and offer retry, not a duplicate entry on each attempt. Shared aliases and changed anchors require author confirmation. Search indexes are derived, not the world database.

### Reusable analytical tools

Saved tool view stores world/project scope, tool type, title, settings schema version, subjects, and attachment contexts. Stable entry/event/claim identities are referenced independently of labels. Typed tool records represent important searchable data; JSON can hold layout/settings, not an opaque copy of the entire world.

Alignment placements record chart axes, linked subject or temporary author-defined subject, position/cell, reasoning, and optional temporal context. A comparison table defines columns and rows with values, units where relevant, unknowns, and source links. Relationship edges record type, direction, participants, semantic status, and optional phases. Investigations link evidence, claims, hypotheses, counter-evidence, questions, and decisions. Dependency diagrams record author-declared dependencies; they are not automatic simulations.

Each Livewire screen owns filters, selection, forms, and saved state. JavaScript owns drag/zoom/layout. Dragging does not send every pixel to PHP; save a validated final placement. Wrong-world IDs are rejected server-side. An alignment chart can attach to a philosophy, institution, manuscript discussion, or character comparison without being owned by one category.

### Events, calendars, arcs, and knowledge

Events support exact world dates, ranges, relative ordering, or unknown dates. Store world-calendar values separately from machine timestamps. Calendar definitions describe units/eras/recurrence and uncertainty; do not force non-Gregorian dates into SQL Gregorian date columns. Machine timestamps remain ISO; ordinary human interface dates use yyyy/mm/dd; fictional calendar labels express their own units.

Narrative appearances link events/claims to chapters and later scenes. Character knowledge changes reference claims and events, distinguishing knows/believes/suspects/misunderstands/unaware. Relationships can have time-bounded phases. Arc beats and plot pressures link to actual occurrences without dictating a compulsory storytelling formula.

Deterministic checks report explicit contradictions such as cycles or impossible ordering. Broader literary/moral consistency remains a question for author discussion, not a claimed proof.

### Geography

Map record declares type, coordinate system, units, calibration, and revision. Map assets and annotations are versioned. Placement connects a world location to a map revision; the same location can appear on multiple maps. Routes are separate from direct-distance measurements. Travel assumptions specify method, speed, terrain, stops, and world rules; estimates expose uncertainty.

Blank schematic maps support authoring points, regions, connections, and relative constraints before any illustration exists. Imported images can be calibrated. Planetary/astronomical maps need appropriate measurements; do not apply a city ruler to interplanetary movement. Parent/child maps link through shared place identity; coordinates do not automatically convert between unrelated drawings.

Map discussion records capture the relevant revision, selected area, proposed changes, accepted decisions, and alternatives. Updated Wonderdraft exports require marker alignment/review. File formats, direct automation, and image-to-editable-map fidelity remain unproven and must be investigated with actual files.

### Assistant connections

Add an authenticated MCP delivery boundary through supported packages/protocol components once their fit is checked. Reuse authorised Laravel model workflows; do not hand assistants database credentials or a public private-data endpoint. Initial tools are read-only search/fetch/traverse/compare and include source links, revision IDs, semantic status, scope, and explicit truncation/completeness metadata.

A review session pins a draft/world snapshot or captures a server-saved review manifest. Multi-part novel retrieval must not mix later revisions. Images/maps require a supported visual delivery mechanism and text annotations; a tool that returns only text cannot claim to have supplied visual map understanding.

### Authorised editorial mode — added requirement

An editor account capability does not grant access to every author's work. The author grants a particular editor read/comment/suggest access to a named draft or explicitly selected active manuscript. A grant records manuscript, optional draft scope, grantee, granting author, allowed capabilities, and revocation. Draft-specific grants do not automatically expose later drafts, working text, private summaries, or the whole world encyclopedia. Summary access must be an explicit grant capability.

Review records identify the editor and an immutable reviewed draft/revision. Proposed changes record source chapter/revision, original text, replacement text, anchor context, proposer, state, and author decision. Comments can attach to a passage or chapter and have replies/resolution. Accepted changes record the accepting user and resulting working revision. Use lookup tables for changeable review statuses/classifications; do not introduce database enums.

Completed drafts remain immutable even during editorial review. Suggestions are separate records; the author applies accepted changes to the active manuscript. Changed passages require reconciliation rather than applying stale offsets. Direct editor overwrite is excluded initially. Finishing, publishing, managing grants, and ownership transfer are not implied by editing capability. Revocation stops further reads, exports, and writes, although it cannot recall previously downloaded material.

Plan records and policies for this requirement now; deliver its interface after save/conflict/draft behaviour is proven. Do not advertise a working editorial mode before grants, suggestions, decisions, and revocation have been implemented and verified.

Scopes are revocable and per account/world/project. The separate connector authentication does not change session-only Sanctum's existing browser rules. No paid inference endpoint is added for this feature. Later proposal capture is write-to-review only; it does not silently promote analysis to canon. Actual Codex/Claude client connectivity must be demonstrated before promising a particular linking UX.

## 12. Export and import boundaries

Compile chooses current server-saved work or a named draft, all chapters or a subset in manuscript order, and a format. Show prose/summaries/notes inclusion separately. Private author export needs no ISBN, legal boilerplate, or storefront metadata. Named draft export reproduces that draft even after current work changes.

First deliver a Markdown file/archive and PDF built from a stable compile manifest. A format library/renderer is selected through a representative typography/Unicode/large-manuscript proof, not chosen silently in this plan. Generate outside the snapshot transaction; queue heavy work where needed, store output privately, and return an authenticated expiring download. Pending export shows the source revision and status. Add DOCX/EPUB later.

Campfire and old Story Vault imports start as private staged previews with counts, provenance, warnings, duplicate handling, semantic boundaries, and no automatic overwrite. Inspect actual exports first. Named placeholders are valid material; distinguish repeated template text from authored lore with review rather than silently declaring it canon or deleting it. Maintain rollback/recovery for imports.

## 13. Delivery work packages and reviewable outputs

| Increment | Scope | Current state / review gate |
| --- | --- | --- |
| A: private manuscript studio | Separate Livewire/Flux shell, manuscript creation, chapter list/order, Markdown/preview/Assist, summaries, autosave/recovery/conflicts, locked draft handover, comparison and Markdown download. | Implemented locally. Review the real writing screen, run the focused manuscript charter, approve a small private release. |
| B: writing refinements | Real tablet feedback, archive/restore, draft restoration with safety checkpoint, manuscript details, richer comparison and compile formats. | Design against actual writing needs after A. No placeholder controls. |
| C: public novel adoption | Explicit Book onboarding, immutable reader releases, legacy mutation guards, stable public URLs, deliberate publishing. | Required before moving existing public novels into this studio. Rehearse reader equivalence first. |
| D: editorial collaboration | Author-controlled grants for selected drafts; comments and proposed revisions; actor attribution; author acceptance into active work with conflict checks. | Requirement accepted; no grants or editor access shipped in A. Design permissions and review interaction together. |
| E: worlds and references | Shared worlds, entries and aliases, search, quick selected-text creation, Markdown-preserving highlighting. | Demonstrate writing a name, creating/linking it, and using an alias across stories. |
| F: thinking tools | Reusable alignment chart and comparison first, then relationships/investigations/dependencies. | Prove the same tool useful for two different real concepts. |
| G: time and narrative | Events, calendars, timelines, arcs, relationship phases, knowledge and reveals. | Explore a real sequence and its different narrative exposure. |
| H: geography | Imported and blank schematic maps, placements, scales/routes/constraints, revisions and Wonderdraft exchange. | Place writing-created locations and evaluate a journey with explicit scale assumptions. |
| I: assistant access and depth | Authorised account-based retrieval, fixed snapshots, reviewed proposals; specialised magic/ecology/belief/language tools. | Both clients can analyse the same chosen manuscript/world without silently changing canon. |

World/reference work can move ahead of public onboarding if that better serves Tapuwa’s writing. Detailed future boundaries are in section 11. No delivery-date promises until the actual writing and device review establishes what needs refinement.

## 14. Acceptance matrix and review method

This is a planning matrix, not a set of automated tests or one giant manual charter. At implementation time prepare one workflow charter per request using the repository's required modes.

| Workflow | Required evidence |
| --- | --- |
| Onboard existing novel | All prose/order/archive states copied; old URLs and public content unchanged; retry creates no duplicate manuscript. |
| Chapter save | Title/prose/summary preserved; correct revision increment; invalid/foreign inputs rejected; cursor not reset. |
| Assist | Correct selected Unicode range; accepted replacement undoable; stale request cannot replace newer prose. |
| Chapter switch | Saved or explicitly recoverable current text; target loaded once; URL/Back/Forward correct; late response cannot mutate target. |
| Device conflict | Two sessions based on the same revision produce one accepted edit and one conflict; both candidate texts retained. |
| Recovery | Refresh/network/storage failure/session expiry preserve work and accurately label server state. |
| Reorder/archive/restore | Complete valid structure under concurrency; retained drafts/releases unaffected; no foreign chapter injection. |
| Draft | Snapshot coherent; unsaved work clearly handled; compare/export/restore uses snapshot records; restoration preserves prior state. |
| Publish | Preview revisions match commit; private summary excluded; old release immutable; working autosave never publishes. |
| Tablet | Hardware/virtual keyboard, selection, scrolling, focus mode, chapter drawer, and assistance remain usable. |
| Legacy regression | Poetry, collection reader, login/security, unmanaged books, and blog remain functional. |
| World/reference | Ambiguous aliases, renamed entries, edited anchors, archived targets, and cross-world access handled visibly. |
| Tools/maps | Real inputs with incomplete dates/unknown values; validated saved state; source revisions and units clear. |
| Assistant | Access/revocation enforced; same snapshot retrieved throughout; partial coverage explicit; proposal cannot silently become canon. |

Run PHP syntax/style/dependency checks, frontend lint/build appropriate to changes, actual request/scratch verification, stored-state review, and Tapuwa's manual sessions. No new automated tests unless explicitly requested. Report what was actually exercised and what remains unverified. Existing unrelated lint findings do not disappear because this plan exists.

## 15. Rollout, rollback, and first work order

Work on dev with focused commits. Do not auto-deploy every studio change to production. Use an isolated development database and synthetic shared-account data; do not repurpose the old local database without Tapuwa's decision.

Forward migrations are additive where practical. Rehearse against the shared schema. Production changes require an authorised release, backup, migration check, and compatible code/schema order. A code rollback after onboarding cannot simply send readers/editors back to stale legacy rows. Keep the new reader boundary available or deliberately repoint to a verified retained release. Never use a shared-database rollback/fresh command as a recovery strategy.

Backups must include private files and retained snapshot assets, with off-server storage and restoration evidence. First verify what Anubis already provides; do not claim a local SQL file is an off-server disaster-recovery plan.

Current next work order:

1. Review the actual private studio’s writing, chapter list, summaries, Drafts view and narrow layout.
2. Complete the focused manuscript charter and apply findings without resetting the database.
3. Review the diff once, back up the shared database, and release the approved private increment through master/Forge. Follow `STUDIO_IMPLEMENTATION_STATUS.md` for the release sequence.
4. Refine tablet/recovery/archive needs from real use, then choose editorial review, worlds, or existing-novel onboarding as the next bounded increment.

The initial production release provides private new manuscripts only. It does not import, republish, or replace existing public novels. All later changes to deployed studio schema use new migrations.

## References checked for implementation feasibility

- https://livewire.laravel.com/docs/4.x/javascript — JavaScript integration and DOM ownership.
- https://livewire.laravel.com/docs/4.x/security — action input/public property authorisation.
- https://fluxui.dev/docs/installation — installation, assets, optional paid tier.
- Existing repository guides: PROJECT_CONTEXT, ARCHITECTURE, LARAVEL_LIVEWIRE, DATABASE, and MANUAL_TESTING.

The package resolver and actual installed APIs must be checked at implementation time. Reading documentation is not an integration proof.

Undo finish was approved and implemented on 2026/10/25. Only an untouched next draft permits cancellation of the latest completion. See STUDIO_DRAFTING_DESIGN.md for concurrency, retained cancellation history, and reused-number semantics.

First world/magic increment implemented locally, 2026/10/25: World/MagicSystem
models, shared notes and aliases, rule decisions/details, rule-linked cases,
explicit manuscript variations and reviewable source changes, immutable system
versions, and pinned magic context on finished manuscript drafts. This is not the
full encyclopedia. See WORLD_MAGIC_WORKSPACE.md for actual limits and evidence.
No Anubis release is planned at this stage; collect Tapuwa’s feedback first.


## World development expansion · 2026/10/25

Local dev now includes Characters & gods (combined custom roles/aliases), fictional
chronology with participants, arcs with linked events and Flux Pro line charts, and
reusable alignment charts with custom axes and reasoned placements. New forward
migration: `2026_10_25_000003_create_world_development_tools.php` (seven tables).
New finished drafts copy these world records into their retained world context.
See `WORLD_DEVELOPMENT_TOOLS.md` for delivered behavior and limits. No production
deployment; Tapuwa will play with the local tools before further design feedback.


Local increment, 2026/10/26: encyclopedia entries and manuscript passage references
now support selection-based creation/linking/aliases and preview recognition. Private
studio records have recoverable archive/restore tools; old queued chapter saves are
invalidated. No cascading deletion or permanent purge. See
ENCYCLOPEDIA_REFERENCES_AND_ARCHIVING.md (under docs/) for actual scope and limits.
Raw-editor inline highlighting, maps and editorial collaboration remain future work.
Production remains unchanged.

Local increment, 2026/10/26: named relationships and author-defined comparison tables
are implemented with same-world subject links, archived-link retention, revision
checks, archive/restore and finished-draft copies. See
WORLD_RELATIONSHIPS_AND_COMPARISONS.md for delivered scope. A whole-world network
graph, semantic claim status and formal time phases remain future refinements.

Local compile increment, 2026/10/26: the first bounded Markdown/PDF compile path is
implemented with source/chapter selection, explicit summaries, stable manifests,
a private expiring download, saved preferences and cleanup. Browsershot replaces
the initial renderer choice at Tapuwa's request. See MANUSCRIPT_COMPILE.md for
limits and verification. Very large queued exports, DOCX/EPUB, onboarding and
publishing remain later work; this does not approve an Anubis release.
