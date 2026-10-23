# Publishing rebuild review handoff

Updated: 2026/10/24. Implemented and verified locally; Anubis has not been changed.

## Outcome

Publishing is rebuilt for the family's existing Laravel/MySQL database on Anubis.
The blog keeps its tables and deployed migration ledger. Supabase PostgreSQL is
used as a snapshot of writing, slugs, publishing history, themes and media; its UUID
schema no longer dictates Laravel identities. React remains the established UI.

## Read in this order

1. `config/database.php`, `AppServiceProvider`, `database/migrations`: separate
   publishing_migrations ledger, native numeric keys and the shared-user bootstrap.
   Existing users are inspected, never dropped or altered by publishing migration.
   Destructive fresh/reset/rollback/wipe commands are prohibited in every environment.
2. `User`, `Profile`, `Passkey`, `FortifyServiceProvider`, auth routes: shared family
   identity with publishing-owned profiles/factors/passkeys; session-only Sanctum.
3. `config/authorization.php`, `config/permission.php`, policies and
   `SyncAdministrator`: prefixed publishing permissions and explicit account setup.
4. `SupabaseSnapshot`, `SupabaseImportService`, `ImportSupabase`: strict source
   validation, email-based family account mapping, native ID translation, uniqueness,
   checksums, atomic media copying and transactional data import.
5. Content Form Requests, `ContentController`, `ReaderController` and content models:
   numeric input/route IDs, direct user ownership, publication/archive/restore,
   draft preview and public parent visibility. Reader responses hide source_uuid.
6. React services, image provider helper and storage configuration: existing
   frontend contracts, same-origin CSRF requests, public URLs and Node runtime.

## Decisions and trade-offs

Shared `users` remains the family account record, with the existing blog's numeric
ID and credential ownership. Importing links by email; it never replaces an
existing name, hash, role, verification timestamp or other account column. A missing
user is created from the supported source credentials with a native ID.

Publishing-owned records use auto-incrementing bigint IDs. `source_uuid` remains a
nullable unique provenance key on imported publishing rows, including profiles.
It enables repeat imports without making source IDs application identities.
`author_id` directly references a family user; profiles use a unique user_id.
Original slugs and public reader links stay unchanged. Old UUID-based dashboard
edit links are no longer valid. No foreign-key cascades were introduced.

The migration ledger and Spatie tables are prefixed for publishing, avoiding
ownership of the blog's migration history or its legacy users.role. Optional TOTP
secrets live in publishing_profiles and passkeys in publishing_passkeys. No columns
are added to shared users, so the blog cannot accidentally serialize publishing
factor material. A small Eloquent attribute/save adapter in User gives native
Fortify actions their expected properties and writes private factor changes in
one transaction. This adapter is the explicit cost of sharing identity while
keeping independently encrypted security state out of the blog's model.

A normal administrator sync preserves existing shared passwords. Rotation requires
--rotate-password; verification requires --verify-email or normal verification.
These family-wide changes need deliberate operation. Laravel logout normally
rotates the shared remember token; remembered sessions in other family apps may
be revoked. Sharing a database does not imply automatic SSO or shared MFA/session
state. Keep each site's APP_KEY, cookie and host-only session domain deliberate.
Optional factors and environment-driven provisioning preserve Tapuwa's earlier
instructions, overriding the secure-provisioning skill's mandatory MFA defaults.

The four content resources retain one explicit four-case CRUD controller/request,
without repositories, DTOs, resource registries or per-verb actions. Models own
publication and archival; request validation/policies protect writes. Native integer
casts and input validation preserve ownership checks when selects submit strings.

Media uses local public storage and relative URLs. Original source file paths and
bytes are retained; external HTTPS image URLs are supported. Keep storage persistent
across deployments. The official Hugging Face SDK still runs through a fixed Node
helper; its diagnostic stream is separated from image bytes. This provider bridge
was verified live on 2026/10/23 and not changed by the native-ID rebuild. Public
Datamuse/dictionary calls remain direct; grammar and image calls go through Laravel.

## Verification completed

No automated tests were added. Verification used actual application requests,
scratch scripts outside the repository, and real isolated MySQL databases.

- Copied Anubis's schema only, read-only. Loaded it into a socket-only local MySQL
  instance with a synthetic existing family user ID 37 and blog migration marker.
  No production account rows, password hashes or blog content were copied.
- Applied all nine publishing migrations alongside that schema. The shared users
  schema and blog ledger stayed unchanged. Publishing created its own ledger.
- Imported and verified 140 publishing records plus the linked family account,
  with all 45 media SHA-256 checksums matching. Repeated import created zero records.
- Repeated import and administrator sync preserved every existing user column.
  Authentication rehearsal preserved name, password, legacy role, verification,
  and timestamps. Normal logout rotated only the remember token as expected.
- Repeated the entire migration/import/verification on an empty scratch database:
  the missing source user became a native Laravel user and all mappings verified.
- Real HTTP verified numeric IDs, independent profile/user identity, hidden source
  IDs, login/logout, reader restrictions, author ownership, foreign parent denial,
  collection/poem/book/chapter CRUD, empty chapter drafts, duplicate chapter numbers,
  publication, draft previews, parent visibility and archive/restore-to-draft.
- Real HTTP verified recent-password confirmation, TOTP enrollment/confirmation,
  recovery-code login and removal with factor data isolated from shared users.
  Unsupported bearer tokens returned 401 without introducing an unused token table.
- Scratch accounts and content were removed. Original local UUID data, the private
  Supabase snapshot and live Anubis were not changed.
- Frontend production build, PHP syntax, Pint, Composer validation, focused service
  lint and Git whitespace checks passed. Existing unrelated full-lint findings in
  the editor/reader remain; no frontend redesign was part of this rebuild.

Automatic approval rejected resetting the old local publishing database because
project instructions protected it. The safe alternative was the isolated rehearsal
above; no reset was attempted. Its private state and resumption instructions are
saved under storage/app/private/native-rebuild. Original .env is unchanged;
.env.rebuild is private and must never be committed.

## Remaining deployment and review work

The current GitHub master still contains the preceding UUID implementation until
the rebuilt commit is explicitly pushed. Do not deploy that old version and assume
it supports the shared database. This pass saved code locally; it did not deploy,
change DNS, update remote site configuration or modify Anubis records.

Deploy the reviewed rebuilt version using docs/LARAVEL_MIGRATION.md. Take a full
shared-database/media backup, freeze editing, capture a fresh source snapshot and
reconcile any real local edits. The current snapshot is from 2026/10/17. Never
restore the whole family database as a casual publishing-only rollback.

Anubis already has compatible runtimes and capacity, but still needs a publishing
site/env, persistent storage, production key, SMTP, correct upload/proxy limits and
final-domain HTTPS/passkey configuration. Browser/device passkeys, actual mail,
full browser editor/mobile checks and Forge cutover remain unverified. Local HTTP
and copied-schema rehearsal do not replace those production checks.

LanguageTool's configured endpoint accepted a synthetic unauthenticated request on
2026/10/22. That is evidence of a response, not guaranteed free application usage.
The Laravel editor's live grammar path and supported production usage need review.

Read-only identity confirmation on 2026/10/24 found that the snapshot author
email matches existing Anubis family user 1. No email address or credential was
printed or copied from the server; deployment can link that existing account.
