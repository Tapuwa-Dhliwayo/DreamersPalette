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

Sprint 2 practical validation focus (non-unit):
- Manual pagination journey checks across first/middle/last pages
- Payload size verification before/after optimization
- Upload guardrail checks for invalid and oversized images

---

## Pillar C — AI Asset Integration

- Generate collection backgrounds from title/description
- Generate book covers from synopsis
- Store generated assets in `generated_assets` table
- Upload AI images to Supabase Storage
- Track prompt history for reproducibility

Goal: Author-assisted world building.

Sprint 2 practical validation focus (non-unit):
- End-to-end generation flow checks (prompt → stored asset → rendered UI)
- Dashboard confirmation of persisted prompt history and timestamps
- Storage URL accessibility checks under expected roles

---

## Pillar D — Editorial Refinement

- Smooth background fade transitions between collections
- Subtle micro-interactions for ReaderNavigation
- Optional accent underline style system
- Improved edit → preview workflow

Goal: Premium reading-app polish.

Sprint 2 practical validation focus (non-unit):
- Visual QA for transition smoothness and navigation micro-interactions
- Manual regression pass for reader navigation continuity
- Screenshot-based signoff on updated reading surfaces

---

## Pillar E — Deployment Hardening

- Full RLS audit across all tables
- Storage bucket policy audit
- Environment variable validation
- Production Supabase project configuration
- Define v1.0.0 milestone criteria

Goal: Stable public deployment.

Sprint 2 practical validation focus (non-unit):
- Role-based manual verification of RLS and storage policies
- Production build + smoke tests on candidate release
- Final go/no-go checklist for v1.0.0 milestone
