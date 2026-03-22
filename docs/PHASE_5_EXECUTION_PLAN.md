# Phase 5 — Full Execution Plan

> **Status:** ✅ Approved — All decisions finalized
> **Date:** February 27, 2026
> **Scope:** Literary Expansion & Production Hardening
> **Pillars:** Books Theming Parity · Performance Discipline · AI Integration · Editorial Polish · Deployment Hardening

---

## Overview

Phase 5 has five pillars spanning Books theming parity, performance/data discipline, AI asset generation, editorial polish, and deployment hardening. The plan is organized into **4 sprints** with **25 concrete tasks**, leveraging existing patterns (`useCollectionTheme`, `useActiveCollection`, `contentService`, `storageService`, `ReaderLayout`) and building new capabilities atop them.

**Critical dependency chain:**
```
Books CRUD Backbone → Books Theming → AI Integration → Polish → Hardening
```

---

## Sprint 1 — Books & Chapters Content Backbone (Prerequisite)

> Currently `Books.jsx`, `Chapters.jsx`, `BookDetailPage.jsx`, and `ChapterPage.jsx` are empty stubs. The `books` and `chapters` tables exist in the DB but `contentService.js` has zero book/chapter methods. This sprint must come first.

| # | Task | Files to Modify/Create | Complexity | Notes |
|---|------|------------------------|:----------:|-------|
| 1.1 | **Add book theme columns to `books` table** — Add `theme_background_url`, `theme_overlay_opacity`, `accent_color`, `theme_text_mode` columns (mirroring `poetry_collections`). | `docs/DatabaseUpdatesHistory.sql` (document the ALTER), Supabase SQL editor | **S** | No schema exists for book-level theming yet. The current `books` table only has `cover_image_url` and `ai_prompt`. This migration is required before Pillar A. |
| 1.2 | **Add Books & Chapters service methods** to `contentService.js` — Author CRUD: `getMyBooks()`, `createBook()`, `updateBook()`, `deleteBook()`, `toggleBookPublish()`, `getMyChapters()`, `getChaptersByBook()`, `createChapter()`, `updateChapter()`, `deleteChapter()`, `toggleChapterPublish()`. Public queries: `getPublishedBooks()`, `getBookBySlug()`, `getPublishedChaptersByBook()`, `getPublishedChapter()`. Follow existing `insertWithUniqueSlug` pattern for books. Chapters use `book_id + chapter_number` composite, not slugs. | `src/services/contentService.js` | **L** | Mirror the existing collections/poems patterns exactly. Use narrow `select()` projections from the start (Pillar B discipline). |
| 1.3 | **Build Dashboard Books page** — Full CRUD with modal create/edit (mirroring `Collections.jsx` pattern), including theme fields (background upload, overlay opacity, accent color, text mode), cover image upload, synopsis textarea, publish/unpublish toggle. | `src/pages/dashboard/Books.jsx` | **L** | Reuse the same modal theme controls pattern from Collections.jsx. Needs `uploadCoverImage()` in storageService. |
| 1.4 | **Extend storageService** — Add `uploadCoverImage(file, userId)` (bucket: `covers`) and `uploadGeneratedAsset(file, userId)` (bucket: `generated`). | `src/services/storageService.js` | **S** | Follow existing `uploadBackgroundImage` pattern. |
| 1.5 | **Build Dashboard Chapters page** — Route-based chapter editor (mirroring `PoemEditorPage.jsx` pattern), using `EditorPanel` for markdown, book selection, chapter number, `is_preview` toggle. | `src/pages/dashboard/Chapters.jsx`, **NEW:** `src/pages/dashboard/ChapterEditorPage.jsx`, `src/app/router.jsx`, `src/app/routes.js` | **L** | Add routes: `/dashboard/books/:bookId/chapters`, `/dashboard/books/:bookId/chapters/new`, `/dashboard/books/:bookId/chapters/:id/edit` |
| 1.6 | **Build public BookDetailPage** — Fetch book by slug, display synopsis, cover image, list preview chapters with links. | `src/pages/public/BookDetailPage.jsx` | **M** | |
| 1.7 | **Build public ChapterPage** — Fetch chapter by book slug + chapter number, render markdown content with `ReactMarkdown`. | `src/pages/public/ChapterPage.jsx` | **M** | |
| 1.8 | **Update BooksPage** — Replace "coming soon" placeholder with actual published books listing. | `src/pages/public/BooksPage.jsx` | **S** | |

