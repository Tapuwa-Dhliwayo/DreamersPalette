# Dreamer’s Palette — Master Architecture & Project Specification (v1)

## 1. Project Identity

**Name:** Dreamer’s Palette
**Type:** Multi-author literary publishing platform
**Version Scope:** v1 (Online-Only)
**Primary Content:**

* Poetry Collections (with themed backgrounds)
* Individual Poems (Markdown rich formatting)
* Books / Novels (with preview chapters)
* AI-generated visual assets (covers & collection themes)

Core Philosophy:

* Elegant reading experience
* Creator-first tooling
* Flux-inspired minimal UI
* Rich formatting via Markdown
* Scalable multi-author architecture from day one

---

## 2. Final Technology Stack (Locked)

### Frontend

* React (SPA)
* React Router
* TailwindCSS (Design System)
* React Markdown (Rendering)
* Custom Markdown Editor (Split Preview)

### Backend (BaaS)

* Supabase Auth (JWT)
* Supabase PostgreSQL (Database)
* Supabase Storage (Images & AI assets)
* Row Level Security (RLS) for RBAC

### AI Layer

* Image Generation API (covers + themed backgrounds)
* Supabase Storage for generated assets

### Hosting

* Vercel / Netlify / Static hosting (SPA)
* Supabase Cloud Backend

---

## 3. Application Architecture Overview

```txt
Client (React SPA)
│
├── UI Layer (Flux-Inspired Components + Tailwind)
├── Feature Modules (Editor, Reader, Dashboard, AI Tools)
├── Supabase Client SDK
│
└── Supabase Backend
    ├── PostgreSQL Database
    ├── Authentication (Users & Roles)
    ├── Storage Buckets (Images)
    └── Row Level Security Policies
```

Online-only approach:

* No offline caching
* No service workers (v1)
* Real-time API fetch from Supabase

---

## 4. Design System (Flux Principles + Tailwind)

### 4.1 Core Design Philosophy

* Minimal surfaces
* Soft shadows instead of heavy borders
* Generous spacing hierarchy
* Calm, literary aesthetic
* Variant-based reusable components
* Accessibility-first contrast

### 4.2 Design Tokens (Tailwind Standards)

* Radius: `rounded-xl`, `rounded-2xl`
* Shadows: `shadow-sm`, `shadow-md`
* Spacing scale: `p-4`, `p-6`, `p-8`
* Typography focus: readable serif/sans pairing
* Max reading width: `max-w-3xl`

---

## 5. Component Architecture (Custom UI System)

Directory:

```
src/components/ui/
```

### Core UI Components

* Button (primary, subtle, ghost)
* Card (content containers)
* Modal (AI generator, forms)
* Input
* Textarea
* Tabs
* Dropdown
* Badge
* EditorPanel (Markdown split view)

All components:

* Tailwind-based
* Variant-driven props
* Consistent padding & radii
* Reusable across dashboard + reader UI

---

## 6. Feature Modules

### 6.1 Public Reader Module

* Poetry collection browsing
* Themed collection pages
* Poem reading (Markdown rendered)
* Book browsing
* Chapter preview reading
* Author profiles (future-ready)

### 6.2 Author Dashboard (Essential CMS)

* Create/Edit Poetry Collections
* Theme background management
* Markdown Poem Editor + Preview
* Book & Chapter Management
* AI Image Generator Panel
* Publish/Unpublish controls

### 6.3 AI Asset Generator Module

* Generate book covers from synopsis
* Generate collection backgrounds from title/description
* Store outputs in Supabase Storage
* Track prompts in database

---

## 7. Database Schema (Supabase PostgreSQL)

### 7.1 profiles (Extends Auth Users)

```sql
profiles
- id (uuid, PK, references auth.users.id)
- display_name (text)
- bio (text)
- avatar_url (text)
- role (text) -- admin | author
- created_at (timestamp)
```

---

### 7.2 poetry_collections (Themed Worlds)

```sql
poetry_collections
- id (uuid, PK)
- author_id (uuid, FK -> profiles.id)
- title (text)
- slug (text, unique)
- description (text)
- theme_background_url (text)
- theme_overlay_opacity (numeric, nullable)
- accent_color (text, nullable)
- is_published (boolean)
- created_at (timestamp)
- updated_at (timestamp)
```

---

### 7.3 poems (Markdown Content)

```sql
poems
- id (uuid, PK)
- collection_id (uuid, FK -> poetry_collections.id)
- author_id (uuid, FK -> profiles.id)
- title (text)
- slug (text, unique)
- content_md (text)
- excerpt (text, nullable)
- is_published (boolean)
- created_at (timestamp)
- updated_at (timestamp)
```

---

### 7.4 books (Novels / Story Collections)

