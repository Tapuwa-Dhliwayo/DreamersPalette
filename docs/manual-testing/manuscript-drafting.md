# Author writes a manuscript and finishes a draft

Date: 2026/10/24. Timebox: 30–45 minutes. Risk: **High** — private prose, recoverable unsaved work, concurrent saves and immutable finished versions.

## Goal and stop condition

Write and arrange chapters, finish one complete draft, continue the next, and establish that the finished text is retained privately. Stop after the normal journey, chosen risk probes, read-only state verification and recorded findings, or on a blocking defect.

This covers one workflow. Publication, worldbuilding and editorial collaboration are outside this implementation.

## Accounts and required data

Use an authenticated, verified author with `dashboard.access` and the five `manuscripts.*` capabilities. For access checks, use a second author in a separate browser session; a reader and an anonymous session are useful additional boundaries. Administrator status alone must not expose another author’s prose.

Local starting URL: `http://127.0.0.1:8018/studio`. The synthetic sample “The city beyond the rain” already has a finished Draft 1 and active Draft 2. Use a **new** manuscript for a clean normal pass. On production, use an explicitly disposable review manuscript, never another author’s real writing.

## Fixture reset

There is no reset/archive/delete control in this increment. Do not reset the shared database or delete locked drafts. Start each pass with New manuscript and a distinct title, such as “Studio review 2”. This preserves all prior writing and public data. Any later cleanup requires a separately scoped decision about those named records.

## Phase 0 — data shape review

Read the two new migrations and the four studio models before clicking. A working chapter stores one title/prose/summary revision and a stable parent/identity. Finished header/chapters capture title, order, prose, summaries, source revisions, creator and time; rendering the old draft must not depend on current chapter content.

Review questions: Is the standalone manuscript suitable before public onboarding? Is the chapter-level conflict boundary acceptable when summaries and prose change together? Does the saved draft contain enough context for future editorial attribution? World material is deliberately not frozen by this first draft snapshot.

## Phase 1 — intended workflow, notes mode

Record findings while completing the whole pass; apply fixes afterwards.

1. Open the library and choose **New manuscript**. Submit a title and description. Expect a private workspace with Draft 1 and one empty chapter; no public Book should appear.
2. Rename the chapter “Prologue”. Write two paragraphs containing `**bold**`, `*italic*`, a quotation, a Unicode name and a Markdown link. Expect the source to remain Markdown and the save state to progress to **Saved to server**.
3. Expand **Chapter summary** and write a private outline. Save, reload, and verify title, prose and summary return exactly. The summary appears in chapter navigation but remains separate from prose.
4. Use **Preview** and **Split**, then focus mode and Escape. Expect safe rendered Markdown and a usable return to writing without lost selection/text or duplicate controls.
5. Select a word and use **Assist → Thesaurus**. Apply a suggestion. Expect only that selected range to change, normal undo to work, and the change to join the normal save queue. Keep or undo it deliberately, then save.
6. Add a second chapter, give it a title and summary, and write a paragraph. Switch between chapters while edits are pending. Expect successful checkpointing before the target opens, correct chapter text, and an updated chapter query in the address.
7. Move the second chapter up. Expect chapter numbering/order to change together; save/reload and confirm that order persists.
8. Choose **Finish draft**. Read the confirmation: it captures server-saved chapters and summaries. Give Draft 1 a meaningful name and note, then finish once. Expect one locked Draft 1 and active Draft 2 starting with identical content.
9. Before editing Draft 2, use **Undo finish** on Draft 1. Expect active Draft 1 again, identical text, and no ordinary finished draft. Finish again; expect a new locked snapshot of Draft 1 and active Draft 2. Then continue writing and change prose and summary in Draft 2. Open **Drafts → Read & compare**. Expect Draft 1’s text/summary to remain unchanged and the chapter to be marked Changed. Other untouched chapters should read Unchanged.
10. Download the finished draft’s **Markdown**. Expect its captured manuscript/chapter titles and prose in captured order; no private summaries or current Draft 2 changes. Return to the library: chapter and locked-draft counts should agree.

## Phase 2 — ranked risk map, fix loop

Choose what to attack while looking at the screen. Record each probe and its result; this is not a second scripted journey.

