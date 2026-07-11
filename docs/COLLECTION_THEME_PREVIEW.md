# Collection Theme Preview

## Purpose

The dashboard collection editor includes an inline live preview for collection themes. It lets authors judge the reader background, overlay, accent color, and text contrast while editing, without opening the protected `/preview/collections/:slug` route in a new tab.

This follows the same product pattern as Invito's designer preview: controls update local draft state, and a constrained preview artboard renders from that draft state immediately. Saving remains a separate action.

## Files

- `src/pages/dashboard/CollectionEditorPage.jsx`
  - Owns collection form state (create/edit is a full page, no longer a modal).
  - Passes unsaved form values into the preview.
  - Persists theme fields on create/update.
- `src/pages/dashboard/Collections.jsx`
  - The collections list page; links into the editor page.
- `src/components/dashboard/CollectionThemePreview.jsx`
  - Renders the inline reader-style preview artboard.
  - Mirrors reader theme behavior for background image, overlay, accent color, and text contrast.
- `src/layouts/ReaderLayout.jsx`
  - Remains the production reader layout.
- `src/hooks/useCollectionTheme.js`
  - Remains the route-backed theme resolver for public reader pages.

## Data Flow

1. The author opens the collection editor page (`/dashboard/collections/new` or `/dashboard/collections/:id/edit`).
2. `CollectionEditorPage.jsx` initializes local `form` state with collection fields:
   - `title`
   - `description`
   - `theme_background_url`
   - `theme_overlay_opacity`
   - `accent_color`
   - `theme_text_mode`
3. Inputs, uploads, and AI generation update `form`.
4. `CollectionThemePreview` receives `form` as `collection`.
5. The preview recalculates the visual theme from draft values on every render.
6. Clicking Save persists the same fields through `createCollection` or `updateCollection`.

## Theme Behavior

The preview intentionally mirrors the reader rules:

- `theme_background_url` is rendered as the collection background.
- `theme_overlay_opacity` controls the overlay strength.
- `theme_text_mode: "light"` uses a dark overlay and light text.
- `theme_text_mode: "dark"` uses a light overlay and dark text.
- `accent_color` is exposed as `--accent-color`, so underline accents match the reader.

The preview is an approximation of the reader route chrome, but it uses the same visual contract and global reader utility classes.

## Why Not Reuse ReaderLayout Directly

`ReaderLayout` resolves theme data from the current route through `useCollectionTheme()` and `useActiveCollection()`. The dashboard preview needs to render unsaved draft values that may not exist in the database yet.

For that reason, the inline preview uses a local render component instead of mounting `ReaderLayout`.

## Full Preview Route

The protected `/preview/collections/:slug` route still exists for full-page review of saved collection state. The inline preview is the primary editing feedback loop; the route is still useful for final saved-state checks.
