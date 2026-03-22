# Version Notes (Current Sprint)

## v0.5.0-sprint.2 — AI Prompted Asset Generation

### AI generation provider currently used

- **HuggingFace Inference API** — model: `black-forest-labs/FLUX.1-schnell`
- Routed through a **Supabase Edge Function** (`generate-asset`) to avoid browser CORS/Turnstile issues.

### Current practical limitations

- Output quality/style consistency varies by prompt and model behavior.
- No client-side throttling/queueing is implemented yet.
- Generation depends on HuggingFace API availability and latency.
- Prompt length is capped at 500 characters.
- Successful persistence depends on existing Supabase Storage policies.
- HuggingFace free tier has rate limits — heavy usage may be throttled.

For full historical details, see `docs/VERSION_HISTORY.md`.
