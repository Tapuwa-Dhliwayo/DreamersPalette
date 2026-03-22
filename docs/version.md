# Version Notes (Current Sprint)

## v0.5.0-sprint.2 — AI Prompted Asset Generation

### AI generation site currently used

- **Pollinations** (`https://image.pollinations.ai/prompt/...`)

### Current practical limitations

- Output quality/style consistency varies by prompt and provider-side model behavior.
- No client-side throttling/queueing is implemented yet.
- Generation depends on provider/network availability and latency.
- Only `pollinations` is supported right now (`VITE_AI_IMAGE_PROVIDER`).
- Prompt length is capped at 500 characters.
- Successful persistence depends on existing Supabase Storage policies.

For full historical details, see `docs/VERSION_HISTORY.md`.
