# AGENTS.md

Guidance for coding agents working in this repository.

## Project Overview

DreamersPalette is a React 19 + Vite single-page app for publishing and managing literary content. It uses React Router, Supabase, Tailwind CSS v4, and Vercel Analytics.

The app has two main surfaces:

- Public reading pages in `src/pages/public`
- Authenticated dashboard/editor pages in `src/pages/dashboard`

Shared UI components live in `src/components/ui`, layout components in `src/layouts`, hooks in `src/hooks`, and Supabase-backed data access in `src/services`.

## Commands

Use npm scripts from the repository root:

- `npm run dev` starts the Vite development server.
- `npm run build` creates the production build in `dist`.
- `npm run lint` runs ESLint.
- `npm run preview` previews a built app locally.

When changing UI, run at least `npm run lint`. Run `npm run build` before handing off larger changes or changes involving routing, imports, or environment assumptions.

## Environment

Supabase is required at runtime. The app expects these Vite environment variables:

- `VITE_SUPABASE_URL`
- `VITE_SUPABASE_ANON_KEY`

Do not commit secrets. If local commands fail immediately from missing Supabase env vars, report that directly instead of adding fallback credentials.

## Code Style

- This is an ES module JavaScript/JSX codebase. Follow the existing style: double quotes, semicolons, functional React components, and Tailwind utility classes.
- Prefer existing components from `src/components/ui` before adding new UI primitives.
- Keep data fetching and persistence logic in `src/services`; do not scatter Supabase calls through page components unless that is already the established pattern nearby.
- Keep routing changes centralized in `src/app/router.jsx` and route constants in `src/app/routes.js` when possible.
- Use existing hooks for auth, active collection, theming, and reader navigation instead of duplicating state logic.
- Avoid broad refactors unless they are necessary for the requested change.

## UI Guidelines

- Preserve the literary/editorial tone of the public reader pages.
- Preserve dashboard density and utility for authenticated workflows.
- Use Tailwind v4 classes and the global styles in `src/index.css`.
- Be careful with reader contrast, themed backgrounds, safe-area spacing, and mobile viewport behavior. Existing classes such as `h-frame`, `poetry-content`, `reader-muted`, and reader text-shadow utilities are intentional.
- Avoid nested cards and decorative gradients unless they match an existing local pattern.

## Data And Content

- Database and storage reference material lives in `docs`, including initial schema and update history SQL files.
- Treat SQL files and Supabase bucket/security docs as source-of-truth context when changing data assumptions.
- Preserve slug behavior through `src/utils/slugify.js` and color utilities through `src/utils/hexToRgb.js`.

## Reference Projects

When asked to copy an implementation pattern from another project, inspect it read-only and never edit files outside this repository.

## Verification

Before final handoff, summarize what was changed and what was verified.

Preferred checks:

- `npm run lint` for most source changes.
- `npm run build` for routing, import, dependency, or production-surface changes.

If a check cannot be run because of missing env vars, sandbox limits, or dependency issues, state the exact blocker.

## Git Safety

- Do not revert user changes or unrelated work.
- Keep commits and diffs focused on the requested task.
- Do not modify generated output such as `dist` unless the user explicitly asks for it.
