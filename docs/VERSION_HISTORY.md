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

---

## v0.4.0 — Theming Engine + Immersive Reader Stabilized
**Release Date:** 2026-02-21  
**Tag:** v0.4.0  
**Branch Merged:** dev → main

### Overview

Phase 4 introduces Dreamer’s Palette’s immersive reading engine.

This release establishes a controlled theming system for poetry collections, integrates author-managed atmospheric styling, and stabilizes Reader navigation architecture.

The platform now supports dynamic, author-driven aesthetic worlds while preserving deterministic readability.

---

### ✨ Major Features

#### 1. Collection Theming Engine

Each poetry collection now supports:

- `theme_background_url`
- `theme_overlay_opacity`
- `accent_color`
- `theme_text_mode` (light / dark)

Theming is database-driven and applied dynamically at the layout level.

---

#### 2. ReaderLayout Redesign (Framed World Model)

- Removed full-viewport background stretching
- Introduced neutral outer surface (light/dark controlled)
- Contained themed background inside centered canvas
- Preserved readability under arbitrary image conditions
- Prevented background dominance

This creates a curated immersive world instead of uncontrolled wallpaper.

---

#### 3. Deterministic Contrast System

`theme_text_mode` now controls:

- Outer surface tone
- Overlay polarity (dark vs light)
- Reading surface contrast baseline

No runtime image analysis required.

Readability is author-controlled and stable.

---

#### 4. Accent Color Injection

Accent color now:

- Injected via CSS variable (`--accent-color`)
- Used for navigation highlights and interactive elements
- Decoupled from structural contrast logic

Accent is expressive identity, not readability logic.

---

#### 5. Performance Deduplication

Introduced:

`useActiveCollection()`

Collection resolution centralized and consumed by:

- `useCollectionTheme`
- `useReaderNavigation`

Eliminated duplicate Supabase queries on poem routes.

---

#### 6. Reader Navigation Stabilization

ReaderNavigation now supports:

- Collections index level
- Collection detail level
- Poem level (previous / next)
- Deterministic theme reset when leaving themed routes

No lingering background artifacts.

---

#### 7. Draft Preview System

Added:

`/preview/collections/:slug`

Features:

- Auth-required
- Draft collections visible to authors
- Uses full ReaderLayout
- Uses preview-safe service method
- Respects RLS ownership

Authors can preview immersive worlds without publishing.

---

### 🔒 Architectural Decisions Reinforced

- Theme logic lives at layout level
- Contrast is deterministic, not heuristic
- Accent is expressive, not structural
- Collection resolution centralized
- Preview route separated from public route
- RLS remains security authority

---

### 🚫 Not Included Yet

- AI-generated theme backgrounds
- AI-generated book covers
- Books theming parity
- Pagination
- Performance caching layer
- Accessibility contrast tooling
- Admin moderation controls

---

### Phase 4 Status

Phase 4 is complete.

Dreamer’s Palette now supports:

- Multi-author architecture (RLS-enforced ownership model)
- Markdown-based literary rendering
- Immersive theme-controlled reading environments
- Preview-safe editorial workflow
- Production-safe Supabase integration

## v0.4.2 — Public Reader Identity & Atmospheric UI

- Established global atmospheric background layer
- Introduced Logo system (symbol + wordmark)
- Implemented persistent reader header
- Refined Home, Collections, Collection Detail, Login, and Books pages
- Unified typography system (serif-based hierarchy)
- Introduced accent-button interaction system
- Stabilized visual rhythm and spacing

This version marks the transition from scaffold UI to cohesive literary platform.

---

## v0.4.3 — Mobile Responsiveness
**Date:** 2026-02-26

### Overview

This release is a comprehensive mobile responsiveness pass across every layout, component, and page of Dreamer's Palette. All changes use Tailwind's `md:` breakpoint prefix (768px+) to distinguish mobile from desktop, preserving the existing desktop design exactly while making the platform fully usable on small screens.

A key architectural decision in this release: on mobile, the framed world model disappears — the `max-w-5xl` container loses its rounded corners and width constraint. When a collection has a `backgroundUrl`, that background fills the entire viewport on mobile via `fixed inset-0`, replacing the global atmospheric background and creating a true full-bleed immersive experience.

---

### ✅ Changes by Category