| Priority | Boundary | What could break |
| --- | --- | --- |
| 1 | Two sessions editing one chapter | Stale text overwrites newer writing; local candidate disappears; wrong draft generation accepted after another tab finishes. |
| 1 | Private access | Second author, reader, anonymous request or foreign manuscript/chapter/draft ID exposes text or permits a mutation/download. Initial routes and Livewire actions must both refuse. |
| 1 | Connection/session interruption | Uncertain save acknowledged as successful, retry creates extra revisions, local recovery silently replaces newer server prose, or moving away loses the candidate. |
| 2 | Finish transition | Double submission creates two drafts, a chapter changes after confirmation opens, partial snapshot is retained, or subsequent edits alter locked rows. |
| 2 | Pending typing and navigation | Typing during an in-flight save gets cleared; switching creates the wrong chapter content; stale preview or suggestion applies to the new chapter. |
| 2 | Input and recovery | Empty required title, intentionally empty prose, oversized/multibyte text, unavailable browser storage, HTML/unsafe links, or expired session gives misleading feedback. |
| 3 | Tablet and focus | Virtual keyboard obscures tools, chapter drawer cannot close, focus does not escape, text selection or Markdown undo fails. Test on the actual tablet, not only resized desktop. |

Permissions: the owner may view/create/update/finish/export only with the corresponding capability. A second author/admin may not view that manuscript or export its draft. There is no editor grant or direct historical edit path yet. Server rejection is the evidence; a missing button alone is insufficient.

## Phase 3 — read-only state verification

Use the actual review manuscript ID. Never run write queries as verification.

```sql
SELECT id, author_id, title, working_draft_number, based_on_draft_id, revision
FROM studio_manuscripts WHERE id = <manuscript_id>;

SELECT id, manuscript_id, position, title, revision, updated_by, last_save_uuid, deleted_at
FROM studio_chapters WHERE manuscript_id = <manuscript_id>
ORDER BY position, id;

SELECT id, manuscript_id, number, name, manuscript_title, source_revision, created_by, request_uuid, created_at
FROM studio_drafts WHERE manuscript_id = <manuscript_id> AND undone_at IS NULL ORDER BY number;

SELECT c.draft_id, c.source_chapter_id, c.position, c.title, c.source_revision
FROM studio_draft_chapters c JOIN studio_drafts d ON d.id = c.draft_id
WHERE d.manuscript_id = <manuscript_id> ORDER BY d.number, c.position, c.id;
```

Expected after the normal pass: active number 2, exactly one non-cancelled finished Draft 1 (plus the internally cancelled first completion), one snapshot row per chapter at finish, retained source identities/order, current edits only in working rows. Compare captured content directly when investigating a mismatch. An accepted save increments the chapter revision once; an identical UUID retry does not increment it again. Word count is whitespace-delimited, not a publication-grade linguistic count.

No emails, publication, uploads, world changes or editor invitations should occur. Assist calls its existing providers only when requested; ordinary autosave remains local application persistence. Markdown download excludes summary text.

## Findings and evidence

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| --- | --- | --- | --- | --- | --- | --- |
| — | — | — | — | Awaiting Tapuwa’s review | — | — |

Capture the manuscript/chapter/draft IDs, exact save message, screenshot and relevant request outcome for any failure. Do not capture credentials or unrelated private prose.

Implementation checks already performed are in `docs/STUDIO_IMPLEMENTATION_STATUS.md`; they do not replace this author review. Agent records findings, decisions and probe coverage during the session.

## Session summary

- Tester / environment / commit / device:
- Intended workflow completed:
- Probes attempted and results:
- Read-only state verification:
- Findings and blocking defects:
- Unverified areas / next focused session:

Undo-specific risk probes: saved edits, reorder or chapter creation after finishing must prevent undo; repeated undo must not rewind again; a stale finish request for a cancelled completion must fail; an old browser chapter revision must remain invalid after undo/refinish. Inspect `undone_at`/`undone_by` separately when verifying retained cancellation history.

### Comparison visibility finding — 2026/10/25

Read & compare previously inserted content beneath the list without moving focus
or scroll, and repeated clicks could appear to do nothing. Fixed by opening an
explicit Flux dialog with the selected/current draft identities. Browser verified:
button opens the dialog, both texts and Changed status are visible, Escape closes
it and returns focus to the invoking button. The normal review step must verify
this dialog rather than search beneath the draft list. Temporary verification data
was removed; no user manuscript was changed.
