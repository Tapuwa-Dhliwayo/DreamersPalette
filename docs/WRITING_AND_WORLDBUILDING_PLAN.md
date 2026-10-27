# DreamersPalette writing and worldbuilding plan

Current delivery and remaining review: [completion ledger](STUDIO_REMAINING_WORK.md), updated 2026/10/26. Historical scope/status statements below do not supersede that ledger.

Updated: 2026/10/24.
Status: long-term scope for critique; the first private manuscript increment is now implemented locally on dev. See [implementation status](STUDIO_IMPLEMENTATION_STATUS.md) for the exact delivered scope and remaining release work.
Development branch: `dev`. Production branch: `master`.

The later architecture decision is Laravel + Livewire for the new studio, without React components embedded in it. Read [LIVEWIRE_STUDIO_IMPLEMENTATION_PLAN.md](LIVEWIRE_STUDIO_IMPLEMENTATION_PLAN.md) for the current implementation proposal. Where the documents differ, that proposal supersedes the implementation choices here. This document remains the record of broader product scope.

## 1. Product direction

DreamersPalette is Tapuwa's personal writing, worldbuilding, analysis, and publishing environment. Worldbuilding is a primary activity, not supporting metadata for a novel. A world may exist without a manuscript and support multiple stories.

Build usable increments, beginning with a manuscript workspace around the Markdown editor already present. Preserve poetry workflows and public publishing while adding the studio.

The intended experience:

- Open a clearly separated project and resume work.
- Write Markdown without a document-formatting workflow taking over.
- Navigate chapters without returning to a management list.
- Develop world entries either deliberately or in the middle of writing.
- Use real analytical and organisational tools across subject categories.
- Preserve working text, named drafts, world decisions, and public releases separately.
- Let existing Codex and Claude accounts retrieve authorised material for discussion, without adding model API calls to DreamersPalette for this workflow.
- Export selected writing quickly and publish deliberately.

There is no requirement for an App Store app. PC and tablet browser use are primary for novels. Google Keep remains a valid poetry capture tool on iPhone.

## 2. Existing implementation and boundaries

The application is Laravel 13, React 19, MySQL, and Tailwind. It runs on Anubis alongside the blog in the shared database. Laravel publishing is now live, Cloudflare cutover is complete, and Tapuwa reports Vercel removed. Older migration documents still describing deployment as pending are historical and need updating during implementation preparation.

Reuse:

- `resources/js/components/editor/EditorPanel.jsx`: Markdown input, formatting actions, preview, selected-text assistance, and replacement behaviour.
- `WritingAssistant.jsx`, selection hooks, and existing assistance service.
- Existing Book and Chapter models, studio APIs, policies, permissions, reader routes, themes, and public slugs.
- Existing local draft recovery and unsaved-navigation handling as starting points.

Current limitations identified from the code:

- Chapter editing is a form above an editor; saving returns to the chapter list.
- Chapter navigation is outside the editor workspace.
- Chapters have no private summary field.
- Browser draft recovery is not server autosave or multi-device conflict protection.
- Public chapter content comes from the same `content_md` field edited in the studio.
- The editor is a textarea with a persistent separate preview, not an inline reference-aware editing surface.

Use new forward migrations. Do not rewrite deployed migrations or reset the shared database. Retain standard Laravel authorisation and the existing shared-account boundary. Planning this work does not authorise production deployment or data replacement.

## 3. First delivery: the manuscript workspace

### Opening and layout

Open a novel from Books and enter its manuscript workspace. The selected book is the context, so the main screen no longer asks which novel each chapter belongs to.

Desktop:

- A collapsible chapter navigator on the left.
- A dominant Markdown writing surface in the centre.
- An optional details or reference area on the right, opened for a specific task.
- A restrained top bar containing novel/chapter identity, save state, Assist, preview mode, focus mode, and manuscript actions.

Tablet:

- Chapter navigation opens as a drawer.
- Writing gets the available width by default.
- Details and references adapt to a drawer or adjacent area depending on space.
- Opening the keyboard must not hide essential saving feedback or make chapter selection unusable.

Avoid redundant application and manuscript sidebars competing for space. Focus mode hides navigation and optional controls while retaining an accessible way back.

### Chapters

Show chapters in manuscript order, with full titles accessible and the current chapter clearly selected. Provide quick creation and selection, previous/next navigation, and a way to resume the last chapter and position on that device.

Allow an empty chapter and an automatically suggested title so capturing an idea does not require completing a form. Renaming can happen later. Do not silently invent a meaningful title from the prose.