```sql
books
- id (uuid, PK)
- author_id (uuid, FK -> profiles.id)
- title (text)
- slug (text, unique)
- synopsis (text)
- cover_image_url (text)
- ai_prompt (text, nullable)
- is_published (boolean)
- created_at (timestamp)
- updated_at (timestamp)
```

---

### 7.5 chapters

```sql
chapters
- id (uuid, PK)
- book_id (uuid, FK -> books.id)
- author_id (uuid, FK -> profiles.id)
- title (text)
- chapter_number (integer)
- content_md (text)
- is_preview (boolean)
- is_published (boolean)
- created_at (timestamp)
- updated_at (timestamp)
```

---

### 7.6 generated_assets (AI Tracking)

```sql
generated_assets
- id (uuid, PK)
- author_id (uuid)
- type (text) -- cover | background
- prompt (text)
- image_url (text)
- created_at (timestamp)
```

---

## 8. Row Level Security (RBAC Strategy)

### Public Access

* SELECT only where `is_published = true`

### Author Permissions

Authors can:

* Insert their own content
* Update their own content
* Delete their own content

Policy Concept:

```sql
auth.uid() = author_id
```

### Admin (Future)

* Full access override via role check

---

## 9. Markdown Content System (Rich Formatting)

### Storage Strategy

All literary content stored as:

* `content_md` (TEXT, Markdown)

### Rendering Requirements

* Preserve line breaks (critical for poetry)
* `white-space: pre-wrap`
* Custom typography styles
* Markdown parser (React Markdown)

Supported Formatting:

* Headings (chapters)
* Italics & bold
* Line spacing (poetry stanzas)
* Blockquotes (stylistic verse)

---

## 10. Themed Poetry Collection Engine (Signature Feature)

Each collection includes:

* AI-generated background image
* Accent color
* Overlay opacity for readability

Rendering Logic:

* Load theme from database
* Apply dynamic background + overlay
* Maintain text readability layer
* Consistent reading container width

---

## 11. AI Image Generation Pipeline

### Use Cases

* Book cover from synopsis
* Collection background from title/mood

### Flow

1. Author clicks “Generate Image”
2. System constructs prompt
3. Send to AI image API
4. Upload result to Supabase Storage bucket
5. Save URL + prompt in `generated_assets`
6. Attach image to book/collection record

Prompt Template Example:

```
Dreamlike artistic illustration inspired by:
Title: {title}
Description: {synopsis or collection description}
Style: ethereal, literary, painterly, high detail
```

---

## 12. Supabase Storage Structure

Buckets:

```
covers/
backgrounds/
avatars/
generated/
```

Access:

* Public read for published assets
* Authenticated upload for authors

---

## 13. Frontend Folder Structure (Production)

```
src/
├── app/
├── components/
│   ├── ui/
│   ├── reader/
│   ├── editor/
│   └── dashboard/
├── pages/
│   ├── public/
│   └── dashboard/
├── layouts/
├── hooks/
├── services/
│   ├── supabaseClient.js
│   ├── aiService.js
│   └── contentService.js
├── utils/
└── styles/
```

---

## 14. Routing Structure (SPA)

```
/
/collections
/collections/:slug
/poems/:slug
/books
/books/:slug
/books/:slug/chapter/:number
/login
/dashboard
/dashboard/collections
/dashboard/poems
/dashboard/books
/dashboard/chapters
```

---

## 15. Performance Strategy (Online-Only v1)

* No PWA caching
* Direct Supabase queries
* Lazy load chapters & images
* Optimized image storage (compressed covers/backgrounds)
* Pagination for large collections

---

## 16. Security Best Practices

* Enforce RLS on all tables
* Never expose Supabase service role key
* Slug uniqueness constraints
* Input sanitization for Markdown
* Rate limiting AI generation endpoints
* Author-only write access policies

---

## 17. MVP Development Order (Recommended)

1. Supabase project + schema setup
2. Auth + profiles system
3. UI design system (Tailwind + Flux principles)
4. Markdown editor + preview module
5. Poetry collections + theming engine
6. Books + chapters system
7. AI image generator integration
8. Author dashboard CMS
9. Public reader polish & typography refinement

---

## 18. Scalability (Multi-Author Ready)

* Author-based ownership via `author_id`
* Role-based access (admin/author)
* Future onboarding system supported without schema changes
* Horizontal content scaling (text-first platform)

---

## 19. Future Expansion (Post v1)

* Reader accounts & bookmarks
* Comments on poems
* Audio narration uploads
* EPUB export per book
* Featured collections
* Community author discovery
* Advanced typography themes

---

## 20. Vision Summary

Dreamer’s Palette is a premium, AI-enhanced literary platform built for immersive storytelling, poetic expression, and multi-author publishing — powered by Markdown, themed aesthetics, and a calm Flux-inspired design system.