#### Layouts
- **DashboardLayout** — Added `sidebarOpen` state; hamburger button (☰) on mobile triggers a sidebar overlay; main padding reduced to `px-4 md:px-10`, `py-6 md:py-12`
- **ReaderLayout** — Framed world container changed to `max-w-full md:max-w-5xl rounded-none md:rounded-3xl`; collection background and overlay use `fixed inset-0 md:absolute md:inset-0 md:rounded-3xl` for full-bleed mobile; content padding `px-4 md:px-6`; nav top spacing `pt-6 md:pt-12`

#### Components
- **Sidebar** — Accepts `isOpen`/`onClose` props; on desktop renders as permanent sidebar; on mobile renders as `fixed inset-0 z-50` overlay with semi-transparent backdrop, close button (✕), and `overflow-y-auto`; slide-in animated with `transition-transform duration-300`
- **ReaderNavigation** — Nav gap reduced to `gap-3 md:gap-8`; context nav row uses `flex-col gap-3 md:flex-row md:items-center md:justify-between`; sibling nav uses `flex-wrap gap-2 md:gap-4`
- **EditorPanel** — Min-height reduced to `min-h-[300px] md:min-h-[500px]`; editor and preview pane padding `p-4 md:p-6`
- **Modal** — Full-screen on mobile (`w-full h-full`), constrained on desktop (`md:max-w-lg md:rounded-3xl`); added `overflow-y-auto` for scrollable content

#### Pages — Public
- **HomePage** — Hero section `py-12 md:py-24`; login button `top-2 right-2 md:top-0 md:right-0`; featured collection card padding `p-4 md:p-6`
- **CollectionsPage** — Collection card content padding `p-6 md:p-10`; card title `text-xl md:text-2xl`
- **CollectionDetailPage** — Title `text-3xl md:text-5xl`; description `text-base md:text-lg`; article spacing `space-y-12 md:space-y-20 pt-8 md:pt-12`; poems section `pt-8 md:pt-12`
- **LoginPage** — Container `py-12 md:py-24`; card `p-6 md:p-10 space-y-6 md:space-y-10`; title `text-2xl md:text-3xl`
- **BooksPage** — Header `pt-6 md:pt-12`; coming-soon card `px-8 md:px-12 py-12 md:py-16`
- **PoemPage** — Article spacing `space-y-8 md:space-y-12`

#### Pages — Dashboard
- **Collections** — Card header stacks vertically on mobile (`flex-col gap-3 md:flex-row md:items-start md:justify-between`); card padding `p-4 md:p-6`
- **Poems** — Same card header stacking pattern; card padding `p-4 md:p-6`
- **PoemEditorPage** — Page header `flex-col gap-4 md:flex-row md:items-center md:justify-between`; metadata section `max-w-full md:max-w-xl`

#### CSS
- **index.css** — `h1` now `text-3xl md:text-4xl`; `h2` now `text-xl md:text-2xl`

#### Global
- **index.html** — Viewport meta tag `width=device-width, initial-scale=1.0` confirmed present (no change needed)

---

## v0.5.0-sprint.1 — Books Theming Parity & Performance Discipline
**Date:** 2026-03-22
**Phase:** 5 — Literary Expansion & Production Hardening
**Sprint:** 1

### Overview

Phase 5 Sprint 1 begins the transition from feature completeness to platform maturity.

This sprint establishes books as a first-class content type with full theming parity alongside poetry collections, narrows all database queries for production-grade performance, and adds environment variable validation for deployment safety.

---

### ✨ Major Features

#### 1. Book Service Layer (`bookService.js`)

Full CRUD service for books and chapters, following the same patterns as `contentService.js`:

- **Author methods:** `getMyBooks()`, `createBook()`, `updateBook()`, `deleteBook()`, `toggleBookPublish()`
- **Chapter methods:** `getMyChaptersByBook()`, `createChapter()`, `updateChapter()`, `deleteChapter()`, `toggleChapterPublish()`
- **Public methods:** `getPublishedBooks()`, `getBookBySlug()`, `getBookBySlugPreview()`
- **Chapter public methods:** `getPublishedChaptersByBook()`, `getPublishedChapter()`
- All queries use specific column projections (no `select("*")`)
- Unique slug generation with retry logic for books

#### 2. Book Detail Page (`/books/:slug`)

- Displays book cover image, title, and synopsis
- Lists published chapters ordered by chapter number
- Chapter number prefix in listing
- Preview badge for preview chapters
- Follows CollectionDetailPage architectural pattern
- Not-found and loading states

#### 3. Chapter Reader Page (`/books/:slug/chapter/:number`)