Support moving chapters with explicit move-up/down controls, including on tablet. Dragging is an optional enhancement, not the only way to reorder.

Ordering must be updated atomically. Current chapter numbers form reader URLs and have a per-book uniqueness rule. Reordering private work must not renumber already published reader URLs. Public release ordering therefore needs to be independent of working ordering, with an explicit publication decision for URL changes.

### Optional chapter summaries

Add a private Markdown summary, initially empty and optional. It is an author aid, not a public excerpt and not part of the chapter body.

Show summaries in chapter details and optionally in an outline view of the whole manuscript. Allow writing a planned summary before the chapter exists as prose, or summarising a chapter afterwards. The app does not automatically generate or rewrite summaries.

Summary editing uses the same save protections as chapter prose. Summaries are captured in named drafts and are excluded from normal manuscript exports and reader APIs unless explicitly selected for an author export.

### Editor and Assist

Preserve the current Markdown editor and the assistance already present. Default to Write mode; offer Preview and Split rather than permanently dividing the writing area. Keep existing formatting actions accessible without making a large toolbar dominate the screen. Verify shortcuts and behaviour on both keyboards and touch selection.

Retain selected-text assistance and explicit acceptance of replacements. The existing grammar/provider workflow is separate from the future Codex/Claude connection: this plan adds no new model API analysis flow. Unaccepted suggestions never alter prose. If text changes while assistance is pending, stale suggestions must not replace the wrong range.

This first delivery does not replace the editor engine merely to prepare for distant features. Before inline world highlighting, assess whether its current input can support it reliably. If not, replace only the editor input with a Markdown-capable implementation, preserving stored Markdown, selection, shortcuts, preview, and Assist behaviour.

### Saving and recovery

Provide Save without leaving the workspace, followed by server autosave after an idle interval. Never label browser-only recovery as saved to the server.

Use per-chapter revision checks so an older tab or device cannot silently overwrite newer server writing. Identify save requests and avoid applying responses from one chapter to another after switching. Chapter prose and summaries must have clear, consistent save boundaries.

On a connection interruption, retain local recovery and show pending server changes. On reconnection, check revision conflicts before saving. On a conflict, retain both texts and let Tapuwa choose or reconcile them. Recovery must not silently overwrite newer server content. Handle local storage failure explicitly.

Chapter switching must either complete the save or preserve and identify pending work. Keep a manual save action. Save synchronously to the server before taking a named draft snapshot; if that fails, do not pretend the snapshot includes unsaved local text.

This is recoverable interruption support, not a promise of fully offline multi-device editing of every tool.

### First-delivery completion criteria

Tapuwa can open a real novel, create and navigate chapters, write Markdown, add optional summaries, use Assist, preview, save without leaving, recover after refresh, and recognise server/local save states. Conflicting tabs cannot silently replace each other's writing. Existing public content remains stable while private prose is edited. The workspace is comfortable on PC and tablet, and poetry editing still works.

## 4. Working text, named drafts, and published releases

Separate three concepts:

1. Working manuscript: current editable content.
2. Named draft: an immutable manuscript milestone such as Draft 1 or War restructuring.
3. Published release: the content made visible to readers.

Before enabling manuscript autosave for already published chapters, introduce a preserved public representation. Backfill it from existing published content and compare reader output before switching the reader path. Editing working content afterwards must not change public prose or reader URLs.

This initial public snapshot boundary is a prerequisite, not something deferred until the end of the programme. Later release management adds a fuller preview and whole-manuscript release workflow.

Named drafts capture ordered chapter identities, titles, Markdown, private summaries, and relevant manuscript metadata. Once world references exist, capture their associations too. Once worlds exist, drafts can be associated with preserved world snapshots rather than a pointer to mutable current lore.

Allow reading, export, comparison, and restoration. Compare chapter additions/removals, order, prose, and summaries. Preserve the working copy as a recovery milestone before restoration. Retained draft content must survive chapter archiving or deletion from the working manuscript.

Use full snapshots initially, retaining immutable references to assets; avoid a custom version-control system. Review storage and retention based on real usage. Named drafts are never discarded by automatic recovery cleanup. Full parallel draft branching and automatic merging are outside the initial scope.

## 5. Projects, worlds, and manuscript organisation

Project is the user-facing workspace. World holds shared material. A manuscript may attach to a world; the world may serve several manuscripts. Series is optional manuscript grouping.

Start the first delivery from existing Books; do not require a project/world setup wizard before writing. Add the richer project shelf when shared worlds arrive, carrying existing books into it without changing identities or URLs.