### Sprint 1 Internal Dependencies
```
1.1 (DB migration) → 1.2 (service methods) → 1.3, 1.5, 1.6, 1.7, 1.8
1.4 (storageService) → 1.3 (Books dashboard upload)
```

---

## Sprint 2 — Books Theming Parity + Performance (Pillars A & B)

| # | Task | Files to Modify/Create | Complexity | Notes |
|---|------|------------------------|:----------:|-------|
| 2.1 | **Create `useActiveBook` hook** — Mirrors `useActiveCollection.js`. Detects `/books/:slug` and `/books/:slug/chapter/:number` routes via `useMatch()`. Fetches book with theme data. Returns `book` object or `null`. | **NEW:** `src/hooks/useActiveBook.js` | **M** | Uses `getBookBySlug()` from contentService. For chapter routes, resolve book from the slug param. |
| 2.2 | **Create `useBookTheme` hook** — Mirrors `useCollectionTheme.js`. Consumes `useActiveBook()`, extracts `backgroundUrl`, `overlayColor`, `textMode`, `accentColor`. | **NEW:** `src/hooks/useBookTheme.js` | **S** | Identical logic to `useCollectionTheme`, just sourcing from book instead of collection. |
| 2.3 | **Refactor `ReaderLayout` for unified theme resolution** — Instead of only consuming `useCollectionTheme`, create a unified `useReaderTheme` hook that delegates to `useCollectionTheme` or `useBookTheme` based on current route. ReaderLayout consumes this single hook. | `src/layouts/ReaderLayout.jsx`, **NEW:** `src/hooks/useReaderTheme.js` | **M** | **Key architectural decision:** A unified hook avoids duplicating the background/overlay rendering in ReaderLayout. The hook checks book routes first, then collection routes, then returns defaults. |
| 2.4 | **Extend `useReaderNavigation` for book routes** — Add `level: "book"` and `level: "chapter"` states. Chapter level gets previous/next chapter navigation. Book level gets "← All Books" back-link. | `src/hooks/useReaderNavigation.js` | **M** | Add `useMatch("/books/:slug")` and `useMatch("/books/:slug/chapter/:number")`. Fetch `getPublishedChaptersByBook()` for sibling nav. |
| 2.5 | **Update `ReaderNavigation` for book contexts** — Render back-links for book/chapter levels, chapter prev/next navigation. | `src/components/reader/ReaderNavigation.jsx` | **M** | Follow existing poem-level nav pattern. |
| 2.6 | **Add pagination to public queries** — Add `page` and `pageSize` params to `getPublishedCollections()`, `getPublishedBooks()`, `getPublishedPoemsByCollection()`, `getPublishedChaptersByBook()`. Return `{ data, count }`. Use Supabase `.range()` method. | `src/services/contentService.js` | **M** | Also add pagination to dashboard queries: `getMyCollections()`, `getMyPoems()`, `getMyBooks()`. |
| 2.7 | **Build pagination UI component** — Reusable `Pagination` component with page numbers and prev/next buttons. | **NEW:** `src/components/ui/Pagination.jsx` | **S** | |
| 2.8 | **Integrate pagination into listing pages** — Wire pagination into `CollectionsPage.jsx`, `BooksPage.jsx`, `Poems.jsx` (dashboard), `Collections.jsx` (dashboard), dashboard Books page. | Multiple page files | **M** | |
| 2.9 | **Narrow all `select("*")` projections** — Audit every Supabase query in `contentService.js` and replace `select("*")` with explicit column lists. | `src/services/contentService.js` | **S** | Affected: `getMyCollections()`, `getMyPoems()`, `getCollectionBySlug()`, `getCollectionById()`, `getCollectionBySlugPreview()`, `getPublishedPoemBySlug()` |
| 2.10 | **Add image size validation to uploads** — Validate file size (max 2MB for backgrounds, 1MB for covers) and dimensions before uploading. Show user-facing error in dashboard upload flows. | `src/services/storageService.js`, `src/pages/dashboard/Collections.jsx`, dashboard `Books.jsx` | **S** | |

