# Laravel migration operations

Updated: 2026/10/20.

## Current local state

The dedicated MySQL target is `dreamerspalette_laravel`. The database named
`dreamerspalette` contains the separate blog application; never refresh, migrate,
or import this application's records into it.

The current verified import contains 141 records: one user, one profile, 16
collections, 77 poems, one book, one chapter, and 44 generated assets. All 45
source storage objects are copied (9,760,772 bytes), including the object not
currently referenced by a record. Checksums are recorded for every file.

React now uses Laravel for authentication, content and uploads. All ten migrations
are applied locally, and the administrator was synchronized separately from the
import. See `docs/LARAVEL_SYSTEM_REVIEW.md` for current verification and review.
Importing alone does not assign Laravel administrator permissions.

## Connection and schema

Configure `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in
local `.env`. The database user needs rights on this application's database only.
Confirm the target name before running migrations. On a new empty target:

```bash
php artisan migrate
php artisan storage:link
```

Do not run `migrate:fresh`. Existing business content must remain intact.
The content migrations introduce no database foreign keys or cascade deletions.

## Import and verify

The default private snapshot directory is `storage/app/private/migration-source`.
Override it with `--source=/absolute/path` or `SUPABASE_MIGRATION_SOURCE_PATH`.
`SUPABASE_MIGRATION_SOURCE_URL` identifies the original Supabase project URL;
during the transition it falls back to `VITE_SUPABASE_URL`.

```bash
php artisan dreamers:import-supabase --dry-run
php artisan dreamers:import-supabase
php artisan dreamers:import-supabase --verify
```

Dry-run validates source structure, ownership, timestamps, source checksums,
filesystem destinations, target schema, and uniqueness conflicts. It reports
existing/new UUIDs without changing records or copying media. A lock file is used
to serialize snapshot operations.

Import updates source-owned rows by UUID, preserving exact source timestamps and
publication history. It does not regenerate slugs or invoke normal model events.
Source-owned rows are synchronized: local editorial changes to the same UUIDs
will be replaced by source values. Extra local rows are retained and reported;
review these deliberately before a production cutover. No missing source row is
automatically deleted from the target.

An existing user's password is never replaced on rerun. A first import translates
the PostgreSQL bcrypt prefix into PHP's equivalent `2y` prefix so Laravel can
verify the same existing password. No plaintext password, hash, or account email
is included in command output. No email verification is granted beyond the source
confirmation timestamp, and no administrator role is granted by the importer.

Record changes run inside one database transaction. Files are validated and copied
before that transaction, using temporary files and atomic moves. A database
failure can leave verified, unreferenced media copies, which a rerun reuses.
Existing destination files with different checksums cause a refusal rather than
an overwrite. Importing never contacts Supabase or an external provider.

Verification compares every imported source column after timestamp/decimal/URL
mapping, counts extra rows, and hashes every destination file. Password equality
is intentionally excluded for existing accounts; the target must have a supported
password hash. Verification is read-only except for the operational lock file.

Production writes require the command's production confirmation or `--force`.
Use `--force` only within an explicitly approved cutover. Final live export and
production deployment tooling are still pending; the current snapshot is a local
migration snapshot, not the final production cutover backup.

## Source snapshot format

The importer accepts this private directory shape:

```text
snapshot.json
schema.json
files-manifest.json
files/<bucket>/<original object path>
```

`snapshot.json` contains:

- `captured_at`: an ISO-8601 capture timestamp;
- `tables`: all columns of profiles, poetry_collections, poems, books, chapters,
  and generated_assets; timestamps remain strings with source precision;
- `administrators`: the single existing auth user's id, email, encrypted_password,
  email_confirmed_at, created_at, and updated_at; source metadata may also remain;
- `storage_objects`: object bucket_id, name, metadata (including size), and source
  identifiers/timestamps.

`schema.json` contains the captured schema plus `buckets` with each bucket's id
and boolean public visibility. `files-manifest.json` is a list of bucket, name,
status (`ok`), integer bytes, and lowercase hexadecimal SHA-256 for every object.
Capture database records in one read-only repeatable-read transaction. A final
export must take place after content editing stops, and include actual storage
files. A PostgreSQL SQL dump alone is not this JSON format and cannot be passed
directly to the importer.

Snapshot files contain private writing and authentication material. Keep the
source directory private and outside Git. The captured directory is ignored;
never add it to an archive intended for public distribution.

## Asset mapping and visibility

Public files map to `storage/app/public/imports/supabase/<bucket>/<object path>`.
Their database references become relative `/storage/imports/supabase/...` URLs,
which continue working when the site's hostname changes. Known embedded media
URLs in Markdown and descriptive text are mapped too. Private-bucket objects
copy to the private filesystem disk. An unsupported original storage URL causes
a refusal; it is not silently exposed or left pointing to Supabase.

The source URLs on Supabase's hostname cannot be transferred to our server. The
application's own `/collections`, `/poems`, `/books`, and chapter routes retain
their existing slugs and paths. Source storage files remain available in Supabase
for rollback.

## Verification recorded

Applied migrations and imported to the dedicated local database; rerunning added
zero UUIDs. Every mapped record and all 45 media checksums match. Runtime probes
on disposable MySQL verified changed-password preservation, original-password
compatibility with Laravel, retention/reporting of extra local rows, rejection of
corrupt files/unknown owners/UUID conflicts, and fresh atomic media copies with
no temporary files remaining. An imported image returned HTTP 200 with matching
bytes; the private source snapshot was not disclosed by the public storage link.

No automated tests were added. Use the focused manual charter in
`docs/manual-testing/supabase-import.md`. Laravel HTTP authorization and reader
workflows against MySQL remain for the application switch.

## Application activation and release

Authentication migrations and administrator synchronization were applied locally on
2026/10/20. Laravel APIs now serve content, uploads, image generation and grammar;
React no longer needs Supabase credentials or the Supabase SDK.

The latest integrity check matches all 140 non-user records and all 45 source
file checksums. The account's `updated_at` differs because administrator
synchronization updated it; its password is intentionally operational state.
Do not reimport to make the account timestamp match an old snapshot.

For a new Forge target: install Composer and npm dependencies, set private server
environment variables and the dedicated database, run `php artisan migrate --force`,
run `php artisan storage:link`, import the reviewed frozen snapshot, and explicitly
synchronize the configured administrator. Build assets with `npm run build` and
build the Laravel configuration cache from the intended environment. The Node
runtime and `@huggingface/inference` dependency must remain available to PHP;
`NODE_BINARY` may specify the absolute executable path. Preserve and back up
`storage/app` across deployments. Configure SMTP and provider keys server-side.

Rehearse on Forge staging before production. Verify existing reader paths, local
media, login, permissions, editor writes, mail and browser/device passkeys with the
canonical HTTPS hostname. A new origin may require new passkey enrollment.

Final production cutover requires stopping editing in the old application,
capturing a fresh records-and-media snapshot, backing up the destination,
importing/verifying, and only then switching traffic. Preserve Supabase as rollback
material; do not delete its data or media. This work has not deployed or switched
production traffic. See the system review handoff for remaining checks.
