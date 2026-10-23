# Project Context

Updated: 2026/10/24.

**Project:** DreamersPalette. **Owner:** TAPUWA_OR_ALATE.
**System:** Laravel 13 REST APIs and React 19. **Testing:** NO_AUTOMATED_TESTS.
**Publishing Laravel deployment:** NOT YET DEPLOYED. Existing Supabase publishing
and Laravel blog are live. Blog migration history is deployed and immutable.

## Current architecture

Publishing has been rebuilt to join the existing `dreamerspalette` MySQL database
on Anubis, alongside the blog. This supersedes the separate-database/UUID design.
The user explicitly authorized rebuilding publishing; the blog must be preserved.
Supabase PostgreSQL is the content/behavior snapshot, not an architecture contract.

Use native numeric Eloquent IDs. `users` is the existing shared Laravel table.
Publishing content has nullable unique `source_uuid` columns solely for import
provenance/idempotency. Authors reference users directly. Profiles live in
`publishing_profiles` with user_id. Reader URLs/slugs and source writing remain.

Publishing owns the `publishing_migrations` ledger, prefixed Spatie permission
tables, `publishing_profiles` and `publishing_passkeys`. It never takes over blog
migration history or users.role. Optional encrypted factors remain in publishing
profiles, accessed through Eloquent attributes for native Fortify actions. No
publishing columns are added to shared users. Session cookies and encryption keys
stay site-specific; shared users are not automatic SSO. Session-only Sanctum
explicitly ignores bearer tokens; no unused personal-access-token table is created.

Standard Laravel logout rotates the shared remember token. Password rotation is
family-wide and requires `dreamers:sync-admin --rotate-password`; a normal sync
preserves existing credentials. Administrator ID, when configured, is numeric.

Models own lifecycle behavior, Form Requests validate inputs, policies authorize
resources, and the existing React services use same-origin Laravel APIs. No Inertia,
Livewire conversion, repository layer or per-verb action classes were introduced.

## Local and remote state

Anubis was inspected read-only. It already runs the blog in `dreamerspalette`,
with numeric users, eight posts and a separate blog migration ledger. No server
writes or publishing deployment were performed. Naming a server authorizes
read-only investigation in this chat; do not infer permission for remote mutation.

The original local `dreamerspalette_laravel` UUID database and `.env` are preserved;
they do not match the rebuilt code. Verification used `.env.rebuild` and isolated
socket-only MySQL with the copied blog schema plus synthetic account/ledger data.
A second scratch database verified a standalone install. No production user rows
were copied. Private resumption instructions are in
`storage/app/private/native-rebuild/README.md`.

Read `docs/LARAVEL_MIGRATION.md` and `docs/LARAVEL_SYSTEM_REVIEW.md` for current
operations, shared-user boundaries, evidence and remaining release checks.

## Commands and safety

Use `php artisan serve --env=rebuild` for the private rehearsal environment after
starting its local MySQL instance. Use `npm run dev` for development assets or
`npm run build` for production assets. Open the Laravel URL; web root is `public`.

Run PHP syntax checks, `vendor/bin/pint --test`, `composer validate`, focused lint
and the frontend build. Existing unrelated full-lint findings remain documented.
Verify by actual requests/scratch scripts and manual charters. Add no automated tests.

Destructive Artisan fresh/reset/rollback/wipe commands are prohibited in all
environments because a shared database contains blog data too. Do not rewrite
deployed blog migrations. Publishing originals were rewritten only because this
Laravel publishing schema has never been deployed. Use forward migrations after
its deployment. Preserve the private snapshot, source Supabase, writing, slugs,
media, blog data and reader theme/accessibility conventions.
