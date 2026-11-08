# Publishing authoring refresh

2026/11/06 · Local development · 40 minutes · Author and reader handoff.

## Mission and stopping point

Verify that an author can find work, edit a poem or a directly managed chapter, configure its collection/book, and review a finished manuscript release while the atmospheric background remains visible. Stop after checking saved state and reader output, or record a blocking defect.

## Accounts, data and starting state

Use the existing local author with publishing and manuscript permissions. Start at `/dashboard/books`. Use an existing collection and one disposable poem; use a directly managed novel for chapter editing and a manuscript-managed novel for the separate release path. Prepare a finished manuscript draft only if reviewing release submission. Include long titles, multiple stanzas, blank content, Markdown headings and lists. Record selected IDs before editing. Use a second author without ownership for permission probes.

No automated fixture reset is provided: this session uses real copied content. Preserve the initial text/settings separately before intentional edits; restore through the same forms. Archive only the disposable poem through the normal Trash flow. Never reset the database. Publication changes affect local readers immediately.

## Data shape review

Check which novels are manuscript-managed, which chapters are published, the selected collection's theme, and the manuscript's finished drafts. A browser recovery copy is not a server save. A finished draft is the source of a release; the working manuscript is not.

## Phase 1 — intended workflow (15 minutes)

1. Open Poems, Collections, Novels and Chapters. Expect compact rows, publication badges, dates in yyyy/mm/dd, visible selected states, working filters and pagination. Search for an absent title and inspect the empty state.
2. Create a disposable poem. Enter title, collection and two stanzas. Collapse Poem details. Confirm writing remains available and Save/status stay visible while scrolling.
3. Switch Write → Preview → Split → Write. Expect unchanged text, preserved stanza breaks, correct Markdown, and the same editing buffer. Select text and open/close Assist; do not send a paid generation solely for visual verification.
4. Save and reopen the poem. Confirm persisted text. Edit, wait for the recovery-copy status, refresh, and inspect recovery feedback. Attempt to navigate away with unsaved writing; cancel the navigation first.
5. Open a directly managed chapter. Check title, novel, chapter number and reader-preview option in details, then inspect all three writing modes.
6. Open a novel's metadata/cover editor. Expect grouped novel details and cover controls with an adjacent preview. Open a collection; compare theme changes with its existing live preview. Save only deliberate local changes.
7. For a manuscript-managed novel, use Open manuscript from Novels and Chapters. Expect the right manuscript, or library restoration for an archived manuscript. The publishing copy must remain protected from direct prose edits.
8. Open the manuscript Publishing screen. With no finished drafts expect guidance back to writing. With a finished draft select it, expand a chapter, review the public synopsis, reader destination and retained releases. Submit only if deliberately verifying a local release; inspect the public book afterward.

## Phase 2 — risk probes (15 minutes)

Prioritize recovery/save failure, unsaved navigation, long text and mobile controls. Use a 390px viewport: no page-width overflow; the sidebar opens/closes and returns focus; toolbar wraps; split panes stack; metadata remains reachable; background remains visible. Keyboard through modes and details. Check visible focus and disabled actions. Interrupt a save or expire the session and confirm writing remains available and the failure is visible. Check publication confirmations still explain immediate reader visibility. Inspect a collection with no image and one with a busy background.

## Permissions

Authors need the existing resource permissions and ownership. Attempt another author's direct editor/manuscript URL using a second account; the server must refuse access. Cover editing must not enable manuscript-owned prose fields. No permission rules changed in this refresh.

## Phase 3 — state verification (10 minutes)

Reload each intentionally saved record and compare with the original input. Inspect public poetry for exact line/stanza preservation and the collection theme. Inspect local released chapters only if a release was intentionally submitted. Restore changed settings/text, archive disposable records and confirm Trash recovery. Confirm no live-site content changed.

## Findings

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |

## Verification and attention

Build and Blade compilation are checked by the agent. Focused lint reports the same pre-existing ref warnings in EditorPanel and effect warning in CollectionEditorPage as HEAD. No automated tests were added, per repository instructions. Browser verification performed without saving, publishing, uploading or generating content; full persistence/recovery/permission probes remain for this charter.

## Agent browser pass — 2026/11/06

Verified authenticated poem creation screen, Write/Preview/Split visibility, novel details/cover layout, collection listing/editor and existing preview, direct manuscript link on Novels, and the manuscript release screen with no finished drafts (publication disabled and existing reader link present). At a 390px viewport, document and body widths both measured 390px; no horizontal overflow. Existing author content was not edited or submitted. Persistence, recovery, paid assistance, uploads and ownership probes remain for manual review. Local Artisan server is running at http://127.0.0.1:8000.
