# Phase 5 Execution Plan

## Sprint 1 — Books + Dashboard Completion

- [x] Books service layer (author + public queries)
- [x] Books theming parity (matching poetry collection theme fields)
- [x] Public book and chapter reader routes/pages
- [x] Chapter navigation context integration
- [x] Query projection hardening (no `select("*")`)
- [x] Environment variable validation
- [x] Dashboard: Books management page (`/dashboard/books`) with create/edit, publish/unpublish, delete
- [x] Dashboard: Chapters management page (`/dashboard/chapters`) with book filter, create/edit, publish/unpublish, delete
- [x] Dashboard: Book editor (`/dashboard/books/new`, `/dashboard/books/:id/edit`)
- [x] Dashboard: Chapter editor (`/dashboard/chapters/new`, `/dashboard/chapters/:id/edit`)
- [x] Dashboard: Chapter markdown live preview in editor

## Sprint 2+

### Sprint 2 Kickoff

- [x] Align Sprint 2+ scope to `PHASE_5_PLAN.md` pillars
- [x] Define implementation order and practical validation strategy

### Workstream A — Pagination (Collections, Poems, Books)

- [ ] Add page + page-size controls for public collection listings
- [ ] Add page + page-size controls for poems within a collection
- [ ] Add page + page-size controls for books in dashboard/public surfaces where needed
- [ ] Preserve current sort/order semantics while paginating
- [ ] Add loading/empty/end-of-results states for paginated views

### Workstream B — Image Upload Optimization

- [ ] Enforce client-side file constraints (size/type/dimensions) before upload
- [ ] Add image compression/resizing flow for oversized assets
- [ ] Keep metadata and storage paths consistent with existing content models
- [ ] Add clear author feedback for rejected/optimized uploads

### Workstream C — AI Asset Integration

- [ ] **Pre-development approval gate (required):** share AI implementation plan and wait for explicit go-ahead before coding
- [ ] Share exact additions list before coding (files/services/endpoints/schema-touchpoints/UI surfaces)
- [ ] Share constraints alignment check before coding (RLS, storage policies, query projection discipline, env/security rules)
- [x] Add prompt capture inputs for collection background generation
- [x] Add prompt capture inputs for book cover generation
- [x] Save generated metadata in `generated_assets`
- [x] Upload generated files to Supabase Storage and persist URLs
- [x] Track prompt history + generation timestamps for reproducibility

#### AI Pre-Development Notice Template (send before implementation)

- Planned additions (exact):
  - Components/pages to change
  - Services/functions to add
  - Any schema/policy/storage updates
  - Environment variables required
- Constraints alignment:
  - No `select("*")`; use minimal column projections
  - RLS maintained (`generated_assets` author-only write/read as intended)
  - Storage bucket policy compatibility for generated assets
  - No service role key exposure in frontend code
  - AI endpoint guardrails/rate limiting plan
- Practical validation plan (non-unit):
  - Dashboard generation journey (prompt → image → saved URL)
  - Role-based access verification (author vs anon)
  - Error/timeout handling checks

### Workstream D — Editorial Refinement

- [x] Add smooth theme/background transitions between reader contexts
- [ ] Add subtle ReaderNavigation micro-interactions
- [ ] Add optional accent underline style treatment
- [ ] Refine edit → preview flow latency and visual continuity

### Workstream E — Deployment Hardening

- [ ] Complete RLS policy-by-policy audit for all Phase 5 tables
- [ ] Complete storage bucket policy audit for user/public/generated assets
- [ ] Re-verify environment variable validation paths in production mode
- [ ] Finalize v1.0.0 release checklist and acceptance gate

## Practical Test Coverage Plan (No Unit Tests)

### 1) Manual End-to-End Reader Journeys

- [ ] Verify collection list pagination across first/middle/last pages
- [ ] Verify poem and book navigation continuity after pagination changes
- [ ] Verify chapter navigation prev/next behavior after editorial/UI changes
- [ ] Capture before/after screenshots for all changed reader surfaces

### 2) Dashboard Authoring Workflows

- [ ] Upload small/large/wrong-format images and verify guardrails + feedback
- [ ] Validate AI asset generation from dashboard inputs through saved output URLs
- [ ] Verify generated assets are visible in subsequent edit sessions
- [ ] Confirm edit → preview workflows remain stable after UI refinements

### 3) Data & Security Verification (Supabase)

- [ ] Validate paginated queries return minimal projected columns only
- [ ] Validate `generated_assets` insert/read paths under intended roles
- [ ] Execute RLS policy checks for anon/authenticated/author access scenarios
- [ ] Validate storage bucket read/write constraints for all asset classes

### 4) Performance & Release Readiness Checks

- [ ] Measure payload sizes before/after pagination + upload optimizations
- [ ] Measure interaction responsiveness on themed transitions/navigation actions
- [ ] Run `npm run build` for release candidate verification
- [ ] Run production smoke test pass before tagging v1.0.0
