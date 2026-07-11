# Dreamer's Palette

A creator-first literary publishing studio paired with an immersive public
reader. Authors write, organize, theme, and publish poetry collections, poems,
novels, and chapters; readers experience each collection as its own
atmospheric visual world.

## Tech Stack

- **Frontend:** React 19 + Vite, React Router, Tailwind CSS v4, React Markdown
- **Backend:** Supabase (Auth, PostgreSQL with RLS, Storage, Edge Functions)
- **AI assets:** HuggingFace FLUX.1-schnell via the `generate-asset` Edge Function
- **Hosting:** Vercel (with Vercel Analytics)

## Getting Started

```bash
npm install
npm run dev
```

Supabase is required at runtime. Create a `.env` with:

```
VITE_SUPABASE_URL=...
VITE_SUPABASE_ANON_KEY=...
```

## Scripts

| Command | Purpose |
|---------|---------|
| `npm run dev` | Start the Vite dev server |
| `npm run build` | Production build into `dist` |
| `npm run lint` | Run ESLint |
| `npm run preview` | Preview a built app locally |

## Project Structure

- `src/pages/public` — public reading pages
- `src/pages/dashboard` — authenticated author dashboard and editors
- `src/components/ui` — shared UI primitives
- `src/layouts` — reader and dashboard layouts
- `src/services` — all Supabase data access
- `src/hooks` — auth, active collection, theming, reader navigation
- `supabase/functions` — Edge Functions

## Documentation

See [docs/README.md](docs/README.md) for the documentation index, including
architecture, theming philosophy, database schema reference, and the current
phase plan. Agent/contributor guidance lives in [AGENTS.md](AGENTS.md).
