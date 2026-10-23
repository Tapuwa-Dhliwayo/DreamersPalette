# Reader opens the existing website through Laravel

Date: 2026/10/17. Timebox: 30 minutes.

## Risk level

Medium: moving the entry point and updating dependencies can break direct reader links or asset loading.

## Goal and stop condition

Read an existing published poem and novel through the local Laravel host, including direct-link refreshes. Stop after both asset modes and access checks are recorded, or on a blocking failure.

## Accounts and roles

Use a signed-out browser. No administrator account is needed. Content still comes from the configured Supabase project; do not edit production content.

## Test data

Public records observed during foundation verification:

- Poem `/poems/you-aint-romatic`.
- Collection `/collections/heartbreaks-woes`.
- Novel `/books/dawn-of-the-moon`.

The database owner may change these records after this snapshot. No private fixture is created in this step.

## Fixture reset

No database reset. No records are written by this charter. Stop Vite before checking built assets; rebuild with `npm run build`. Preserve `.env` and all source content.

## Starting state

Run `php artisan serve --host=127.0.0.1 --port=8000`. With Vite stopped, run `npm run build`, then open http://127.0.0.1:8000 in a signed-out browser.

## Data shape review

Read `routes/web.php`, `resources/views/app.blade.php`, `vite.config.js`, and `resources/js/app/router.jsx`. Check that route slugs and paths remain unchanged, assets resolve through the Vite manifest, and API paths cannot be swallowed by the React shell. Domain schema is not introduced yet.

## Intended workflow

1. Open `/`. Expect the existing literary homepage, featured poem, collection links, and novel listing. Observe backgrounds, fonts, and reader contrast.
2. Open the featured poem. Expect its title and Markdown content; refresh the page and expect the same reader rather than a server 404.
3. Open `/collections/heartbreaks-woes` directly. Expect the published collection and its theme; follow a poem and use browser Back.
4. Open `/books/dawn-of-the-moon`. Expect its cover, synopsis, and published chapters. Open an available chapter and refresh its URL.
5. Start `npm run dev -- --host 127.0.0.1`, refresh the Laravel URL, and repeat the homepage-to-poem navigation. Expect the same content with development assets.
6. Stop Vite and refresh. Expect the built website to return without requests to a stopped development server.

## Risk map for probing

- High: accidentally writing to the live Supabase project from the local dashboard.
- Medium: direct-link refreshes, nested chapter URLs, stale hot-file references, missing covers or themed backgrounds.
- Medium: dependency updates affecting reader layout, mobile viewport behavior, share-card rendering, or login behavior; these need later workflow-specific verification.
- Low: existing JavaScript lint findings and the large-main-chunk build warning.

## Permission checks

Open `/dashboard` while signed out. Expect the existing React protection to send the reader to login. Request `/api/missing` with JSON acceptance and expect JSON 404 rather than the React shell. Laravel authentication and domain authorization are pending; this charter does not establish their security.

## State verification

No content writes or schema migrations should occur. Verify `/up` returns 200. Confirm no environment values, generated assets, or vendor dependencies appear in the Git diff. MySQL is configured but not yet provisioned or connected.

## Findings

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |
| F1 | Verification | Local MySQL connection | Reach local target database | PDO connection fails with code 2002 | Medium | Needs investigation |
| F2 | Verification | Existing React source with updated ESLint | Clean lint | 24 errors and 1 warning; original main source produces the same counts under current tooling | Medium | Documented |
| F3 | Verification | Production build | Asset build succeeds | Passes; main chunk exceeds 500 kB warning threshold | Low | Documented |

## Session evidence

Agent verified production asset build, Laravel startup, homepage with live published content in both built and development asset modes, HTTP 200 for home/collections/health, and HTTP 404 for an unknown API path. Composer validation, platform checks, formatting, and Git whitespace checks pass. No automated tests were added. The full reader navigation and mobile visual pass remain for Tapuwa.
