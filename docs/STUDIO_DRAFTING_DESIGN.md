# Writing studio: drafting and ownership design session

Date: 2026/10/24. Status: draft lifecycle confirmed by Tapuwa; first private implementation available locally; editorial review and later lifecycle tools remain proposed. See [implementation status](STUDIO_IMPLEMENTATION_STATUS.md).

This refines the implementation plan for future multiple users. The HTML design preview is illustrative and stores changes in browser memory only. Application/database implementation has not begun.

## Confirmed draft lifecycle

Tapuwa confirmed: one editable manuscript at a time. Work on Draft 1; when done, finish it. The complete draft is saved and locked. Draft 2 begins as a copy of that manuscript and is the only version open for adjustments. Repeat for later drafts. Autosave updates the active draft; it never finishes it.

Do not expose multiple editable branches or arbitrary snapshots under the name Draft. Use Finish draft -> Start next draft as the normal transition. The finish action may let the author name the completed version and add a note before it is locked.

Store `working_draft_number`, an optional working label, and `based_on_draft_id` on the manuscript. Keep active text in studio_chapters; store complete immutable copies in studio_drafts/studio_draft_chapters when finishing. In one transaction, capture Draft N, update the active number to N+1 and its basis to the finished draft, and retain the active chapter content as the identical starting copy. There is no need to create another second editable set or change stable working chapter IDs just to express the copy. The locked records are physically independent copies. This is a storage optimisation, not a different user-facing lifecycle.

Every save also sends its expected active draft number. After another tab finishes Draft N, a stale tab cannot unknowingly submit changes into Draft N+1 even if its chapter revision has not otherwise changed. Return an explicit draft-transition conflict and retain that tab's local work. Store a unique finish-request identity on the completed draft, scoped to its manuscript, so retrying a lost response returns the same finished/next-draft pair.

## Screens

Write: chapter navigator, current Markdown, compact save feedback, Assist, Write/Preview/Split, and Focus. Optional summary opens below or beside the prose; it is private. The active version is labelled Draft N · In progress. Completed versions are explicitly Locked and read-only.

Drafts: the active draft appears first with Continue writing. Finished drafts show name, finishing time, creator, chapter count, word count, optional note, and Compare/Read/Export actions. Avoid grids of giant cards. Finish draft opens a small name-and-note form describing that the whole manuscript and summaries will be locked and the next draft started from the same content. Completed draft content, name, and finishing note stay preserved. Later annotations may be separate records; they do not rewrite the finished version.

Read saved draft: clear draft name and read-only state, snapshot chapter navigation, no autosave. Provide Compare with working copy and Restore as working copy. Avoid an inactive editor that looks editable.

Compare: choose two milestones or milestone/current saved work. Show chapter additions/removals/reordering and prose/summary changes. Compare a coherent server revision, not a moving mixture while someone types. The preview demonstrates only a two-column Prologue comparison; full structured differences are not implemented.

Restore: describe what will be replaced and confirm that the current server-saved manuscript will first be retained as a recovery checkpoint. Restore content/order/summaries without changing published readers. If unsynchronised local work exists, resolve/checkpoint it before restoration.

Publish: separate destination with a public preview. No private summary, analysis notes, draft comments, or world spoilers enter public output by default. Saving a draft never publishes.

## Storage model

| Table | Principal fields | Mutability |
| --- | --- | --- |
| studio_manuscripts | id, book_id, author_id, working_draft_number, working label, based_on_draft_id, working title/metadata, structure_revision, current_release_id, timestamps/archive | Active draft header |
| studio_chapters | id, manuscript_id, position, title, content_md, summary_md, revision, updated_by, timestamps/archive | Working chapters |
| studio_drafts | id, manuscript_id, sequence, label, note, kind, created_by, source_structure_revision, metadata snapshot/schema version, created_at | Finished draft and captured metadata immutable |
| studio_draft_chapters | id, draft_id, source_chapter_id, position, title, content_md, summary_md, source_revision | Immutable copied content |
| studio_releases / studio_release_chapters | manuscript, release identity, publishing actor, public metadata/prose/order, stable routes | Immutable public content with an explicit current pointer |

Draft sequence is unique within a manuscript; labels need not be globally unique. Chapter source identity connects comparisons, but rendering a snapshot must not join live chapter text or depend on the working chapter still being active. Drafts capture only the active working chapter set; older snapshots retain formerly active chapters. Restore must define how currently active chapters absent from the snapshot are archived and snapshot chapters are restored/recreated, preserving associations where possible.

Use ordinary full text snapshots first. For illustration, a 500 KB manuscript captured 100 times is roughly 50 MB of raw text before summaries, indexes, and storage overhead. Actual usage determines whether immutable chapter-version reuse is worth implementing. Do not start with custom text-delta storage or Git branching inside MySQL.

Avoid storing every autosave as a permanent full manuscript draft. Draft kind can distinguish named milestone, pre-restore recovery, and import baseline using fixed technical values validated in PHP, not database enums. Recovery retention is separate; named drafts are not automatically pruned. Decide and display a recovery policy before implementing cleanup.

Named drafts get whole-manuscript metadata and, when worldbuilding exists, an explicit preserved world snapshot reference. They do not own independent duplicate worlds. Public releases and author drafts remain separate even if created from the same source.

## Ownership and attribution for multiple users

Keep `author_id` as the ownership field to match existing Book conventions; clarify that it represents the account controlling the manuscript, not a complete literary credit list. A future pen name or credited co-author is a different concept. Book and manuscript owner must agree.

