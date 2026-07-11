# AI Asset Integration — Implementation Record

> Workstream C pre-development documentation gate.
> This document captures the exact additions, constraints alignment, and validation plan
> for the AI asset generation feature delivered in Sprint 2.

---

## 1. Exact Additions

### Files / Services

| Layer | File | Purpose |
|-------|------|---------|
| Service | `src/services/aiAssetService.js` | Client-side prompt validation, provider label, Edge Function invocation |
| Edge Function | `supabase/functions/generate-asset/index.ts` | Auth verification, HuggingFace inference call, storage upload, `generated_assets` DB record |
| Shared | `supabase/functions/_shared/cors.ts` | CORS headers for Edge Function responses |

### Schema Touchpoints

| Table | Operation | Notes |
|-------|-----------|-------|
| `generated_assets` | INSERT + SELECT | Stores `author_id`, `type` (background/cover), `prompt`, `image_url`, `created_at` |
| `poetry_collections` | UPDATE (via existing `updateCollection`) | `theme_background_url` set to generated image URL |
| `books` | UPDATE (via existing `updateBook`) | `cover_image_url` set to generated image URL |

### Storage Buckets

| Bucket | Usage |
|--------|-------|
| `backgrounds` (default, overridable via `GENERATED_ASSETS_BUCKET` env var) | AI-generated images uploaded here, same bucket as manual uploads |

### UI Surfaces

| Page | Feature |
|------|---------|
| Dashboard collection editor (`src/pages/dashboard/CollectionEditorPage.jsx`) | Prompt input + "Generate with AI" button for collection backgrounds |
| Dashboard Book editor (`src/pages/dashboard/BookEditorPage.jsx`) | Prompt input + "Generate Cover with AI" button for book covers |

### Endpoints / External APIs

| Endpoint | Purpose |
|----------|---------|
| HuggingFace Inference API (`black-forest-labs/FLUX.1-schnell`) | Image generation from text prompts |
| Supabase Edge Function `generate-asset` | Proxy to avoid browser CORS; handles auth + storage |

### Environment Variables Required

| Variable | Location | Purpose |
|----------|----------|---------|
| `HUGGINGFACE_API_KEY` | Edge Function (Supabase secrets) | Auth for HuggingFace API |
| `SUPABASE_URL` | Edge Function (auto-injected) | Supabase project URL |
| `SUPABASE_SERVICE_ROLE_KEY` | Edge Function (auto-injected) | Admin storage uploads |
| `SUPABASE_ANON_KEY` | Edge Function (auto-injected) | User auth verification |
| `GENERATED_ASSETS_BUCKET` | Edge Function (optional) | Override default storage bucket |
| `VITE_SUPABASE_URL` | Frontend `.env` | Already in use |
| `VITE_SUPABASE_ANON_KEY` | Frontend `.env` | Already in use |

---

## 2. Constraints Alignment

### Query Projection Discipline
- ✅ `generated_assets` insert returns only `id, author_id, type, prompt, image_url, created_at` — no `select("*")`
- ✅ Client-side `aiAssetService.js` only passes through `provider`, `imageUrl`, `asset` from edge function response

### RLS Policies
- ✅ `generated_assets` table uses author-only write (`auth.uid() = author_id` on INSERT)
- ✅ Read access scoped to own records (`auth.uid() = author_id` on SELECT)
- ✅ Edge Function uses service role key only for storage upload — DB insert uses author context via anon key user verification

### Storage Bucket Policies
- ✅ Generated images upload to the `backgrounds` bucket (same policies as manual uploads)
- ✅ File path includes `user.id` prefix for scoping: `{userId}/{timestamp}-{type}.{ext}`
- ✅ `upsert: false` prevents overwriting existing files

### Service Role Key Security
- ✅ Service role key is only used server-side in the Edge Function (`SUPABASE_SERVICE_ROLE_KEY`)
- ✅ No service role key exposure in frontend code
- ✅ Frontend uses `supabase.functions.invoke()` which automatically attaches the user's JWT

### AI Endpoint Guardrails
- ✅ Prompt length capped at 500 characters (validated both client-side and server-side)
- ✅ Type restricted to `"background"` or `"cover"` only
- ✅ Auth required — anonymous users cannot invoke the Edge Function
- ⚠️ No server-side rate limiting implemented yet (relies on HuggingFace tier limits)

### Environment / Security
- ✅ `supabaseClient.js` validates `VITE_SUPABASE_URL` and `VITE_SUPABASE_ANON_KEY` at startup
- ✅ Edge Function checks for `HUGGINGFACE_API_KEY` presence before proceeding

---

## 3. Practical Validation Plan (Non-Unit)

### Dashboard Generation Journey
- [ ] Enter prompt → trigger generation → verify spinner/loading state
- [ ] Verify generated image appears in preview after success
- [ ] Save collection/book → verify `theme_background_url` / `cover_image_url` persists
- [ ] Re-open editor → verify previously generated image URL is loaded

### Role-Based Access Verification
- [ ] Verify anonymous (logged-out) user cannot invoke generate-asset Edge Function
- [ ] Verify authenticated author can generate and save assets
- [ ] Verify `generated_assets` records are scoped to the generating author

### Error / Timeout Handling
- [ ] Submit empty prompt → verify client-side validation error
- [ ] Submit prompt > 500 chars → verify truncation error
- [ ] Simulate HuggingFace API failure → verify error message displayed
- [ ] Verify loading state clears on both success and failure paths
