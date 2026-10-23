# Author publishes, archives, and restores literary content

Date: 2026/10/17. Timebox: 40 minutes.

## Risk level

High: publication and restoration must preserve writing and prevent public access to drafts or content inside unpublished parents.

## Goal and stop condition

Verify the MySQL schema and model lifecycle using local records. Finish when identity, publication dates, archival/restoration, public visibility, and database uniqueness are recorded. Stop on data loss, timestamp drift, or unexpected public visibility.

This is model-level verification. The React editors still call Supabase; do not use their buttons for these checks.

## Accounts and roles

Use a local developer console against an isolated MySQL database. No HTTP authentication or permissions are implemented in this step. The source profile's `author` label remains historical; it does not grant or deny Laravel access.

## Test data

The private source snapshot contains one user/profile, 16 collections, 77 poems, one book, one chapter, and 44 generated assets. There are 76 published poems and one draft. No original rows are archived. Use copies in a disposable database; never modify the Supabase source or the snapshot files.

For lifecycle checks, associate a draft poem with a collection, and a chapter with its book. Retain their UUIDs and exact creation timestamps for comparison.

## Fixture reset

The agent's scratch exercise ran inside one database transaction and rolled back every record write. For a console session, start `DB::beginTransaction()` and finish `DB::rollBack()` in that same session. Do not run a database refresh. Keep source files, UUIDs, publication dates, and production data unchanged.

## Starting state

Run `php artisan migrate` only against the intended empty local MySQL database, after verifying connection settings. The agent verified migrations on a separate temporary MySQL server; the ordinary `.env` target is not provisioned by this step. Use the copied records once the approved importer exists, or create disposable records from the console with explicit author/parent associations and slugs.

## Data shape review

Read the seven migrations in execution order: users, profiles, collections, poems, books, chapters, generated assets. Confirm:

- identity/ownership, content, presentation, publishing, timestamps are grouped in that order;
- users and profiles share the source UUID; content `author_id` identifies the profile;
- Markdown uses long text, dates retain six fractional digits in UTC, and imported slugs are unchanged;
- the three slug indexes and `(book_id, chapter_number)` are unique;
- relationship indexes support actual listing queries without foreign keys or cascades;
- all source fields remain, including collection text colors, chapter preview flags, publication dates, and generated prompts;
- profiles and generated assets retain their source created-only timestamp shape.

## Intended workflow

1. Load a copied user with its profile. Observe the same UUID on both and the expected profile relationships: 16 collections, 77 poems, one book, one chapter, 44 assets.
2. Publish the draft poem using `$poem->publish()`, refresh it, and note `published_at`. Expect publication to be true with a populated first-publication date.
3. Unpublish and republish it, then refresh. Expect the exact original `published_at` to remain.
4. Call `$poem->archive()` and refresh. Expect a deletion date, false publication, and exclusion from ordinary queries. Calling `publish()` on it must return false.
5. Call `$poem->restoreAsDraft()` and refresh. Expect no deletion date and false publication. Repeat restoration on the active record; expect false without changing state.
6. Publish the poem and query its collection's poems with `publiclyVisible()`. Unpublish the collection; expect no publicly visible children. Archive the collection; expect its children to remain stored and publicly invisible.
7. Restore the collection. Expect a draft parent, with children still stored and their own publication states preserved.
8. Repeat parent visibility and archive/restore on the copied book and chapter. Expect no public chapters while the parent is unpublished or archived, and restored chapters to be drafts.
9. Save an unchanged poem with a six-digit fractional creation timestamp. Expect its creation date to survive the Eloquent round trip exactly.

## Risk map for probing

- High: calling `published()` alone for a public child endpoint would omit parent checks; public APIs must use `publiclyVisible()`.
- High: bulk query updates/deletes bypass Eloquent lifecycle events; normal publishing/archiving must use model methods. A later importer may intentionally write source rows directly.
- High: model behavior does not replace authorization; no Laravel content endpoint should be exposed until policies and permission checks exist.
- Medium: repeated archive/restore requests, restoring a parent with independently published children, and publication dates across repeated publish cycles.
- Medium: duplicate slugs, duplicate chapter numbers even on archived records, and interpreting PostgreSQL timezone values without converting them to UTC.
- Medium: relationship safety has no database foreign keys; the later Form Requests, policies, and importer must validate parent existence and ownership.