Chapters inherit ownership through the manuscript. Drafts inherit through the manuscript, draft chapters through the draft, and releases through their parent. Do not add independent mutable owner fields to every copied row. `created_by` on a draft/release and `updated_by` on chapter record the actor, not ownership. They remain accurate if explicit collaboration or ownership transfer is added later.

Example: user 7 owns manuscript 42 and user 8 owns manuscript 43. Both may have Draft 1 and a chapter called Prologue. URLs, filenames, local recovery keys, queries, cached results, exports, and assistant requests must not mix them. The client cannot supply author_id to make itself an owner. A chapter ID must be fetched within the authorised manuscript, not independently by global ID and then trusted.

Application capability and record access are separate checks: being allowed to use manuscript editing does not permit editing every manuscript. Use Spatie permissions plus policies and scoped queries at all boundaries, including Livewire actions and download/assistant endpoints. Public access to a released chapter never grants access to its working manuscript.

Recommend no automatic private studio access through the existing broad `content.manage-all` behaviour. If support/administrative access is required, make that an explicit separately reviewed policy and record its use. This is a proposal; current React policy behaviour is unchanged.

Design for multiple independent authors first. Tapuwa subsequently requested authorised editorial review: sharing is now an explicit planned requirement. Add scoped editor grants and separate review/comment/suggestion records after the core draft lifecycle; no broad account role should confer private manuscript access. A reviewer can see an authorised snapshot without automatically seeing private summaries, the whole world, or later drafts. Completed text stays locked; accepted suggestions modify the active draft with conflict checks and attribution. Teams, simultaneous coauthoring, and direct editorial overwrite remain outside the first increment.

Ownership transfer is not initial scope. If introduced, transfer Book/manuscript consistency explicitly and re-evaluate world links, member grants, exports, and assistant authorisations; changing one author_id silently is insufficient.

## Snapshot transaction

1. Checkpoint the active local editor and await its acknowledgement. Resolve connection/conflict states first.
2. Send authorised manuscript identity, expected structure revision, relevant saved revisions, label/note, and a unique request identity.
3. Authorise snapshot creation, lock the manuscript, and verify the expected state. All chapter saves/structural operations observe the same lock discipline.
4. Capture the server-saved header and ordered active chapters in one transaction; insert draft and draft chapter rows, including source revisions.
5. Return the draft identity and saved scope. Retrying the same request cannot create a second milestone.
6. Advance the active draft number and basis in this same transaction, starting the next draft with the same chapter content and summaries. Continue editing the next active draft; do not reset its cursor or undo state. Repeated finish requests return the original transition instead of skipping another draft number.

Work still unsaved in another browser is not included. Say Server-saved manuscript captured rather than implying every open device was synchronised.

## Restore and conflicts

Restoration uses a new transaction and current revision checks. First capture the existing working manuscript as a recovery checkpoint; then apply the chosen snapshot. Preserve source chapter IDs where they still belong to the manuscript, restore/archive the correct chapter set, and allocate fresh working identities only when required. Never resurrect a foreign record by trusting a stored ID without checking its parent.

Increment structure and chapter revisions; never rewind revision counters to old values. A tablet still editing pre-restore content must get a conflict, not overwrite the restored manuscript. Published release pointers stay unchanged. Record restored_from_draft_id and restoring actor as part of the restore/recovery metadata.

## Design preview and limits

Open `docs/design/writing-studio-drafts.html` through the local preview server. It demonstrates chapter selection, optional summary, focus mode, named snapshot creation, simple comparison, and a database explanation. All names/IDs/times/excerpts are illustrative. It is not Livewire, not connected to the application database, not a persistence/recovery implementation, and not a fully functional Markdown renderer. Refresh resets its changes.

Pending decisions: placement of Finish draft in the writing interface; whether future sharing is desired beyond independent users; explicit administrative private access. Restore means starting/replacing active work from a locked draft, never unlocking and editing the historical draft. Recovery checkpoints remain a separate kind from completed author drafts. These details should be resolved before migrations are written.

## Review feedback — 2026/10/25

Remove decorative instructional copy and empty-chapter fallback subtitles. Only
show an actual author-written summary beneath a chapter title. Keep save/recovery
and finish-confirmation wording that explains a real consequence.

Tapuwa asked about undoing an accidental finish. Current implementation has no
unlock/undo action; the next active draft retains identical writing. Recommendation
for discussion: Undo finish only for the latest completion while the next draft
has no edits or structural changes, with server-side concurrency checks. Do not
implement arbitrary historical unlocking or silently discard later work. Exact
history/numbering semantics still need agreement before implementation.


### Accepted and implemented: Undo finish — 2026/10/25

The author can undo the latest finish while the next draft has no accepted saves
or structural changes. The action returns to the previous active number and leaves
working prose/summary/order intact. Cancelled snapshots retain `undone_at` and
`undone_by` internally and disappear from ordinary finished-draft lists/downloads.
Finishing again creates a fresh snapshot, even with the same draft number.

The manuscript revision and based-on identity must still match the finish. Undo
increments chapter revisions and clears save UUID acknowledgements so stale tabs
cannot become valid when draft numbers are reused. Server policy requires both
finish and update capabilities on the author’s manuscript. Repeating an accepted
undo does not rewind a later state. A cancelled finish request cannot finish again.

Verified through the real browser on a disposable local manuscript and transactional
scratch execution: undo/retry, refinish, old finish replay rejection, edits blocking
undo, chapter creation blocking undo, and retained earlier draft basis. No automated
tests were added. The temporary browser fixture was removed after verification.
