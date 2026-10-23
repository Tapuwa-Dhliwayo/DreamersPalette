# Dreamer's Palette

A literary publishing studio with an immersive public reader for poetry
collections, poems, novels and chapters.

## Laravel application

Laravel 13 owns session authentication, permissions, MySQL content, local media
uploads and provider integrations. React remains the frontend in `resources/js`.
Supabase is retained as historical migration/rollback material only.

Requires PHP 8.3+, Composer and a Node version supported by the installed Vite
release. Node is also used server-side by the official Hugging Face SDK.

For a new checkout:

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Publishing is intended to use Anubis's existing `dreamerspalette` database alongside
the blog. It uses shared numeric users, its own migration ledger and publishing-owned
profile/security/permission tables. Supabase is an import snapshot, not a runtime
or schema requirement. See [migration operations](docs/LARAVEL_MIGRATION.md).

For a reviewed new target, configure the database, app key, host-only session
cookie, administrator email/numeric ID and private provider settings. Then:

```bash
php artisan migrate
php artisan storage:link
php artisan dreamers:import-supabase --dry-run
php artisan dreamers:import-supabase
php artisan dreamers:import-supabase --verify
php artisan dreamers:sync-admin
```

The importer preserves existing family account fields and blog records. Normal
administrator sync preserves an existing password; `--rotate-password` explicitly
changes the family password. Public registration/resets are disabled and optional
two-factor/passkeys remain available. Never commit credentials or private snapshots.
Do not run database refresh/reset commands; they are prohibited for the shared database.

The old local UUID database is preserved and does not match the rebuilt code.
Private rehearsal instructions are in `storage/app/private/native-rebuild/README.md`;
that environment runs with `--env=rebuild`. Anubis remains unchanged.

Run in separate terminals, then open the Laravel URL:

```bash
php artisan serve --host=127.0.0.1 --port=8000
npm run dev
```

For built assets, stop Vite and run `npm run build`. Forge's document root is
`public`; preserve `storage` across deployments and keep Node/npm production
dependencies available for image generation. Configure server-only provider keys
and SMTP. No browser Supabase credentials are required.

## Verification and final review

```bash
npm run lint
npm run build
vendor/bin/pint --test
composer validate
php artisan route:list
```

No automated tests are introduced. Real request probes and manual charters are
the verification method. Current evidence, remaining environment checks and the
final code audit order are in [the system review handoff](docs/LARAVEL_SYSTEM_REVIEW.md).
The production build passes; existing full-lint findings are documented there.

## Application structure

- `app/Models` — relationships, publication and archive/restore behavior.
- `app/Policies`, `config/authorization.php` — capabilities and record ownership.
- `app/Http/Requests` — validated API inputs.
- `app/Http/Controllers` — authentication, studio and public reader endpoints.
- `app/Services` — source importing and image-provider orchestration.
- `resources/js` — React pages, components, layouts, hooks and backend services.
- `scripts/generate-image.mjs` — server-only Hugging Face provider client.
- `database/migrations` — MySQL schema and authentication tables.
- `supabase/functions` — historical source, no longer used at runtime.

Reader paths, public slugs remain stable; imported UUIDs are retained as provenance. Public queries exclude unpublished
or archived content and children of unpublished/archived parents. Archive/restore
returns restored records as drafts. Media uses relative `/storage/` URLs.
