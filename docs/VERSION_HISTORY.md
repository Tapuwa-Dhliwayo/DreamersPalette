# Dreamer’s Palette — Version History

This document tracks architectural and feature milestones across versions.

It exists to:

- Preserve architectural intent
- Prevent regression drift
- Track foundational decisions
- Provide rollback clarity
- Support disciplined versioning

---

## v0.1.0 — Infrastructure Foundation Stabilized
**Date:** 2026-02-20  
**Tag:** v0.1.0  
**Branch Merged:** dev → main  

### Overview
Initial architectural stabilization release.

This version establishes the foundational infrastructure for the platform, including authentication, routing, layout separation, and design system setup.

No content CRUD functionality exists yet.

---

### ✅ Implemented

#### 1. Authentication Layer
- Supabase client configured via environment variables
- Service-layer abstraction (`authService.js`)
- Email login-only system
- Public signups disabled
- Database trigger for automatic `profiles` creation
- Reactive `ProtectedRoute` using Supabase auth listener
- Working logout system

#### 2. Routing Architecture
- React Router v6 SPA configuration
- Layout-based route separation
- ReaderLayout (public surface)
- DashboardLayout (protected surface)
- Centralized route definitions (`routes.js`)
- Slug-ready routing structure

#### 3. Layout System
- `<Outlet />` properly implemented
- Container system active
- Reader vs Dashboard UI separation established
- Protected dashboard routing applied at layout level

#### 4. TailwindCSS
- Tailwind v4 configured properly
- PostCSS configured with `@tailwindcss/postcss`
- Global typography base styles implemented
- Dark mode support enabled
- UI component primitives scaffolded

#### 5. Project Structure Stabilized
- Services layer separated
- Hooks layer created (`useAuth`)
- UI components isolated under `/components/ui`
- Public vs Dashboard page separation
- Slug-based route structure prepared

---

### 🧠 Architectural Decisions Locked

- SPA only (no SSR)
- Supabase as sole backend (Auth + DB + Storage)
- Role-ready multi-author schema from day one
- Layout-based routing structure
- Markdown-first content architecture (implementation pending)
- Login-only access during early phase
- Service abstraction for all Supabase calls

---

### 🚫 Not Included Yet

- Content CRUD operations
- Markdown editor integration
- React Markdown rendering
- Collection theming engine
- AI image generation pipeline
- Public content display logic
- Role-based admin controls
- Publishing workflows

---

### Next Planned Phase

Phase 2: Content Backbone

Focus:
- Collections CRUD
- Content service abstraction
- RLS-aligned queries
- Dashboard content management wiring

---
---

## v0.2.0 — Collections Backbone + Public Rendering
**Date:** 2026-02-20  
**Tag:** v0.2.0  
**Branch Merged:** dev → main  

### Overview

Phase 2 establishes the first functional vertical slice of Dreamer’s Palette.

This version transforms the platform from infrastructure scaffolding into a working multi-author literary CMS foundation.

Collections are now fully manageable via the dashboard and publicly readable via slug-based routes.

---

### ✅ Implemented

#### 1. Dashboard Structural Shell
- Permanent 2-column layout
- Sidebar navigation (Dashboard, Collections, Poems, Books, Chapters)
- Profile context integration (display_name + role)
- Logout relocated into Sidebar
- Clean separation between layout and service logic

#### 2. Profile Service Layer
- `profileService.js` implemented
- `getMyProfile()` RLS-safe query
- No inline Supabase queries inside layout components
- Ownership enforced via `auth.uid()`

#### 3. Collections Service Backbone
- `contentService.js` created
- Author methods:
  - `getMyCollections()`
  - `createCollection()`
  - `updateCollection()`
  - `deleteCollection()`
  - `togglePublish()`
- Public methods:
  - `getPublishedCollections()`
  - `getCollectionBySlug(slug)`
- Strict RLS alignment
- Service-layer injection of `author_id`

#### 4. Slug System
- Deterministic `slugify()` utility
- Accent normalization
- Special character stripping
- Hyphen collapsing
- Unique slug constraint enforced at DB level
- Slug-based routing validated end-to-end

#### 5. Dashboard Collections UI
- Modal-based create/edit form
- Draft vs Published workflow
- Publish toggle
- Delete support
- Empty state messaging
- Calm Flux-inspired layout styling

#### 6. Public Collections Rendering
- `/collections` listing page
- `/collections/:slug` detail page
- Published-only filtering enforced
- 404-style fallback for unpublished content
- ReaderLayout typography preserved

#### 7. UI Refinements
- Card hover interaction switched from underline to surface darkening
- Scoped link override for UI surfaces
- Soft staggered fade-in animation for collections
- Maintained separation between prose links and UI links

---

### 🧠 Architectural Decisions Reinforced

- RLS is the source of truth for ownership
- Service layer is mandatory for all Supabase queries
- Slug stability is critical to routing integrity
- UI surface links differ from literary prose links
- Dashboard logic separated from layout structure
- Publish workflow enforced at both service and UI levels

---

### 🚫 Not Included Yet

- Poems CRUD
- Markdown editor integration
- React Markdown rendering
- Collection theming engine
- AI image generation
- Books & chapters backbone
- Role-based admin logic
- Pagination
- Realtime updates

---

### Phase 2 Status

Phase 2 is considered complete.

The platform now has:

- Author content management
- Public content rendering
- Slug-stable routing
- RLS-enforced publishing discipline
- Clean architectural layering

# Version History

---

## v0.3.0 — Poems System + Markdown Engine
Release Date: 2026-02-21

### Overview
Phase 3 activates the core literary publishing engine of Dreamer’s Palette.

This release transitions the platform from structural CMS groundwork to fully functional poetry publishing and reading.

---

### ✨ Major Features

#### 1. Poems Content Backbone
- Full poems CRUD in dashboard
- Author-scoped RLS enforcement
- Slug generation with retry logic (max 10 attempts)
- Database-backed UNIQUE slug constraint
- Deterministic, stable public URLs

#### 2. Route-Based Poem Editor
- `/dashboard/poems/new`
- `/dashboard/poems/:id/edit`
- Clean CMS architecture (no modal editor)
- Collection assignment support
- Draft / Publish workflow

#### 3. Markdown Split Editor
- Custom `EditorPanel` component
- Side-by-side editor + live preview (desktop)
- Stacked layout (mobile)
- Divider for visual separation
- `react-markdown` rendering
- HTML injection disabled
- `white-space: pre-wrap` for poetic formatting
- `max-w-3xl` literary container width

#### 4. Public Reader Experience
- `/poems/:slug` route
- Published-only visibility enforced
- Markdown rendering with preserved stanza spacing
- Graceful not-found states

#### 5. Collection Detail Enhancement
- Published poems listed under collection
- Soft hover interaction
- Public slug navigation
- Published-only enforcement

#### 6. Dashboard Filtering
- Filter poems by collection
- Clean Flux-inspired UI
- Service-layer discipline maintained

---

### 🔒 Architectural Improvements
- Centralized slug retry logic via service layer helper
- UI fully decoupled from slug generation
- RLS enforcement preserved across all queries
- Route-based CMS structure prepared for Books + Chapters
- Markdown-first content discipline solidified

---

### Status

Phase 3 complete.

Dreamer’s Palette is now a functioning literary publishing platform.