### Sprint 2 Internal Dependencies
```
2.1 → 2.2 → 2.3 (theme hook chain)
2.6 → 2.7 → 2.8 (pagination chain)
2.9 and 2.10 are independent
```

---

## Sprint 3 — AI Asset Integration + Editorial Polish (Pillars C & D)

| # | Task | Files to Modify/Create | Complexity | Notes |
|---|------|------------------------|:----------:|-------|
| 3.1 | **Create `aiService.js`** — Encapsulates Pollinations.ai image generation. Exports `generateCollectionBackground(title, description)` and `generateBookCover(title, synopsis)`. Constructs styled prompts, calls `https://image.pollinations.ai/prompt/{encodedPrompt}` with query params (`width`, `height`, `seed`, `nologo=true`). Fetches the returned image as a blob for upload to Supabase Storage. | **NEW:** `src/services/aiService.js` | **M** | ✅ **Decided:** Pollinations.ai — completely free, no API key, no sign-up, no server proxy needed. URL-based API returns images directly. Call from client. Prompt engineering is key: prefix prompts with style keywords like "dreamy watercolor atmospheric" for backgrounds and "book cover illustration literary" for covers. |
| 3.2 | **Create `generated_assets` service methods** — Add `createGeneratedAsset(payload)`, `getMyGeneratedAssets()` to `contentService.js`. Track `type`, `prompt`, `image_url`, `author_id`. | `src/services/contentService.js` | **S** | The `generated_assets` table already exists in the DB schema. |
| 3.3 | **Build AI Generator Modal component** — Reusable modal with: editable prompt preview (auto-generated from title/description), generate button, loading spinner, result image preview, "Regenerate" button (new random seed), and "Use this image" action that fetches the blob, uploads to Supabase Storage `generated` bucket, and returns the public URL. | **NEW:** `src/components/dashboard/AIGeneratorModal.jsx` | **L** | Uses `aiService.js` (Pollinations.ai URL builder), `storageService.js` for upload, and `contentService.js` to log prompt history to `generated_assets`. No API key handling needed. |
| 3.4 | **Integrate AI generation into Collections and Books dashboards** — Add "Generate Background" button in collection edit modal, "Generate Cover" button in book edit modal. Both open `AIGeneratorModal` with appropriate context. On success, populate the form's image URL field. | `src/pages/dashboard/Collections.jsx`, dashboard `Books.jsx` | **M** | |
| 3.5 | **Add background fade transition between collections** — In `ReaderLayout.jsx`, implement crossfade by rendering two background layers and toggling opacity. Track `previousBackgroundUrl` in the unified theme hook, fade old out while new fades in. | `src/layouts/ReaderLayout.jsx`, `src/hooks/useReaderTheme.js` | **M** | The current `transition-opacity duration-500` on the background div is a start, but true crossfade needs a dual-layer approach. |
| 3.6 | **Add micro-interactions to ReaderNavigation** — Subtle hover scale on accent-buttons, smooth entry animation for context nav (fade-in-up), and gentle transition when sibling nav links change. | `src/components/reader/ReaderNavigation.jsx`, `src/index.css` | **S** | CSS-only approach using Tailwind's `transition`, `hover:scale-[1.02]`, and `animate-` utilities. Keep it restrained per Theming Philosophy. |
| 3.7 | **Optional accent underline style system** — Add a CSS class `.accent-underline` that renders a thin bottom border using `var(--accent-color)` with configurable width. Apply optionally to collection/book titles in reader pages. | `src/index.css`, `src/pages/public/CollectionDetailPage.jsx`, `src/pages/public/BookDetailPage.jsx` | **S** | |
| 3.8 | **Improve edit → preview workflow** — Add "Preview" button in `PoemEditorPage.jsx` that opens the poem's public reader page in a new tab (if published) or the collection's preview route. Add similar preview link in book/chapter editor. | `src/pages/dashboard/PoemEditorPage.jsx`, `ChapterEditorPage.jsx` | **S** | |

