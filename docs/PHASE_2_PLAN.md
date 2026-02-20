# Dreamer’s Palette — Phase 2 Plan
## Content Backbone + Dashboard Shell

---

## Overview

Phase 2 transitions Dreamer’s Palette from infrastructure stabilization (v0.1.0) into a functioning author content system.

This phase establishes:

- A permanent dashboard shell (sidebar + profile context)
- Collections CRUD backbone
- Public collections listing
- Slug routing validation
- Publishing workflow enforcement

Version Target: **v0.2.0**

---

# Pillar A — Dashboard Structural Shell

## Objective

Create a stable, scalable dashboard layout that will host all current and future author features.

This must be completed before heavy CRUD expansion.

---

## 1. Sidebar Architecture

### File: /components/dashboard/Sidebar.jsx

### Responsibilities:
- Brand header
- Navigation links:
  - Dashboard
  - Collections
  - Poems
  - Books
  - Chapters
- Active route highlighting
- Profile section
- Logout action

### Rules:
- No inline Supabase queries
- Use `routes.js` for links
- Pure structural component
- No business logic inside layout

---

## 2. Profile Section (Sidebar Bottom)

### Data Source:
`profiles` table

### Required Display:
- display_name
- role
- avatar (placeholder acceptable)
- logout button

### Service Layer:
Create: /services/profileService.js

With:
- `getMyProfile()`

Profile queries must NOT live inside authService.

---

## 3. Dashboard Layout Refactor

### Target Layout Structure
DashboardLayout
├── Sidebar (fixed column)
└── Main Content Area
└── <Outlet />


### Layout Goals:
- Stable 2-column structure
- Clean spacing
- Calm professional aesthetic
- Responsive collapse-ready (future)

---

# Pillar B — Collections Backbone

Collections are the root entity of the literary system.

Everything depends on this structure.

---

## 1. Content Service Layer

Create: /services/contentService.js


### Required Methods:

- `getMyCollections()`
- `getPublishedCollections()`
- `getCollectionBySlug(slug)`
- `createCollection(data)`
- `updateCollection(id, data)`
- `deleteCollection(id)`
- `togglePublish(id, boolean)`

### Rules:
- Respect RLS (auth.uid() ownership)
- Never expose service role key
- Public queries must filter `is_published = true`
- No direct Supabase queries inside components

---

## 2. Slug Strategy

Create: /utils/slugify.js


Slug Requirements:
- Lowercase
- Hyphen-separated
- No special characters
- Enforce uniqueness
- Generated on create
- Editable only if necessary

Slug integrity is critical for routing stability.

---

## 3. Dashboard Collections UI

### File: /pages/dashboard/Collections.jsx


### Required Features:
- List author collections
- Create new collection (modal or inline form)
- Edit collection
- Delete collection
- Toggle publish state
- Clear empty state message

UI must use:
- Card
- Button
- Modal
- Input
- Textarea

No theming controls yet.

---

## 4. Public Collections Pages

### Files:

/pages/public/CollectionsPage.jsx
/pages/public/CollectionDetailPage.jsx


### CollectionsPage:
- Fetch only published collections
- Grid display
- Title + description
- Slug navigation

### CollectionDetailPage:
- Fetch by slug
- Display title + description
- Prepare space for future poem rendering

No theming engine yet.

---

# Publishing Workflow

Collections must include: is_published (boolean)


Dashboard:
- Authors toggle publish

Public:
- Only render `is_published = true`

This validates RLS and frontend filtering discipline.

---

# What Phase 2 Does NOT Include

- Poems CRUD
- Markdown editor integration
- React Markdown rendering
- Theming engine
- AI image generation
- Role-based admin logic
- Pagination
- Realtime updates
- Performance optimizations

Focus is structural integrity.

---

# Implementation Order

1. Build Sidebar component (static version)
2. Refactor DashboardLayout into 2-column layout
3. Implement profileService
4. Wire profile into Sidebar
5. Build contentService
6. Implement Dashboard Collections CRUD
7. Implement Public Collections listing
8. Validate slug routing

Structure before feature depth.

---

# Success Criteria for v0.2.0

✔ Sidebar fully operational  
✔ Profile visible in dashboard  
✔ Logout stable  
✔ Collections CRUD functional  
✔ Slug routing working  
✔ Publish toggle working  
✔ Public collections rendering  
✔ RLS properly enforced  

If all above are complete, Phase 2 is considered successful.

---

# Strategic Importance

Phase 2 transforms Dreamer’s Palette from infrastructure into a functioning literary CMS foundation.

Before Phase 2:
- Auth system
- Routing shell
- Layout structure

After Phase 2:
- Author content system
- Public-readable collections
- Publish workflow
- Slug-based literary routing

This is the first true vertical slice of the platform.

---

End of Phase 2 Plan

