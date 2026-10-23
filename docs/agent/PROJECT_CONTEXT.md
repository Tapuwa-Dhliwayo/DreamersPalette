# Project Context

**Project:** DreamersPalette. **Owner:** TAPUWA_OR_ALATE.
**System:** LARAVEL. **First real v1 production deployment:** DEPLOYED (existing Supabase application).
**Refactoring risk:** SURGICAL. **Testing:** NO_AUTOMATED_TESTS.
**Can deployed migrations be rewritten?** NO.

## Current architecture

Laravel 13 REST APIs and MySQL, React 19/React Router 7, Vite with Laravel
integration and Tailwind CSS 4. No Inertia or Livewire conversion.

React source lives in `resources/js`; Laravel renders `resources/views/app.blade.php`.
Session authentication uses Sanctum/Fortify with optional TOTP and passkeys.
Spatie permissions and resource policies enforce server access. Frontend capability
checks are presentation only. Service modules own React's backend interactions.

Content, uploads, image generation and grammar requests now use Laravel. Supabase
is historical import/rollback material, not an application runtime dependency.
Public dictionary/Datamuse lookups remain direct browser requests.

## Local database and authorization

Use only `dreamerspalette_laravel`. `dreamerspalette` is the separate blog database.
All ten migrations are applied, including the three authentication migrations.
The environment-configured administrator has been synchronized. Do not refresh
or replace the database. The original imported content and 45 storage files remain.

Historical `profiles.role` does not authorize anything. The designated account's
roles come from Spatie. Optional factors and explicit environment-controlled
password synchronization are deliberate user decisions.

Read `docs/LARAVEL_SYSTEM_REVIEW.md` for verification, trade-offs, audit order and
remaining environment/release work. This supersedes the previous code-review-only
authentication restriction: Tapuwa authorized runtime verification on 2026/10/20.

## Protected areas

- Reader theme classes, contrast, backgrounds, safe-area spacing and mobile viewport.
- Existing slugs and reader URLs; editing a title must not regenerate its slug.
- Original UUIDs, writing, source timestamps and media in the private snapshot.
- The blog database and production Supabase data.

## Development and checks

Run `php artisan serve` and `npm run dev`; open the Laravel URL. Built assets use
`npm run build` and live under `public/build`. Forge's document root is `public`.
Run `npm run lint`, `vendor/bin/pint --test`, and `composer validate` for static checks.
Existing React lint findings are documented in the system review handoff.

Verify workflows through actual requests/scratch probes and focused manual
charters; never add automated tests. Preserve existing test files.

## Release boundary

Implementation is local, not deployed to Forge. Server provider credentials,
real mail, browser/device passkeys and Forge staging/cutover remain unverified.
Use `docs/LARAVEL_MIGRATION.md` for operations and the source-integrity boundary.
