# Version Notes (Current)

## v1.0.0 — First Stable Release

**Date:** 2026-03-30

Dreamer's Palette reaches its first stable release after five development phases.

### What's New Since v0.5.1

- **AI generation migrated** — Pollinations → HuggingFace FLUX.1-schnell via Supabase Edge Function
- **Prompt sanitization** — literary word filtering + negative prompts for text-free images
- **Dashboard Home** — stats, quick actions, recent content overview
- **Collection poem counts** — lightweight count badges on dashboard cards
- **Pagination** — reusable component applied to collections, books, and poem listings
- **Image upload service** — client-side validation + compression before upload
- **Homepage redesign** — fixed header/footer, scrollable body, novel featured cards
- **Select component** — transparent background for light/dark mode respect
- **Dashboard scroll fix** — sidebar always visible, inner content scrolls
- **Vercel Analytics** — page view tracking
- **Brand favicon & icons** — SVG + PNG fallbacks + web manifest + theme-color
- **Mobile back button** — shortened to "← Back" on small screens

### Platform Capabilities

- Poetry collections with immersive theming
- Poems with Markdown rendering and stanza preservation
- Novels with cover imagery and theming parity
- Chapters with previous/next navigation
- Author dashboard with full CRUD + AI generation
- Server-side AI image generation (HuggingFace)
- Responsive mobile-first design with dark mode

For full historical details, see `docs/VERSION_HISTORY.md`.