Avoid duplicate editable copies of the same world entry per book. Model time-specific developments and story notes separately. Explicit alternate versions may be supported later; world sharing does not mean every project can see all private material automatically.

The project opening should support resume, search, recent work, and deliberately marked open questions. Charts appear when they help, not as mandatory dashboard decoration.

## 6. World material and reference creation

World entries support characters, places, species, cultures, factions, resources, items, deities, systems, languages, beliefs, and concepts. Types organise material; they do not restrict which tools can use it.

Create an entry with a name and type only. Add prose, aliases, links, images, and specialised details progressively. Use search, filters, and optional tags; folders are optional.

From selected manuscript text:

- Link to an existing entry.
- Add the wording as an alias of an existing entry.
- Create a new entry and link it immediately.

Preserve manuscript wording and stable entry identity. Highlights are an editing aid, not intrusive markup in Markdown exports. Store explicit reference associations separately and recheck their text anchors after edits. Substantial edits may make a reference unresolved; never silently redirect it.

Name recognition is advisory. Match word boundaries and longer phrases first. Shared aliases such as the capital require disambiguation, possibly informed by scene context. Allow dismissing individual false matches. Do not promise infallible contextual understanding without analysis.

Entries and important notes distinguish canon, author intent, exploration, analysis, open questions, and discarded ideas. Do not force a classification on every sentence. Distinguish story truth from in-world claims, official history, rumours, and character beliefs. Preserve who decided something and its revision history where useful.

## 7. Reusable tools rather than category panels

A tool is a working instrument with defined behaviour, not a blank text panel. A saved use of that tool stores its scope, chosen subjects, layout, reasoning, and links. It can be opened independently, attached to an entry, or consulted alongside writing.

Examples:

| Tool | Behaviour and reuse |
| --- | --- |
| Alignment chart | Named axes, linked subjects, placement, reasons, uncertainty, and optional historical variants. Use for characters, institutions, philosophies, strategies, or other comparable subjects. |
| Comparison table | Compare chosen properties across selected subjects while preserving prose and unknowns. Use for magic classes, species, governments, artefacts, or competing accounts. |
| Relationship diagram | Typed links, direction, evidence/reasoning, phases, filtering, and appropriate family or network layouts. |
| Timeline | Relative or exact chronology, parallel tracks, causes, consequences, and distinct narrative exposure. |
| Arc/plot view | Intentions, turning points, pressures, and resolution linked to scenes/events without requiring a prescribed story formula. |
| Investigation board | Observations, hypotheses, testimony, competing explanations, decisions, and unresolved questions. |
| Dependency diagram | Explicit causes, requirements, effects, assumptions, and exceptions. A diagram does not imply a simulation. |
| Quantitative chart | Meaningful values, units, labels, and source data. Pie charts require parts of a defined whole; do not invent numerical precision for subjective judgements. |
| Calendar | World-specific units, eras, recurrence, and approximate/unknown dates linked to events. |
| Geography workspace | Map creation/import, linked locations, scale, routes, hierarchical navigation, constraints, and revisions. |

Do not build a universal configurable application builder. Provide coherent tools and useful starting configurations, with enough flexibility for Tapuwa's uses. Specialised magic, ecology, belief, language, item, and societal workflows can combine reusable tools with genuinely domain-specific behaviour. We are not recreating a panel dashboard.

## 8. Geography and map development

Support starting from a blank geographical workspace, a sketch, a map image, or an existing map export. Do not require a finished Wonderdraft map before reasoning about geography.

Recognise different map types: city/local, regional/continental, planetary surface, and astronomical charts. An astronomical chart is not a planetary surface with different labels. Define coordinate and measurement behaviour appropriate to each type.

Connect scales through locations: system, planet, continent, region, city, building. A location may appear on several maps while retaining one world identity. Moving between maps follows those links; geometrical transforms between independent maps must be defined or treated as approximate, not assumed.

Locations created while writing appear in an unplaced list. Place manually, record a relative constraint, or ask an assistant for a proposed placement. No automatic coordinates inferred merely from recognising a name.

Add calibration and measured routes before claiming distances. Distinguish direct distance from route distance. Travel estimates require a method, speed assumptions, terrain, stops, and relevant world rules. Show uncertainty and allow fantasy transport. Link journeys to scenes and compare declared durations with estimates; a discrepancy is a question, not an automatic correction.

Examples of constraints: inside the capital; upstream of the port; separated by a mountain range; two days by riverboat. Conflicting constraints stay visible for author resolution.

