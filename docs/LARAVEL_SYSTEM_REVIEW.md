# Laravel system review handoff

Updated: 2026/10/23. Local implementation; not deployed to Forge.

## What is ready to audit

Laravel 13 now owns session authentication, authorization, content persistence,
image uploads, image-generation orchestration and grammar requests. React remains
the interface. No Inertia, Livewire conversion, repository layer or action classes
were introduced. Supabase is retained only as the historical source/import and
rollback reference; the browser no longer needs its SDK or credentials.

All ten migrations are applied to `dreamerspalette_laravel`. The configured
administrator was synchronized; a second synchronization preserved its password.
The separate blog database `dreamerspalette` was not changed.

## Read in this order

1. `routes/web.php`, `routes/api.php`, `routes/auth.php`, `bootstrap/app.php`:
   delivery boundaries, stateful sessions, CSRF, verification and throttling.
2. `config/authorization.php`, `app/Policies`, `app/Console/Commands/SyncAdministrator.php`:
   capabilities, ownership and explicit environment-driven administrator setup.
3. `app/Http/Requests/Content`, `app/Http/Controllers/Content/ContentController.php`:
   validated fields, server-assigned ownership, same-author parent selection,
   stable slugs, duplicate chapter numbers and lifecycle authorization.
4. `app/Models/Concerns/PublishesContent.php`, `Poem.php`, `Chapter.php`,
   `app/Http/Controllers/Content/ReaderController.php`: archive/restore semantics,
   publication history, unpublished/archived parent exclusion and protected previews.
5. `AssetController.php`, `ImageGenerationService.php`, `scripts/generate-image.mjs`,
   `WritingAssistantController.php`, `config/services.php`: local media, provider
   credentials, timeouts and provider failures.
6. `resources/js/services`, the editor upload handlers and collection reader:
   same-origin requests, CSRF recovery, existing service contracts and reader URLs.
7. `docs/LARAVEL_MIGRATION.md`, `docs/manual-testing/laravel-content-models.md`:
   source integrity, cutover and manual follow-through.

## Design decisions and costs

The four content types share one small resource controller and request, with an
explicit four-case model map. Their CRUD, ownership and lifecycle flows are the
same. Type-specific fields and parent rules remain visible as explicit cases;
there is no extensible resource registry or generic persistence layer. Split a
resource out when its workflow actually diverges.

Models retain publication/archive/restore behavior. Request validation owns input
rules; policies own capabilities and record access. Administrators can manage
other authors' records, but children must retain the same author as their parent.
The caller must also be allowed to update the selected parent; this is deliberately
stricter than merely being able to see a published parent. Slugs do not change when titles change. Archived records reserve their slugs and
chapter numbers. Concurrent uniqueness collisions return a validation response
rather than overwriting another record; a caller can retry.

Uploaded media is stored on Laravel's local public disk with relative `/storage/` URLs.
The existing ability to enter an external HTTPS image URL is preserved.
The server validates image contents, size and dimensions. Imported media stays
where it was copied. Forge deployments must preserve `storage` across releases
and back it up with the database. Public media URLs remain publicly accessible;
authentication does not turn previously public images into private objects.

The Hugging Face official JavaScript SDK still resolves partner-provider formats,
now called by Laravel through a fixed Node script. This avoids hand-maintaining
provider-specific HTTP adapters, at the cost of requiring Node and production npm
dependencies on the server. Tokens are passed through process input, never shell
arguments or browser code. See the official provider client documentation:
https://huggingface.co/docs/inference-providers/en/index

Optional two-factor/passkeys, environment-driven administrator passwords and
explicit password synchronization preserve Tapuwa's decisions from the preceding
package. They deliberately differ from the secure provisioning skill's defaults
for mandatory MFA and one-time passwords. Public account registration and browser
password reset/change remain disabled. Email verification is not bypassed.

Datamuse and public dictionary lookups remain direct browser requests, as before;
they use public unauthenticated APIs. Grammar requests and secret-bearing image
requests go through Laravel. No Supabase Edge Function remains on the runtime path.

## Verification completed

Real local HTTP requests verified:

- Anonymous studio denial, missing-CSRF rejection, password login/logout,
  verified administrator sessions, account security and password confirmation.
- Reader-role denial and author ownership restrictions, including cross-author
  parent selection; source content was not edited.
- Creation of collections, poems, books and empty chapter drafts; publication,
  parent visibility, archive/restore-to-draft, missing parents and duplicate chapter
  numbers. Scratch records were removed after the checks.
- TOTP enrollment/confirmation, rejection of a replayed TOTP, recovery-code login,
  removal, recent-password protection and password-change session expiry, using
  disposable accounts removed afterward. The real administrator's factors were
  not changed.
- Protected draft preview, public exclusion, stable slugs after title edits, zero
  overlay opacity and HTTPS image URL validation.
- Multipart image upload, exact public image bytes, fake-image rejection and a
  safe unavailable response when image credentials are missing. Scratch upload removed.
- Browser rendering of the imported homepage, featured poem, novel and chapter.

Production assets build successfully; focused service/editor/collection lint,
PHP formatting and Composer validation pass. No automated tests were added.
Full lint still reports pre-existing editor/ref and effect-state findings; see
remaining work. Scratch verification scripts are outside the repository.

Import verification still matches all 140 non-user records and all 45 media
checksums. The user's `updated_at` differs because authorized administrator
synchronization changed the account. This is expected operational drift, not a
content mismatch. Do not rerun an import simply to erase it.

## Remaining environment and release verification

- Hugging Face is configured locally and live generation was verified on
  2026/10/23. The SDK's provider-selection logs were contaminating the binary
  image output; `scripts/generate-image.mjs` now routes SDK diagnostics to stderr.
  A real background request returned a valid 1024×1024 PNG. An authenticated cover
  request returned HTTP 201, persisted a generated-asset record and served its
  1,197,626-byte image correctly. The disposable asset/file were removed.
  Keep provider secrets server-side; no keys enter `VITE_*` variables.
- LanguageTool credentials are optional in this integration. Its configured
  endpoint accepted a synthetic unauthenticated request on 2026/10/22; this does
  not establish guaranteed free application usage. Live grammar behavior through
  the Laravel editor remains unverified.
- Passkey ceremony on a real browser/device remains unverified. Confirm canonical
  HTTPS hostname, relying-party ID and allowed origins on staging. TOTP and
  recovery-code flows have been exercised; do not infer passkey correctness from them.
- Real email delivery is unverified; configure Forge mail before testing verification.
- Existing full-lint findings remain in `EditorPanel.jsx`, `CollectionEditorPage.jsx`,
  `BookDetailPage.jsx`, `HomePage.jsx` and the obsolete suppression in `Logo.jsx`.
  They predate the API switch; focused changed-service checks pass.
- Complete a browser editor/reader pass and mobile check, including share cards,
  unsaved drafts, parent changes and previews. HTTP checks do not establish every
  visual/interaction behavior.
- Forge staging rehearsal, final frozen source export, production backup/import,
  DNS/traffic switch and rollback rehearsal are not yet performed. The current
  snapshot is from 2026/10/17, not a fresh production cutover snapshot.

No production deployment, live Supabase writes, database refresh, automatic email
or final code audit was performed in this implementation pass.