### Sprint 3 Internal Dependencies
```
3.1 → 3.3 → 3.4 (AI chain)
3.2 independent (service methods)
3.5-3.8 are independent of each other and the AI chain
```

---

## Sprint 4 — Deployment Hardening (Pillar E)

| # | Task | Files to Modify/Create | Complexity | Notes |
|---|------|------------------------|:----------:|-------|
| 4.1 | **Full RLS audit** — Review all 6 tables (`profiles`, `poetry_collections`, `poems`, `books`, `chapters`, `generated_assets`). Verify: (a) public SELECT requires `is_published = true`, (b) author policies use `auth.uid() = author_id`, (c) no policy allows unauthenticated writes, (d) author-owned read policies exist for dashboard queries. | `docs/IntialDatabaseCreationQuery.sql`, Supabase dashboard, **NEW:** `docs/RLS_AUDIT.md` | **M** | The current policies look correct but books/chapters need verification since they've never been actively tested with real data. |
| 4.2 | **Storage bucket policy audit** — Verify all 4 buckets (`covers`, `backgrounds`, `avatars`, `generated`) exist, have public read policies, and authenticated upload restricted to `userId/` path prefix per existing security query. Ensure delete/update policies use `auth.uid()::text = (storage.foldername(name))[1]`. | `docs/StorageBucketsSecurityQuery.sql`, Supabase dashboard | **S** | |
| 4.3 | **Add environment variable validation** — Create a startup validation utility that checks `VITE_SUPABASE_URL` and `VITE_SUPABASE_ANON_KEY` are present. Log clear error in console if missing. Call from `main.jsx`. | **NEW:** `src/utils/validateEnv.js`, `src/main.jsx` | **S** | No AI API key needed — Pollinations.ai requires no authentication. |
| 4.4 | **Production Supabase configuration** — Document required Supabase project settings: disable public signups, email confirmations, rate limiting, CORS allowed origins, API key rotation plan. | **NEW:** `docs/PRODUCTION_CONFIG.md` | **S** | |
| 4.5 | **Define v1.0.0 milestone criteria** — Document the checklist: all pillars complete, RLS audit passed, storage audit passed, all pages functional, mobile responsive, pagination active, no `select("*")` remaining. | `docs/VERSION_HISTORY.md` | **S** | |

### Sprint 4 Internal Dependencies
```
All tasks are independent of each other.
4.1 and 4.2 should be done first as they may surface issues.
```

---

## Cross-Sprint Dependency Graph

```
┌──────────────────────────┐
│  Sprint 1                │
│  Books & Chapters        │
│  Content Backbone        │
│  (Tasks 1.1 – 1.8)      │
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────┐
│  Sprint 2                │
│  Books Theming Parity    │
│  + Performance           │
│  (Tasks 2.1 – 2.10)     │
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────┐
│  Sprint 3                │
│  AI Integration          │
│  + Editorial Polish      │
│  (Tasks 3.1 – 3.8)      │
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────┐
│  Sprint 4                │
│  Deployment Hardening    │
│  (Tasks 4.1 – 4.5)      │
└──────────────────────────┘
```

---

## New Dependencies Required

| Dependency | Type | Purpose |
|-----------|------|---------|
| Pollinations.ai | External API (free, no key) | AI image generation via URL-based API. No npm package, no API key, no server proxy needed. Called directly from the browser. |
| `generated` storage bucket | Supabase Storage | Storage for AI-generated images (may already exist) |
| `theme_background_url`, `theme_overlay_opacity`, `accent_color`, `theme_text_mode` columns on `books` table | Supabase DB migration | New columns enabling book theming parity with collections |

