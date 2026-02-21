# Dreamer’s Palette — Phase 4 Plan  
## The Theming Engine (Signature Feature)

---

## Overview

Phase 4 introduces Dreamer’s Palette’s signature aesthetic capability:

**Collection-Level Theming Engine**

Each poetry collection becomes a distinct immersive literary world.

This phase transforms static pages into atmospheric reading environments.

---

## Version Target

v0.4.0

---

# Pillar A — Theme Data Activation

## Objective

Activate theme fields already defined in `poetry_collections`:

- theme_background_url
- theme_overlay_opacity
- accent_color

No schema changes required.

---

## Required Service Layer Additions

Extend `updateCollection()` to allow:

- theme_background_url updates
- theme_overlay_opacity updates
- accent_color updates

Ensure:
- Author-only mutation
- No service role exposure

---

# Pillar B — ReaderLayout Theme Injection

## Objective

ReaderLayout dynamically applies collection theme.

### Behavior

When visiting:
- `/collections/:slug`
- `/poems/:slug`

System should:

1. Fetch collection theme
2. Apply background image
3. Apply overlay layer
4. Apply accent color (optional)

---

## Rendering Rules

- Background image applied at layout level
- Overlay ensures text readability
- Overlay opacity configurable (default: 0.6)
- Accent color used for:
  - Links
  - Subtle UI highlights
  - Borders (optional)

Must preserve:
- max-w-3xl reading container
- Typography clarity
- Contrast accessibility

---

# Pillar C — Theme Application Architecture

## Avoid:

- Inline background styles in page components
- Theme logic scattered across pages

## Implement:

- Theme context or layout-level state
- Centralized theme application logic

Example flow:

ReaderLayout
  ├── detect current route
  ├── fetch collection theme
  ├── apply background
  ├── render children

---

# Pillar D — Dashboard Theme Controls

Extend `/dashboard/collections`:

Add theme editing capabilities:

- Upload background image
- Set overlay opacity
- Optional accent color input

Must:

- Upload to Supabase Storage (`backgrounds/`)
- Save URL in DB
- Use `{userId}/filename.ext` path convention
- Public bucket read access

---

# Pillar E — Readability System

Every themed page must:

- Include semi-transparent overlay
- Preserve text legibility
- Avoid excessive contrast issues
- Maintain calm aesthetic

Overlay example:

- Black at configurable opacity
- Applied via absolute positioned div
- Text container above overlay

---

# Pillar F — Performance Discipline

- Lazy load large background images
- Avoid layout thrashing
- Avoid re-fetching theme unnecessarily
- Cache theme state per route navigation

---

# Success Criteria for v0.4.0

✔ Themed collection backgrounds render dynamically  
✔ Poems inherit collection theme  
✔ Overlay opacity configurable  
✔ Accent color applied consistently  
✔ Dashboard theme controls functional  
✔ Supabase Storage integration complete  
✔ No readability regressions  

---

# Strategic Impact

Phase 4 differentiates Dreamer’s Palette from generic CMS platforms.

It introduces:

- Immersive reading worlds
- Visual emotional tone
- Authorial aesthetic identity
- AI-ready visual expansion foundation

---

# Not Included in Phase 4

- AI image generation
- Books theming
- Global theme presets
- Animation system
- Reader personalization

---

# After Phase 4

Recommended next phases:

- Phase 5 — Books & Chapters Full Engine
- Phase 6 — AI Cover & Background Generation
- Phase 7 — Author Profiles + Discovery
