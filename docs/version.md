# Version Notes (Current Sprint)

## v0.5.1 — Mobile Optimization + Visual Contrast

### Part 1 — Mobile & Frame Optimization

- **Fixed viewport frame:** Reader and dashboard layouts now use `100dvh` (with `100vh` fallback) instead of `min-h-screen`, creating a contained frame that doesn't overflow past mobile browser chrome buttons.
- **Sticky navigation header:** Reader header is now sticky with a backdrop-blur glass effect, staying visible as users scroll through poems and chapters.
- **Safe-area insets:** Added `viewport-fit=cover` and `env(safe-area-inset-*)` padding for notched/modern mobile browsers.
- **Internal scrolling:** Content scrolls within the framed world container, keeping the app within bounds on both mobile and desktop.
- **Desktop framing:** On desktop, the `max-w-5xl` framed world with rounded corners contains all content flow.

### Part 2 — Visual Contrast Improvements

- **Dynamic text tone:** `textMode` from collection theming now actively drives text color (`text-neutral-100` for dark backgrounds, `text-neutral-900` for light backgrounds). Previously computed but never applied.
- **Poem/Chapter text fix:** Removed broken `text-neutral-800 dark:text-neutral-800` (identical in both modes) and `text-neutral-500` (low contrast). Text now inherits high-contrast color from the layout.
- **Collection/Book listing titles:** Removed hardcoded `text-neutral-500` from poem and chapter titles in collection/book detail pages.
- **Logo color inheritance:** Removed forced `text-neutral-700!` — Logo now inherits the appropriate color from its context (light on dark, dark on light).
- **Text shadow for backgrounds:** Added `reader-text-shadow-light` and `reader-text-shadow-dark` CSS utilities that apply when themed backgrounds are active, improving readability.
- **Reader muted text:** Added `--reader-muted` CSS custom property that adapts to the active text mode for secondary/muted text.

For full historical details, see `docs/VERSION_HISTORY.md`.