## Permission checks

No new routes expose these models. Authentication replacement, registration disabling, administrator provisioning, Spatie permissions, and model policies belong to the next approved authentication/API steps. Do not treat successful console access as proof of HTTP authorization.

## State verification

Compare record counts and UUID links with the source snapshot. Read MySQL column order and `SHOW INDEX` output. Confirm there are zero foreign-key constraints. Observe duplicate inserts being rejected without altering the source records. Roll back console changes and verify the original state returns.

## Findings

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |
| M1 | Runtime | Seven migrations and models | Source records fit MySQL with readable column order | All copied records inserted into disposable MySQL; model relationships match source counts | High | Ready for retest |
| M2 | Runtime | Publishing and restoration | First-publication date retained; restored records are drafts | Lifecycle exercise passed for poems, collections, books, and chapters | High | Ready for retest |
| M3 | Runtime | Public child scopes | Draft/deleted parents hide children | Poem and chapter visibility dropped to zero with unpublished/archived parents | High | Ready for retest |
| M4 | Runtime | Timestamp precision and uniqueness | Preserve microseconds; reject duplicates | Eloquent creation timestamp preserved; duplicate poem slug/chapter number rejected with SQLSTATE 23000 | Medium | Ready for retest |
| M5 | Boundary | Local database and HTTP delivery | Working target connection and authorized APIs | Default local database credentials and Laravel authentication/API work remain pending | High | Documented |

## Session evidence

No automated tests were added. The agent applied, rolled back, and reapplied all seven migrations successfully, then ran model methods against disposable MySQL using the copied source records, then rolled back record writes. No Supabase writes occurred. Reader/editor HTTP workflows remain unverified against Laravel because their backend has not switched yet.

## Application switch follow-through — 2026/10/20

The studio now calls Laravel, so the earlier model-only/Supabase boundary is
historical. Use the dedicated local Laravel URL and an account with studio
capabilities. Source snapshot content must remain unchanged; create a collection
and poem named for your local session, and a separate local novel/chapter.

Walk the same publication workflow through the browser: create an empty draft,
edit and save, publish child before parent, confirm the signed-out reader cannot
open it, publish the parent and read it, archive the parent and confirm the child
is hidden, restore the parent and confirm it returns as a draft. Repeat with the
novel/chapter. Duplicate chapter numbers, including an archived chapter's number,
must be rejected without replacing the original writing. Check collection theme
preview while the collection is still a draft. Use Trash to archive only records
created for this session; no database reset or original-record edits are needed.

Inspect `ContentController`, the content Form Requests, policies and
`ReaderController` before the session. The server supplies author identity and
slugs; input cannot transfer ownership. Use a separate author to check that direct
URLs and requests cannot read or modify the first author's drafts or attach
children to their parents. Readers cannot enter the studio. Admins can manage
other authors' content; a child still must belong to the parent's author.

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |
| L1 | Runtime | Stateful API configuration | Login session works on the current local host | Fixed an omitted current-host configuration argument; real login/session/logout checks pass | High | Fixed |
| L2 | Runtime | Draft validation | Empty chapter drafts can be saved | Fixed empty-string/null input normalization; HTTP creation now passes | Medium | Fixed |
| L3 | Runtime | Studio lifecycle and reader queries | Enforce ownership, publication and parent visibility | Actual local requests pass for four content types, publication, archive/restore, foreign/missing parents and chapter uniqueness | High | Ready for retest |
| L4 | Runtime | Original data | Preserve imported writing and media | All 140 non-user records and 45 media checksums match; user timestamp changed through authorized administrator sync | High | Documented |
| L5 | Browser | Local public reader | Render imported content from Laravel | Homepage, poem, novel and chapter render; full editor/mobile/share-card pass remains | Medium | Ready for retest |

Agent scratch records/accounts/uploads were removed. No automated tests were
added. The private source snapshot, source Supabase data and blog database were
not changed. Remaining system/environment checks are in
`docs/LARAVEL_SYSTEM_REVIEW.md`.