---

## New Files Summary

| File | Sprint | Purpose |
|------|--------|---------|
| `src/pages/dashboard/ChapterEditorPage.jsx` | 1 | Chapter create/edit with EditorPanel |
| `src/hooks/useActiveBook.js` | 2 | Active book detection from URL (mirrors useActiveCollection) |
| `src/hooks/useBookTheme.js` | 2 | Book theme extraction (mirrors useCollectionTheme) |
| `src/hooks/useReaderTheme.js` | 2 | Unified theme hook for ReaderLayout |
| `src/components/ui/Pagination.jsx` | 2 | Reusable pagination component |
| `src/services/aiService.js` | 3 | AI image generation API service |
| `src/components/dashboard/AIGeneratorModal.jsx` | 3 | Reusable AI generation modal |
| `src/utils/validateEnv.js` | 4 | Environment variable validation |
| `docs/RLS_AUDIT.md` | 4 | RLS audit documentation |
| `docs/PRODUCTION_CONFIG.md` | 4 | Production configuration guide |

---

## Decisions — All Resolved ✅

### 1. ~~AI API Architecture~~ ✅ DECIDED
**Chosen:** Pollinations.ai — direct client calls, no server proxy.

- Completely free, no sign-up, no API key required
- URL-based API: `https://image.pollinations.ai/prompt/{encodedPrompt}?width=1920&height=1080&nologo=true`
- Fetch response as blob → upload to Supabase Storage `generated` bucket
- No npm package needed, no `VITE_AI_API_KEY`, no Vercel serverless function
- Can be swapped to a paid provider later by updating `aiService.js` only

### 2. ~~Unified Theme Hook vs. Dual Hooks~~ ✅ DECIDED
**Chosen:** Option A — Unified `useReaderTheme` hook.

- Single hook consumed by `ReaderLayout`, delegates to `useCollectionTheme` or `useBookTheme` internally
- Cleaner API, single source of truth for the layout layer
- Easier to extend for future content types (e.g., short stories, essays)

### 3. ~~Chapter Editor Routing~~ ✅ DECIDED
**Chosen:** Option C — Nested as primary, flat as overview.

- Primary: `/dashboard/books/:bookId/chapters` — context-aware chapter management within a book
- Overview: `/dashboard/chapters` — flat listing of all chapters across all books
- Routes: `/dashboard/books/:bookId/chapters/new`, `/dashboard/books/:bookId/chapters/:id/edit`

---

## Complexity Summary

| Complexity | Count | Tasks |
|:----------:|:-----:|-------|
| **S** (Small) | 12 | 1.1, 1.4, 1.8, 2.2, 2.7, 2.9, 2.10, 3.2, 3.6, 3.7, 3.8, 4.2, 4.3, 4.4, 4.5 |
| **M** (Medium) | 10 | 1.6, 1.7, 2.1, 2.3, 2.4, 2.5, 2.6, 2.8, 3.1, 3.4, 3.5, 4.1 |
| **L** (Large) | 3 | 1.2, 1.3, 1.5, 3.3 |

---

## v1.0.0 Milestone Checklist (Exit Criteria)

- [ ] All books CRUD functional (dashboard + public)
- [ ] Books theming parity with collections
- [ ] Chapter navigation with prev/next
- [ ] ReaderLayout unified theme system
- [ ] Pagination on all listing pages
- [ ] No `select("*")` in any Supabase query
- [ ] Image upload size validation
- [ ] AI asset generation functional
- [ ] Background crossfade transitions
- [ ] Micro-interactions on ReaderNavigation
- [ ] RLS audit passed — documented
- [ ] Storage bucket audit passed — documented
- [ ] Environment variable validation in place
- [ ] Production Supabase config documented
- [ ] All pages mobile responsive
- [ ] VERSION_HISTORY.md updated with v1.0.0 entry







