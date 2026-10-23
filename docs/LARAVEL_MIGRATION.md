# Publishing on the shared Laravel database

Updated: 2026/10/24. This supersedes the earlier separate-database migration plan.

## Target and ownership

The publishing app is rebuilt to coexist with the blog in Anubis's existing
`dreamerspalette` database. The blog's deployed migrations and data remain its
own. Inspection of Anubis was read-only; no publishing changes have been deployed.

Both apps use the existing numeric `users.id`. Publishing matches the snapshot's
author by email, preserving an existing family user's name, password, verification,
role, remember token and timestamps. A missing account is created with Laravel's
numeric identity and the source's supported bcrypt password. It receives no
publishing permissions until explicit administrator synchronization.

Publishing's numeric tables are `publishing_profiles`, `poetry_collections`,
`poems`, `books`, `chapters` and `generated_assets`. Each imported record retains
its original UUID in nullable, unique `source_uuid`; Laravel owns primary keys.
`author_id` refers directly to `users.id`; `publishing_profiles.user_id` is unique.
Source parent UUIDs are translated into native collection/book IDs. Source UUIDs
are not exposed in reader responses or accepted as editor IDs.

The snapshot supplies writing, slugs, publication history, theme values and media,
not the application's architecture. Public reader URLs/slugs remain unchanged.
Old UUID dashboard edit links are replaced by numeric-ID edit links.

## Migration and account isolation

Publishing has nine migrations in its own `publishing_migrations` ledger. It does
not alter or take ownership of the blog's `migrations` ledger. Its bootstrap checks
that an existing `users` table has Laravel's expected numeric key and columns; on
an empty standalone database it creates the standard table. It never drops users.
The UUID-based publishing schema was never deployed and has been replaced in its
original migration files. Never run these against the old UUID rehearsal database.

Publishing's Spatie tables use `publishing_` prefixes. The blog's legacy `users.role`
is untouched and does not authorize publishing actions. TOTP fields live in
`publishing_profiles`; passkeys live in `publishing_passkeys`. A small Eloquent
attribute adapter lets native Fortify actions use that private state without
adding encrypted fields to the blog's user schema. Publishing retains its own
APP_KEY and host-only session cookie; sharing accounts does not provide automatic SSO.

Normal Laravel logout rotates the shared remember token, so it can revoke remembered
sign-ins across the family. Explicit password changes also affect both applications.
Administrator synchronization preserves existing passwords by default. Use
`--rotate-password` only for a deliberate family-account password change.
`--verify-email` remains an explicit attestation, not an import shortcut.

Destructive Artisan reset/fresh/rollback/wipe commands are prohibited by
`AppServiceProvider` in every environment. They are inappropriate for a database
that also contains the blog. Take a database backup and preserve media before
any deployment; use reviewed forward migrations after the first publishing deployment.

## Deployment order

1. Create the publishing Forge site with web root `public`, PHP 8.3+ and compatible
   Node. The existing server has PHP 8.5, Node 22 and MySQL 8.4. Keep the blog site
   and DNS unchanged while preparing publishing.
2. Configure the shared database name `dreamerspalette`, site-specific session
   cookie, production APP_KEY, HTTPS APP_URL, SMTP, providers and passkey origin.
   `ADMIN_USER_ID`, if set, is the existing numeric family user ID, not a source UUID.
   It can be left blank to select by ADMIN_EMAIL. ADMIN_PASSWORD is needed only
   for a new account or explicit password rotation.
3. Install Composer and npm dependencies, build frontend assets, then run
   `php artisan migrate --force` and `php artisan storage:link` on the publishing
   checkout. Review `migrate:status`; publishing must use its own ledger.
4. Freeze source editing and take a fresh Supabase records-and-files snapshot.
   The current private reference snapshot is from 2026/10/17, not a final cutover
   export. Reconcile any real local editorial work before importing; source-owned
   publishing records are overwritten on repeat import. Blog records are not.
5. Copy the snapshot privately to the publishing server and run the commands below.
   Production import requires deliberate confirmation or `--force` in the approved
   deployment. Verify before assigning publishing administrator permissions.