Maintain map revisions, discussions, annotations, decisions, and alternatives. Replacing a map image must not silently misplace all existing markers. Preserve the old version and provide alignment/review before carrying markers across.

Use Wonderdraft for detailed cartographic drawing when desired. Inspect its actual supported formats and Tapuwa's original files before promising editable imports, direct control, or automatic synchronisation. Initially support versioned image exchange and, where available, reference layers. A flattened Campfire image cannot be assumed to contain editable Wonderdraft objects.

The first blank-map implementation is a practical schematic workspace with placements and routes. A Wonderdraft-equivalent painting/terrain engine is a separate scope decision, not hidden inside the word map.

## 9. Designing against real concepts

Use Tapuwa's actual material as design cases:

- Dawn of the Moon: Four Kings comparisons, prophecy lines and interpretations, Focus rules/exceptions, relationship phases, war routes, and shifting city control.
- Way of Kings: compare gifts, tests, dangers, and consequences; explore conviction, relationship, foresight, and accountability using the same chart tools.
- Inversion: trace biology, portal engineering, transit restrictions, supply, security, and institutional history together.
- The Wilds: investigate observations, testimony, stolen attribution, spiritual obligations, and competing interpretations.
- Man of Sorrows: connect ecology, magical damage, protection, political dependence, intentions, and reputation.
- Journey to the Center: compare events, official findings, omitted context, and species/infrastructure compatibility.
- The Authors: explore alternative world rules and their benefits, consequences, and unresolved moral costs.

These are design examples, not new canon or automatic assignments of meaning.

## 10. Assistant access and the discussion loop

Use authenticated MCP access from the actual Codex and Claude surfaces Tapuwa uses. Prove connectivity early with a small read-only example; deliver full retrieval after stable manuscript/world identities exist. Do not add paid model API analysis to the website for this purpose.

Capabilities: discover allowed projects; search material; retrieve current chapters or a named draft; retrieve summaries, entries, connected references, timeline portions, maps, and recorded questions; compare selected revisions.

Return revision identity, source links, semantic status, and clear completeness/coverage information. Retrieve large manuscripts in parts instead of silently truncating them. Pin a review to stable revisions so later chapter requests do not mix incompatible versions.

Allow Tapuwa to choose access scopes and revoke a connection. Protect private material with supported authorisation components, enforce access on every request, and keep public reader delivery separate.

Analysis happens in the assistant. Connecting it does not provide automatic access to all chat memory, bypass usage limits, or embed subscription inference inside the app.

Later, support saving selected discussion/analysis and reviewable proposals. Do not automatically capture every conversation or overwrite canon. Promoting a proposal must check whether its source revision has changed.

Official references checked during planning:

- https://developers.openai.com/plugins/concepts/mcp-server
- https://developers.openai.com/plugins/build/auth
- https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp

Actual account/client configuration remains to be verified.

## 11. Compile, export, and publishing

Compile current work or a named draft. Choose all chapters or a subset, retain manuscript ordering, and show exactly what is included. Default to manuscript prose; optionally include summaries, notes, or a world-reference supplement for author analysis. Keep spoilers and author intent explicit.

Deliver Markdown and PDF first, then DOCX and EPUB. Preserve Markdown source as a portable export. The existing PDF discussion workflow is a core use case, not an afterthought. Save export preferences without requiring publishing metadata for a private draft export.

Publish through a distinct preview and release action. Capture content and ordering deliberately, preserve existing URLs, and expose differences between working and published material. Release updates are explicit; autosave and saving Draft 2 never publish.

## 12. Technical approach

Keep Laravel + React and the existing storage and authentication conventions. Use native database IDs, policies, Form Requests, rich models, and focused cross-model services only where warranted. Extend application-facing frontend services without scattering persistence into components.

Introduce data structures in the milestone that needs them: private chapter summaries; revision checks; public snapshots; manuscript drafts and draft chapters; worlds/entries/aliases/reference associations; links and events; saved tool views and their typed data; maps/placements/routes/revisions; assistant grants.

This is a responsibility map, not an instruction to create every table immediately. Avoid an enormous EAV schema, unnecessary services, custom event sourcing, or a graph database simply because graphs are displayed. Use JSON for cohesive tool settings or snapshot payloads when appropriate, not as a substitute for important searchable relationships.

Keep drafts, private summaries, source snapshots, and world data out of reader APIs and public asset paths. Public releases reference only deliberately published material. Full backups need database and private files, off-server retention, and a demonstrated restoration process.

