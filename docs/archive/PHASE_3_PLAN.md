# Dreamer’s Palette — Phase 3 Plan  
## Poems System + Markdown Engine

---

## Overview

Phase 3 activates the core literary unit of Dreamer’s Palette: **Poems**.

This phase transforms the platform from a structural CMS foundation into a functional literary publishing engine.

Collections provide structure.  
Poems provide substance.

---

## Version Target

**v0.3.0**

---

# Pillar A — Poems Content Backbone

## Objective

Activate the `poems` table as a fully operational content layer aligned with:

- Supabase RLS enforcement
- Service-layer query discipline
- Slug-based routing stability
- Publish workflow integrity

---

## 1. Service Layer Expansion

Extend:
/src/services/contentService.js


### Author Methods

- `getMyPoems()`
- `getPoemsByCollection(collectionId)`
- `createPoem(data)`
- `updatePoem(id, data)`
- `deletePoem(id)`
- `togglePoemPublish(id, boolean)`

### Public Methods

- `getPublishedPoemBySlug(slug)`
- `getPublishedPoemsByCollection(slug)`

---

### Rules

- All writes must inject `author_id` in service layer
- No inline Supabase queries inside components
- Public queries must enforce `is_published = true`
- RLS remains the source of truth for ownership

---

# Pillar B — Markdown Editor System

## Objective

Introduce a split-view Markdown editor with live preview.

Poetry formatting must preserve:

- Line breaks
- Stanza spacing
- Indentation where intentional

---

## 1. Editor Component

Create:
/src/components/editor/EditorPanel.jsx


### Layout Structure
EditorPanel
├── Markdown Textarea (Left)
└── Live Preview (Right)


---

## 2. Technical Requirements

- Markdown stored in `content_md`
- Preview rendered using React Markdown
- Preserve spacing using: white-space: pre-wrap


- Reading container width: `max-w-3xl`
- No WYSIWYG editors
- No rich text abstractions

Markdown is the source of truth.

---

## 3. Supported Markdown Features

- Headings
- Italics
- Bold
- Blockquotes
- Line breaks (critical)
- Stanza spacing (must never collapse)

HTML injection must be disabled.

---

# Pillar C — Dashboard Poems UI

## New Page
/src/pages/dashboard/Poems.jsx


---

## Required Features

- List poems owned by author
- Filter poems by collection
- Create poem (modal or route-based)
- Edit poem using EditorPanel
- Delete poem
- Publish / Unpublish toggle
- Empty state messaging

---

## UI Constraints

- Use existing UI primitives:
  - Card
  - Button
  - Modal
  - Input
  - Textarea
- Maintain Flux-inspired minimal aesthetic
- Avoid clutter
- Preserve dashboard structural integrity

---

# Pillar D — Public Poem Rendering

## New Page
/src/pages/public/PoemPage.jsx


---

## Responsibilities

- Fetch poem via slug
- Enforce `is_published = true`
- Render Markdown using React Markdown
- Preserve poetic whitespace
- Maintain literary container width
- Provide graceful fallback for unpublished content

---

## Route Structure
/poems/:slug


# Slug System Extension

Phase 3 introduces slug uniqueness validation with retry logic.

Slug stability is critical to routing integrity and must remain deterministic while preventing duplicate collisions.

---

## Slug Requirements

Poems must:

- Use `slugify()` utility
- Be lowercase
- Remove special characters
- Normalize accents
- Collapse multiple hyphens
- Generate slug at creation
- Avoid auto-mutation after publication

---

## Uniqueness Enforcement Strategy

Slug uniqueness must be enforced at **two layers**:

### 1. Database Constraint (Primary Protection)

- `slug` column remains `UNIQUE`
- Database remains the final source of truth

### 2. Service Layer Retry Logic (User-Friendly Handling)

When creating a poem:

1. Generate base slug using `slugify(title)`
2. Attempt insert
3. If duplicate violation occurs:
   - Append numeric suffix
   - Retry insert
4. Repeat until insert succeeds or retry limit reached

---

## Example Strategy

Base title:
The Silent Moon


Generated slugs:
the-silent-moon
the-silent-moon-1
the-silent-moon-2


Suffix increments must remain deterministic.

---

## Retry Rules

- Maximum retry attempts: 10
- If exceeded → throw descriptive error
- Retry logic must exist inside service layer
- UI must never handle slug mutation directly

---

## Architectural Discipline

- Slug retry logic belongs in `contentService`
- UI components must not modify slug
- Slug must be generated and validated before insertion completes
- Published slugs must never change automatically

---

## Routing Impact

Slug integrity directly affects:
/poems/:slug , /collections/:slug


Breaking slug stability breaks routing.

Slug retry logic ensures:

- Stable public URLs
- No duplicate collision failures
- Clean author experience

---

# Pillar E — Collection Detail Enhancement

Update:
/src/pages/public/CollectionDetailPage.jsx


Add:

- List of published poems
- Title + optional excerpt
- Soft hover interaction (no underline)
- Consistent fade-in animation
- Navigation to `/poems/:slug`

---

# Slug System Extension

Poems must:

- Use `slugify()` utility
- Enforce uniqueness at DB level
- Generate slug at creation
- Avoid auto-mutation after publication

Routing stability remains critical.

---

# Security & Discipline

- RLS continues to enforce author ownership
- Service layer remains mandatory
- No direct DB calls inside UI components
- No exposure of service role keys
- No regression to layout structure

---

# Success Criteria for v0.3.0

✔ Poems CRUD functional  
✔ Markdown editor split-view working  
✔ Poetic whitespace preserved  
✔ Public poem rendering stable  
✔ Slug routing validated  
✔ RLS enforcement maintained  
✔ No regression to dashboard architecture  

---

# Strategic Importance

Phase 3 marks the transition from structural CMS to literary publishing engine.

After this phase:

- Authors can meaningfully publish poetry.
- Readers can experience formatted verse.
- The platform becomes content-driven rather than structure-driven.

This is the first emotionally meaningful feature milestone.

---

# Not Included in Phase 3

- Theming engine
- AI image generation
- Books & chapters backbone
- Role-based admin expansion
- Pagination
- Performance optimizations

Those belong to later phases.

---

# After Phase 3

Recommended next expansion:

- Phase 4 — Theming Engine (Signature Feature)
- Phase 5 — Books + Chapters System
- Phase 6 — AI Asset Integration

---

End of Phase 3 Plan