6. Run `php artisan dreamers:sync-admin`, then cache production configuration and
   verify login, public reading, editing, publication, media, mail and optional
   factors. Keep Node and `@huggingface/inference` available to PHP. Set NODE_BINARY
   to the server path if needed (Anubis has `/usr/bin/node`).
7. Preserve `storage/app` across releases and back up it and the shared database.
   Increase this site's PHP upload limit from Anubis's current 2 MB to at least
   the application's 5 MB allowance, with suitable POST/Nginx limits. Account for
   the generation service's 120-second timeout in the site proxy/PHP limits.
8. Move the production website DNS and HTTPS only after verification. Retain Vercel
   and Supabase as rollback references until the new deployment is stable. Shared
   database restoration must account for blog writes, not just publishing writes.

## Snapshot operations

The source defaults to `storage/app/private/migration-source`. Override with
`--source=/absolute/path` or SUPABASE_MIGRATION_SOURCE_PATH. Set the original
HTTPS source hostname in SUPABASE_MIGRATION_SOURCE_URL. Legacy VITE_SUPABASE_URL
is accepted only as an import fallback; React no longer needs Supabase credentials.

```bash
php artisan dreamers:import-supabase --dry-run
php artisan dreamers:import-supabase
php artisan dreamers:import-supabase --verify
```

The private format is `snapshot.json`, `schema.json`, `files-manifest.json` and
`files/<bucket>/<original path>`. The snapshot contains all source columns of
profiles, poetry_collections, poems, books, chapters and generated_assets; the
original auth user; a capture timestamp; and storage metadata. The schema includes
bucket visibility. The manifest records each object's successful byte count and
SHA-256. A PostgreSQL SQL dump alone is not this importer format.

Source UUIDs, parents, author ownership, uniqueness, timestamps, booleans, precision,
paths and checksums are validated before database writes. Native IDs are assigned
only by MySQL. Slug/chapter conflicts with unrelated publishing rows fail rather
than overwrite those rows. Extra native records are retained and reported. Source
identity conflicts with an existing family-user mapping require review.

Verified media is copied atomically before one database transaction. A SQL failure
may leave reusable unreferenced files, never partially committed record changes.
Existing destination files with different bytes are not overwritten. Reruns do
not delete missing source records or replace existing family account credentials.
A lock serializes snapshot operations; imports never contact Supabase themselves.

Public URLs become relative `/storage/imports/supabase/...` URLs, including known
embedded references. Private-bucket media remains private. Original Supabase
storage URLs cannot be transferred to the new server; keep the source available
for rollback. Keep all snapshot, account and rehearsal material out of Git.

## Local rehearsal and evidence

The original `dreamerspalette_laravel` database and local `.env` are preserved.
The rebuilt code must not use that old UUID schema. Automatic approval rejected
resetting it, so verification instead used an isolated, socket-only MySQL instance
and ignored `.env.rebuild`. Its `dreamerspalette_rebuild` database has Anubis's
blog schema plus a synthetic existing family user with ID 37 and blog ledger marker.
No production user data was copied. A second `dreamerspalette_standalone` database
verified an empty install and creation of a new native user from the snapshot.

Both imports verified 140 publishing records plus the linked/native user and all
45 media files. Reruns created zero records and preserved every existing user
column. Actual HTTP verified numeric editor IDs/parent validation, source-ID hiding,
login/logout, permissions/ownership, publication, draft previews, archive/restore,
chapter uniqueness and optional TOTP/recovery authentication. Existing user names,
password, verification, legacy role and timestamps stayed unchanged. Normal logout
rotated the remember token as expected; the blog ledger stayed unchanged.

Private local rehearsal startup/continuation instructions are saved under
`storage/app/private/native-rebuild/README.md`. They are local artifacts, not
production deployment steps. No automated tests were added. Read
`docs/LARAVEL_SYSTEM_REVIEW.md` for review order and remaining release checks.

Read-only identity confirmation on 2026/10/24 found that the snapshot author
email matches existing Anubis family user 1. No email address or credential was
printed or copied from the server; deployment can link that existing account.