## 13. Delivery order and review gates

| Milestone | Scope | Review gate |
| --- | --- | --- |
| 0: Preparation | Capture current reader behaviour; inspect chapter APIs/schema; update stale deployment context; prototype manuscript layout; agree save/release boundaries. | Review the writing screen and stored state before altering live behaviour. |
| 1: Manuscript workspace | Preserve editor and Assist; chapter navigator; optional summaries; write/preview/split; stay-in-place save; public snapshot boundary; autosave, recovery, conflict handling, ordering. | Tapuwa writes a real chapter on PC/tablet, uses summaries and Assist, and confirms public content stays stable. |
| 2: Drafts and compile | Named manuscript snapshots, comparison/restoration, Markdown/PDF export, explicit publication update flow. | Save Draft 1, revise, compare/export it, restore safely, and preview a release. |
| 3: Shared worlds and references | Project shelf, shared worlds, entries, aliases, semantic status, search, selection-to-entry flow, inline highlighting and reference reading. | Capture a new name while writing; use an alias; resolve ambiguity; reuse one world across manuscripts. |
| 4: Thinking tools | Alignment charts, comparisons, relationship views, investigations, and dependency diagrams; typed saved views attached where useful. | Develop an actual question from at least two concepts with reusable tools, without assembling panels. |
| 5: History and narrative | Events, timelines, calendars, relationship phases, arcs, threads, knowledge/reveal boundaries. | Work through a historical sequence and manuscript reveal order, preserving uncertainty. |
| 6: Geography | Map import and blank schematic creation, linked scales, placement, calibrated measurements, routes, constraints, revisions, Wonderdraft exchange. | Build a small city/region, place writing-created locations, inspect a journey, and revise the base map safely. |
| 7: Deeper workflows and integration | Extend magic/ecology/belief/language/resource workflows from actual needs; full assistant retrieval; reviewed proposal capture; DOCX/EPUB; reviewed imports. | Analyse a fixed draft/world revision through both assistants and complete a real authoring/export/publishing loop. |

These are increments, not a reason to postpone investigation. Trial assistant connectivity and Campfire/Wonderdraft export fidelity early, alongside the first milestone. Design geography and shared-world boundaries early enough to avoid incompatible foundations. Full assistant access can advance once retrieval is stable; geography can advance if the current writing need demands it. Do not start all tool implementations in parallel.

## 14. How each milestone is attacked

1. Define one real author workflow and the failure cases that matter.
2. Review data shape and UI together; resolve decisions that would be costly to reverse.
3. Build the smallest complete vertical slice, including persistence and authorisation.
4. Run syntax/static/build checks and actual request/scratch verification appropriate to the change. No new automated tests under current repository instructions.
5. Prepare one focused manual charter for that workflow. Tapuwa reviews on PC and tablet; record findings and probes.
6. Fix findings, verify stored state, and complete code review before release.
7. Commit focused work on dev. Promote a reviewed increment to master and deploy only when authorised, with backup and migration compatibility accounted for.

Do not use production as a scratch environment or silently replace the user's local database. Prepare a suitable private development database without copying unnecessary production account material. Preserve existing source snapshots and the blog.

The first implementation slice should be one existing book in the manuscript workspace, with chapter switching, a private summary, Markdown/Assist, and stay-in-place save. Public snapshot work is included before enabling autosave on published chapters. Then complete recovery/conflicts and ordering before calling milestone 1 finished.

## 15. Explicit open decisions and limits

- What tablet and keyboard configurations need to pass the editor trial?
- Does starting a map from scratch require terrain painting inside DreamersPalette, or is a schematic planning map plus Wonderdraft sufficient initially? Current recommendation: schematic first, revisit drawing requirements explicitly.
- Which existing Campfire export and original Wonderdraft files can actually be recovered? Their fidelity is unverified.
- Which exact Codex and Claude clients/accounts must connect? Protocol support is documented; end-to-end access has not been proven.
- Should a partial publication update create a complete new release manifest? Recommendation: yes, even when only one chapter's content changes, so each release has a coherent public state.
- Named drafts and world snapshots are retained; recovery history gets a separate retention policy after observing usage. No automatic deletion policy is assumed.
- Travel checks depend on declared assumptions. They do not simulate ecology, climate, warfare, or planetary motion automatically.
- In-app discussion records are deliberate captures, not automatic access to private assistant history.

No dates or effort estimates are committed before the editor, save boundary, and import/connection trials expose their actual cost.
