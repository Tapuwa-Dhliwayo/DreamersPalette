# Project Context

Updated: 2026/10/27.

**Project:** DreamersPalette. **Owner:** TAPUWA_OR_ALATE.
**System:** Laravel 13 REST APIs and React 19. **Testing:** NO_AUTOMATED_TESTS.
**Publishing Laravel deployment:** LIVE on Anubis; final Supabase snapshot imported,
Cloudflare cutover verified by Tapuwa, and Vercel removed. Both blog and publishing
migration histories are deployed and immutable.

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

Models own lifecycle behavior, Form Requests validate API inputs, policies authorize
resources, and existing React services use same-origin Laravel APIs. The new private
manuscript/worldbuilding studio is Laravel + Livewire 4 + Flux Pro with Form Objects
and small browser-local editor modules. Existing React poetry/readers remain intact.
Studio writing is separate from published chapter rows. Drafts have one active
generation; finishing locks it and starts the next from identical content. Editorial
review uses author-issued draft grants and separate proposed changes, never
by unlocking finished prose. No repository/per-verb action layer is required.

## Local and remote state

Anubis runs both sites in `dreamerspalette`, with numeric shared users and separate
blog/publishing migration ledgers. Earlier authorised work imported publishing and
linked its public storage; the new studio is local work only so far. Naming a server
authorizes read-only investigation; do not infer permission for a new deployment.

Local development was repaired on 2026/10/25: normal `.env` now uses the device’s
persistent MySQL database `dreamerspalette_laravel` with the compatible numeric
schema and studio migrations. The old UUID tables are preserved under
`legacy_20261025_` names and a private SQL backup. The existing local account’s
email/password were retained. Do not run destructive fresh/reset commands: the
current writing must survive. Tapuwa clarified that obsolete UUID data is not a
compatibility or preservation requirement; Anubis’s deployed MySQL schema is the
local migration baseline. Legacy cleanup was deferred while reviewing studio visuals.

Earlier verification used `.env.rebuild` and isolated socket-only MySQL.
A second scratch database verified a standalone install. No production user rows
were copied. Private resumption instructions are in
`storage/app/private/native-rebuild/README.md`.

Read `docs/LARAVEL_MIGRATION.md` and `docs/LARAVEL_SYSTEM_REVIEW.md` for current
operations, shared-user boundaries, evidence and remaining release checks.

## Commands and safety

Use `php artisan serve` for normal persistent local development. The optional
private rehearsal environment still uses `--env=rebuild` after starting its
isolated MySQL helper. Use `npm run dev` for development assets or
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

Studio visual direction, 2026/10/25: manuscript pages are part of DreamersPalette,
not a separate visual application. Reuse the dashboard’s global atmospheric
background and framed surfaces while keeping the writing area readable.

World/magic increment, 2026/10/25: private Worlds screens now live locally under
/studio/worlds. World systems own Markdown notes/aliases, rules, linked cases and
immutable versions. Manuscripts link to a shared world and explicitly qualify,
replace or exclude its rules with source-change review. Finished drafts pin the
system versions and copy manuscript adjustments. See WORLD_MAGIC_WORKSPACE.md.
Use explicit Save for world forms; the manuscript retains autosave. No automatic
alias highlighting, generic encyclopedia or geography tools are claimed yet.
2026/10/27: Tapuwa now wants to use real data on the live site after a first-version
code audit and cleanup. The candidate is committed locally on dev and remains undeployed.
See STUDIO_V1_CODE_AUDIT.md and STUDIO_V1_RELEASE.md for findings and live gates.

World development expansion, 2026/10/25: private world tools now add combined
character/God/Herald roles, event participants and fictional chronology, arcs/beats
with Flux Pro charts, and reusable custom-axis alignment placements. New finished
drafts retain these records as copied context. See WORLD_DEVELOPMENT_TOOLS.md.
Relationships, calendar arithmetic, placement dragging and temporal changes remain
future work. This continues the approved browse/expanded-tools visual direction.


Local increment, 2026/10/26: encyclopedia entries and manuscript passage references
now support selection-based creation/linking/aliases and preview recognition. Private
studio records have recoverable archive/restore tools; old queued chapter saves are
invalidated. No cascading deletion or permanent purge. See
ENCYCLOPEDIA_REFERENCES_AND_ARCHIVING.md (under docs/) for actual scope and limits.
Raw-editor inline highlighting, relationship/comparison tools, maps and editorial
collaboration remain future work. Production remains unchanged.

2026/10/26: local world Relationships and Compare routes now use the Thinking
Livewire component, RelationshipForm/ComparisonForm and two additive tables.
These reuse the active subject catalogue, retain existing archived subject links,
archive/restore independently and copy into finished draft context. See
`docs/WORLD_RELATIONSHIPS_AND_COMPARISONS.md`. No production deployment.

2026/10/26: manuscript Compile uses a separate Livewire component/Form Object,
studio_compiles manifests and private files, working/finished chapter selection,
Markdown/Browsershot PDF and 24-hour retention. Preferences live on manuscripts;
normal scheduler prunes expired copies. Regular Chrome headless mode keeps its
sandbox enabled. See docs/MANUSCRIPT_COMPILE.md. No production deployment.


## Current local studio increment

The authoritative delivery/review ledger is `docs/STUDIO_REMAINING_WORK.md`.
Local dev now includes recovery-first draft restoration, private versioned maps,
custom calendars, deliberate public-book adoption/finished-draft releases, scoped
editorial review, investigations, dependency impact and knowledge/reveal records.
Compile supports Markdown, sandboxed Browsershot PDF and fixed review packets.
Optional local MCP retrieval is scoped to an explicitly enabled, expiring packet;
no remote HTTP connector or client/account configuration was enabled. Details:
`docs/STUDIO_NEW_WORKFLOWS.md` and `docs/STUDIO_REVIEW_HANDOFF.md`.

Studio migrations through `2026_10_26_000010_create_world_reasoning_tools` are
applied locally; Anubis is unchanged. No database reset was performed. Exact
synthetic review fixtures were removed; real world 1/manuscript 1 were preserved.
The local server runs as transient user service `dreamers-studio.service`, with
Node 22 for PDF rendering. Stop it before starting another server on port 8000.
It is not enabled at boot. No studio commit/push/deployment has been performed.

2026/10/27 Anubis preparation: Tapuwa authorised preparing the live server for
this candidate. Site-specific upload limits, a publishing scheduler, matching
Chrome dependencies and a dedicated PHP-FPM worker are installed. Sandboxed PDF
rendering passed under that actual worker; the old site's home/login still return
200. The shared PHP service's namespace restrictions remain unchanged. Production
code is still master c20cb94; studio migrations have not run. See the preparation
record in STUDIO_V1_RELEASE.md for paths and Forge maintenance implications.

2026/10/27: Tapuwa requested focused commits for a code read-through. The complete
studio candidate is committed locally on dev; see STUDIO_CODE_READTHROUGH.md.
No push, merge, studio migration or application deployment accompanied the commits.
