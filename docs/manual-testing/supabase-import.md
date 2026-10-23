# Operator imports Supabase content into local Laravel

Date: 2026/10/17. Timebox: 40 minutes.

## Risk level

High: wrong database selection, credential replacement, missing writing, and incorrectly exposed files can undermine the migration.

## Goal and stop condition

Import a complete private snapshot twice, preserving content and credentials while mapping media to Laravel. Finish with matching record/file verification and recorded rejection cases. Stop on wrong-target selection, data loss, a changed existing password, or checksum failure.

## Accounts and roles

Use the local database user configured in `.env`, scoped to `dreamerspalette_laravel`. Import creates no administrator permissions. The copied existing auth account is not yet a working HTTP login because Sanctum/API work is pending.

## Test data

The captured private snapshot has 141 records: one user, one profile, 16 collections, 77 poems (76 published and one draft), one book, one chapter, and 44 generated assets. It includes 45 storage objects totaling 9,760,772 bytes. Every referenced source object exists; one additional unreferenced object is retained.

## Fixture reset

Do not refresh any database. The agent's unsafe-input/password-change/conflict probes ran on disposable MySQL and rolled back their record mutations. The actual local import is retained for later development. Rerunning import synchronizes source-owned UUIDs, so local edits to those rows are replaced; run rerun probes on a disposable target once editorial development begins. Extra local UUIDs and existing passwords are retained.

## Starting state

Work from the repository directory on `feature/laravel-migration`. Confirm `.env` names `dreamerspalette_laravel`. The database named `dreamerspalette` belongs to the blog; leave it untouched. Keep the source directory private. Confirm migrations are applied and `public/storage` links only to `storage/app/public`.

## Data shape review

Read `docs/LARAVEL_MIGRATION.md`, `config/supabase-migration.php`, and the private source directory names without sharing their contents. Confirm snapshot capture time, source table names/columns, owner UUIDs, manifest counts, bucket visibility, and original media paths. Auth records include password hashes; no source JSON belongs in Git or ordinary logs.

## Intended workflow

1. Run `php artisan dreamers:import-supabase --dry-run`. Expect the dedicated target name, capture timestamp, snapshot fingerprint, counts per table, and 45 files. Confirm the new/existing UUID counts reflect the target. No records or asset files should change.
2. Run `php artisan dreamers:import-supabase`. Expect a verified import, with no credentials or content text in output. The first import adds the source UUIDs; existing UUIDs are updated on subsequent runs.
3. Run `php artisan dreamers:import-supabase --verify`. Expect all 141 source rows to match, zero extra rows on the initial target, and 45/45 file checksums.
4. Rerun import, then verification. Expect zero new UUIDs and the same total counts and file checksums.
5. Read an imported poem's Markdown and publication timestamp from MySQL and compare them with its source copy. Expect exact writing and six-digit UTC timestamp precision. Confirm the draft remains a draft.
6. Read an imported collection or generated asset URL. Expect `/storage/imports/supabase/backgrounds/...`. Request the corresponding URL through Laravel and observe HTTP 200, an image MIME type, and matching bytes.
7. Run `git status --short --untracked-files=all`. Expect no source snapshots, database passwords, copied media, SQL setup secrets, or checksums from private storage to appear as tracked candidates.

## Risk map for probing

- Critical: target database selection. An unrelated schema must be rejected; never bypass a table conflict by refreshing the database.
- High: source-owned local edits are intentionally overwritten by import. Do not rerun against later editorial changes without reviewing that synchronization.
- High: password changes after initial import must survive reruns. Use disposable records and check Laravel authentication against both the original and subsequently changed password, without printing either.
- High: corrupt file checksum, missing file, unknown owner, mismatched parent ownership, duplicate source UUID, unsafe file path, and private-bucket URLs must be rejected before database writes.
- High: the same slug/email/chapter number owned by a different target UUID must fail; the other record must survive intact.
- Medium: interrupted copying or SQL uniqueness failure. Completed verified media may remain unreferenced; a rerun reuses it. Partial temporary files must not become the destination object.
- Medium: extra local UUIDs are retained and reported rather than deleted. Review extra rows before cutover, particularly if a source record was hard-deleted since an earlier import.
- Medium: simultaneous command execution must refuse the second operation. Production mutation requires confirmation or an explicit `--force` invocation in the approved cutover.

## Permission checks

The MySQL user should have privileges on the dedicated target only. Public imported images are served through the public disk; copied source writing/authentication JSON remains in private storage. A public storage request must not return the private snapshot. No administrator role or HTTP API access is granted by this command. Later policies/session authentication still require their own verification.

## State verification

Compare all mapped columns, not just row counts. Confirm 77/77 poem texts, 1/1 chapter text, 44/44 generation prompts, and 77/77 poem publication timestamp values (including null) match the source. The import should retain 76 published poems and one draft. There should be 61 mapped asset references, no remaining original Supabase asset URLs in those fields, and 45 matching local file checksums. Imported account credentials must verify through Laravel without revealing them.

## Findings

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |
| I1 | Setup | Local database selection | Dedicated empty target | Existing `dreamerspalette` was the blog; migration stopped before any table change. Separate `dreamerspalette_laravel` was created and used. | High | Fixed |
| I2 | Runtime | Import and verify commands | Matching source records and files across reruns | 141/141 rows and 45/45 files match; second local import created zero UUIDs | High | Ready for retest |
| I3 | Runtime | Account import | Original password works and subsequent changes survive reruns | Laravel verification passes; changed-password preservation passes on disposable data | High | Ready for retest |
| I4 | Runtime | Source and target preflight | Reject corruption, unknown owners, and UUID collisions | All three rejection probes pass with conflicting target record retained | High | Ready for retest |
| I5 | Runtime | Media copy and HTTP delivery | Atomic copies and correct public media | Fresh copy verified 45 files with no temporary files left; HTTP image bytes match; private snapshot not disclosed | High | Ready for retest |
| I6 | Boundary | React application | Use local Laravel content and authentication | React service switch, authorization, provider integration, and final live export remain pending | High | Documented |

## Session evidence

Verified by running the actual importer, read-only verification, direct MySQL comparisons, disposable transactional probes, and local HTTP requests. No automated tests were added. All source access was read-only; the blog database's tables and production data remain unchanged. The full editor/reader workflow against Laravel is a later approval package.