- Full Markdown rendering via `react-markdown`
- Chapter number and title header
- Same literary rendering components as PoemPage
- `white-space: pre-wrap` for formatting preservation
- HTML injection disabled (`skipHtml`)

#### 4. Books Theming Parity

Books now support the same theming engine as poetry collections:

- `theme_background_url` — Background image
- `theme_overlay_opacity` — Overlay intensity (default 0.65)
- `accent_color` — Accent color
- `theme_text_mode` — Light/dark contrast mode

`useActiveCollection` hook extended to resolve theming context for:
- `/books/:slug` → fetch book theming
- `/books/:slug/chapter/:number` → inherit book theming
- `/preview/books/:slug` → author preview (no publish filter)

ReaderLayout automatically applies book theming using the same atmospheric engine.

#### 5. Chapter Navigation

`useReaderNavigation` extended with new levels:

- **books-index** — Books listing page
- **book** — Book detail with "← All Books" back link
- **chapter** — Chapter reading with previous/next chapter navigation

ReaderNavigation renders chapter sibling navigation using chapter numbers.

#### 6. Books Listing Page

BooksPage transformed from static "coming soon" to dynamic listing:

- Fetches and displays published books
- Grid layout with cover images
- Synopsis preview with line clamping
- Hover interactions matching collection cards
- Graceful empty state fallback

---

### 🔒 Performance & Data Discipline

#### Narrowed Select Projections

All `select("*")` calls in `contentService.js` replaced with specific columns:

| Function | Before | After |
|---|---|---|
| `getMyCollections()` | `*` | 11 specific columns |
| `getCollectionBySlug()` | `*` | 9 specific columns |
| `getCollectionBySlugPreview()` | `*` | 9 specific columns |
| `getCollectionById()` | `*` | 8 specific columns |
| `getMyPoems()` | `*` | 8 specific columns |
| `getPoemsByCollection()` | `*` | 7 specific columns |
| `getPublishedPoemBySlug()` | `*` | 7 specific columns |

All `bookService.js` queries use specific column projections from the start.

---

### 🛡️ Deployment Hardening

#### Environment Variable Validation

`supabaseClient.js` now validates required environment variables at startup:

- `VITE_SUPABASE_URL` — Required
- `VITE_SUPABASE_ANON_KEY` — Required

Missing variables throw a clear error message before any API calls.

---

### 📄 Database Migration

New columns added to `books` table (see `DatabaseUpdatesHistory.sql`):

```sql
alter table books add column theme_background_url text;
alter table books add column theme_overlay_opacity numeric default 0.65;
alter table books add column accent_color text;
alter table books add column theme_text_mode text default 'light';
```

---

### Status

Phase 5 Sprint 1 complete.

Books now have:
- Full service layer (CRUD + public queries)
- Theming parity with poetry collections
- Public reader pages with Markdown rendering
- Chapter navigation with previous/next
- Performance-optimized database queries

---

## v0.5.0-sprint.2 — AI Prompted Asset Generation (Dashboard)
**Date:** 2026-03-22
**Phase:** 5 — Literary Expansion & Production Hardening
**Sprint:** 2

### Overview

This sprint introduces the first production-facing AI asset generation workflow for authors inside dashboard editing flows.

Authors can now provide prompts to generate:
- collection background imagery, and
- book cover imagery,

then persist those generated images into Supabase Storage and track metadata in `generated_assets`.

### ✅ Implemented

#### 1. AI Asset Service

Added `src/services/aiAssetService.js`:

- `generateCollectionBackground(prompt)`
- `generateBookCover(prompt)`

Flow:
1. validate prompt input,
2. generate image with the configured provider,
3. upload generated binary to Supabase Storage bucket,
4. insert metadata row in `generated_assets`,
5. return public URL + record metadata for editor usage.

All Supabase database queries continue to use explicit column projections.

#### 2. Dashboard Collection Editor Integration

`src/pages/dashboard/Collections.jsx` now includes:

- prompt input for background generation,
- Generate with AI action button,
- inline generation error state,
- automatic assignment of generated URL to `theme_background_url`.

#### 3. Dashboard Book Editor Integration

`src/pages/dashboard/BookEditorPage.jsx` now includes:

- prompt input for cover generation,
- Generate Cover with AI action button,
- inline generation error state,
- generated image preview,
- automatic assignment of generated URL to `cover_image_url`.

### 🤖 AI Generation Provider Used

Current provider in use:

- **Pollinations**
  - Endpoint base: `https://image.pollinations.ai/prompt/...`
  - Used in `aiAssetService.js` for prompt-based image generation

