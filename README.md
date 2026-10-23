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

Preserve an existing `.env`. Configure the dedicated MySQL database and private
administrator/provider settings. The local target is `dreamerspalette_laravel`;
`dreamerspalette` belongs to the separate blog and must remain untouched.

All ten migrations have been applied locally and the administrator synchronized.
For a new target, after verifying its name:

```bash
php artisan migrate
php artisan storage:link
php artisan dreamers:sync-admin
```

Administrator synchronization explicitly applies the configured identity and
password. It preserves an unchanged password, revokes remembered credentials
when changed, and does not silently verify email. Two-factor authentication and
passkeys are optional. Never commit actual credentials.

See [migration operations](docs/LARAVEL_MIGRATION.md) before importing data or
preparing Forge. Do not refresh the existing database.

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

Reader paths, UUIDs and slugs remain stable. Public queries exclude unpublished
or archived content and children of unpublished/archived parents. Archive/restore
returns restored records as drafts. Media uses relative `/storage/` URLs.
