# Version Notes (Current Sprint)

## v0.5.0-sprint.2 — AI Prompted Asset Generation + Editorial Refinement

### Workstream D — Editorial Refinement (completed)

- **ReaderNavigation micro-interactions:** Context nav bar slides in with a subtle fade animation. Back links and prev/next buttons have directional hover shifts for tactile feedback.
- **Accent underline style treatment:** New `.accent-underline` CSS class tied to `--accent-color`. Applied to reader page headings (poems, chapters, collections, books) for editorial polish.
- **Reader page entrance animation:** Content pages fade in smoothly via `.reader-fade-in` for a polished loading-to-content transition.
- **EditorPanel preview parity:** Markdown preview pane styling now matches the reader layout more closely (font-serif headings, consistent spacing, prose-reading wrapper).
- **Layout transition smoothness:** Main content area has transition-opacity for smoother route changes within the reader.

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
