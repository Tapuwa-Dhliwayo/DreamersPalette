---

# Phase 5 Plan — Literary Expansion & Production Hardening

Phase 5 transitions from feature completeness to platform maturity.

Focus areas:

- Books parity
- Performance discipline
- AI integration
- Production stability

---

## Pillar A — Books Theming Parity

- Apply theming engine to `/books/:slug`
- Apply theming engine to `/books/:slug/chapter/:number`
- Ensure chapter navigation respects immersive surface
- Maintain consistent ReaderLayout behavior

Goal: Books feel native to the same atmospheric engine.

---

## Pillar B — Performance & Data Discipline

- Add pagination to collections, poems, and books
- Narrow Supabase select projections (avoid `select("*")`)
- Optimize image uploads (size discipline)
- Audit all public queries for minimal payload

Goal: Deployment-ready scalability.

---

## Pillar C — AI Asset Integration

- Generate collection backgrounds from title/description
- Generate book covers from synopsis
- Store generated assets in `generated_assets` table
- Upload AI images to Supabase Storage
- Track prompt history for reproducibility

Goal: Author-assisted world building.

---

## Pillar D — Editorial Refinement

- Smooth background fade transitions between collections
- Subtle micro-interactions for ReaderNavigation
- Optional accent underline style system
- Improved edit → preview workflow

Goal: Premium reading-app polish.

---

## Pillar E — Deployment Hardening

- Full RLS audit across all tables
- Storage bucket policy audit
- Environment variable validation
- Production Supabase project configuration
- Define v1.0.0 milestone criteria

Goal: Stable public deployment.