Provider selection is controlled via:
- `VITE_AI_IMAGE_PROVIDER` (defaults to `pollinations` if unset)

Generated asset storage target is controlled via:
- `VITE_GENERATED_ASSETS_BUCKET` (defaults to `backgrounds` if unset)

### ⚠️ Practical Limitations (Current Implementation)

- **Provider variability:** Generated quality and style consistency can vary by prompt and provider-side model behavior.
- **No hard rate-limit enforcement in client:** The current frontend integration does not implement client-side throttling/queueing.
- **Network/provider dependency:** Generation fails when provider endpoint is unavailable or slow.
- **Provider scope locked:** Only `pollinations` is supported right now; unsupported values for `VITE_AI_IMAGE_PROVIDER` throw a clear error.
- **Prompt length capped:** Prompt input is limited to 500 characters to avoid oversized request payloads.
- **Storage policy dependency:** Successful uploads depend on existing Supabase Storage bucket policy compatibility for authenticated authors.

### Status

Phase 5 Sprint 2 AI generation baseline is now active for dashboard author workflows.

---

## v0.5.1 — Mobile Optimization + Visual Contrast
**Date:** 2026-03-23

### Overview
Two-part optimization release focused on mobile usability and text readability across themed backgrounds.

---

### ✅ Part 1 — Mobile & Frame Optimization

#### Fixed Viewport Frame
- Reader and dashboard layouts use `100dvh` with `100vh` fallback via `.h-frame` CSS class
- Content no longer overflows past mobile browser chrome/navigation buttons
- On desktop, the framed world (`max-w-5xl`) contains all content flow with rounded corners

#### Sticky Navigation
- Reader header is now sticky at top of scroll container
- Backdrop-blur glass effect adapts to text mode (dark bg → `bg-neutral-950/60`, light bg → `bg-white/60`)
- Header stays visible as users scroll through poems, chapters, and collections

#### Safe-Area Support
- `viewport-fit=cover` added to viewport meta tag
- `env(safe-area-inset-*)` padding applied to body and bottom content
- `.pb-safe` utility ensures content clears notched/modern mobile browser UI

#### Internal Scrolling
- Framed world uses flex column layout with `overflow-y-auto` on content area
- Eliminates page-level scrolling in favor of contained internal scroll

---

### ✅ Part 2 — Visual Contrast Improvements

#### Dynamic Text Tone
- `textMode` from collection theming now actively drives text color on the content layer
- `text-neutral-100` for dark backgrounds (light text mode)
- `text-neutral-900` for light backgrounds (dark text mode)
- Previously, `textTone` was computed in `ReaderLayout` but never applied

#### Content Text Fixes
- **PoemPage / ChapterPage:** Removed broken `text-neutral-800 dark:text-neutral-800` (identical in both modes, invisible on dark backgrounds) and low-contrast `text-neutral-500`
- **CollectionDetailPage:** Poem titles now inherit high-contrast color instead of hardcoded `text-neutral-500`
- **BookDetailPage:** Chapter titles now inherit high-contrast color instead of hardcoded `text-neutral-500`
- **Blockquotes:** Changed from hardcoded `border-neutral-800` to `border-current/30` for theme-adaptive borders

#### Logo Color Fix
- Removed forced `text-neutral-700!` that was invisible on dark atmospheric backgrounds
- Logo now inherits contextual color from parent, working correctly in both light and dark text modes

#### Text Shadow System
- `.reader-text-shadow-light` — subtle dark shadow for light text on dark/image backgrounds
- `.reader-text-shadow-dark` — subtle light shadow for dark text on light/image backgrounds
- Applied automatically when collection themed backgrounds are active

#### Muted Text System
- `--reader-muted` CSS custom property set at layout level
- Adapts to active text mode: `#a3a3a3` (neutral-400) for light text mode, `#737373` (neutral-500) for dark text mode

---

### Files Changed
- `index.html` — viewport meta
- `src/index.css` — dvh fallback, safe-area, text-shadow, sticky header, muted text
- `src/layouts/ReaderLayout.jsx` — fixed frame, sticky header, dynamic text tone
- `src/layouts/DashboardLayout.jsx` — dvh support
- `src/pages/public/PoemPage.jsx` — contrast fix
- `src/pages/public/ChapterPage.jsx` — contrast fix
- `src/pages/public/CollectionDetailPage.jsx` — contrast fix
- `src/pages/public/BookDetailPage.jsx` — contrast fix
- `src/components/ui/Logo.jsx` — color inheritance fix
