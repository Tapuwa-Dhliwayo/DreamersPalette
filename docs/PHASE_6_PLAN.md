# Dreamer's Palette — Phase 6 Plan

## Writing Assistance Tools + Collection Text Colour Controls

---

## Overview

Phase 6 is the first post-1.0 feature phase. It deepens the two core author
workflows:

- **Pillar A — Writing Assistance Tools:** selection-based language tools
  (spelling, synonyms, definitions, grammar) inside the editor, without ever
  auto-changing the author's words.
- **Pillar B — Collection Text Colour Controls:** direct control over the
  default text colours of a collection theme, with live preview and contrast
  safety.

Both pillars follow the existing product principles: keep authors in flow,
offer immediate previews, and never sacrifice legibility (`PRODUCT.md`).

Version Target: **v1.1.0**

---

# Pillar A — Writing Assistance Tools

## Objective

When an author selects a word or phrase in the editor, they can look up
spelling corrections, synonyms and antonyms, definitions, part of speech,
related words and phrasing, and basic grammar/punctuation feedback for the
surrounding sentence — and optionally replace the selection with a suggestion.

**Golden rule:** suggestions never modify the poem automatically. The author
must explicitly apply a suggestion. Intentional poetic language, unusual
spelling, rhythm, and stylistic choices are never flagged as errors that block
anything.

## Where It Lives

`src/components/editor/EditorPanel.jsx` is the single markdown editor shared
by the poem editor (`PoemEditorPage.jsx`) and chapter editor
(`ChapterEditorPage.jsx`). Building the assistant into `EditorPanel` makes it
available across the application with one implementation.

Entry points (all optional, never intrusive):

1. **Contextual toolbar/popover** — appears near the selection when text is
   selected (primary entry point).
2. **Toolbar command** — an "Assist" action in the existing `toolbarActions`
   row for keyboard-first or touch users.
3. **Right-click menu** — optional enhancement; must not break the native
   context menu (spellcheck, paste). Only add if it can coexist cleanly.

## New Files

| File | Purpose |
|------|---------|
| `src/services/writingAssistantService.js` | All lookup calls: spelling suggestions, synonyms/antonyms, definitions, part of speech, related words, grammar check. Normalizes provider responses into one suggestion shape. |
| `src/components/editor/WritingAssistant.jsx` | The popover/panel UI: tabs or sections for Spelling, Thesaurus, Definition, Grammar. Renders suggestions with explicit "Replace" actions. |
| `src/hooks/useTextSelection.js` | Tracks the textarea selection (offsets + selected text + surrounding sentence) so the assistant and `EditorPanel` share one source of truth. |

## Modified Files

| File | Change |
|------|--------|
| `src/components/editor/EditorPanel.jsx` | Expose a `replaceSelection(replacement)` built on the existing `updateValue`/selection-range mechanics; mount `WritingAssistant`; add the toolbar entry. |

No schema changes. No new tables.

## Data Sources

Keep the same discipline as the AI asset work: no secret keys in frontend
code, and server-side calls go through a Supabase Edge Function (the
`generate-asset` pattern).

| Capability | Provider | Notes |
|------------|----------|-------|
| Synonyms / antonyms / related words / alternative phrasing | Datamuse API (`rel_syn`, `rel_ant`, `ml`) | Free, no key, CORS-friendly — safe to call client-side. |
| Spelling suggestions | Datamuse (`sp=` spelled-like) | Suggestion list only; never marks the poem "wrong". |
| Definitions + part of speech | Free Dictionary API (`dictionaryapi.dev`) | Free, no key. Part of speech comes from the definition entries. |
| Grammar / punctuation + usage-in-sentence review | New Supabase Edge Function `writing-assist` | **Decided: LanguageTool API** is the provider for this phase. The Edge Function normalizes its response into the shared suggestion shape, so a later move to an LLM (if it proves worthwhile) is a server-side swap with no frontend release. Keys/config stay in Supabase secrets. |

Sentence context for grammar/usage checks is derived locally: expand from the
selection to the nearest sentence boundaries and send only that sentence, not
the whole poem.

## UX Rules

- Popover opens only on explicit author action (selection + click/shortcut),
  never on typing.
- Every suggestion is a button; clicking it calls `replaceSelection` and
  restores focus + selection (existing `updateValue` behavior).
- Loading, empty ("no suggestions found"), and error states are quiet, inline,
  and never block the editor.
- Lookups are debounced and cached per word per session to avoid hammering the
  free APIs.
- If a provider is unreachable, that section degrades gracefully; the editor
  itself is never affected.
- Keyboard accessible: the popover is focus-trapped while open, `Esc` closes
  and returns focus to the textarea.

## Acceptance Criteria

- [ ] Selecting a word in the poem editor and opening the assistant shows
      spelling suggestions, synonyms, antonyms, definition, and part of speech
      for that word.
- [ ] Selecting a phrase offers related words / alternative phrasing and a
      grammar/punctuation review of the surrounding sentence.
- [ ] Applying a suggestion replaces exactly the selected range and nothing
      else; undo (Ctrl+Z) restores the original text.
- [ ] Nothing in the poem ever changes without an explicit apply action.
- [ ] The same tools work in the chapter editor without extra wiring.
- [ ] All states (loading/empty/error/offline) leave the editor fully usable.

---

# Pillar B — Collection Text Colour Controls

## Objective

Give authors direct control over the default text colours of a collection
theme — primary text, headings/titles, and secondary/muted text — alongside
the existing background, overlay, and accent controls, with immediate preview
and a contrast warning.

The collection-level colours are the default for all poems in the collection.
(Per-poem or per-selection overrides are **out of scope** for this phase; the
data model should simply not preclude them later.)

## Current Theme Model

`poetry_collections` today: `theme_background_url`, `theme_overlay_opacity`,
`accent_color`, `theme_text_mode` (`"light" | "dark"`). Text colours are
currently hard-coded from `theme_text_mode` (`text-neutral-100` /
`text-neutral-900` in `ReaderLayout.jsx` and `CollectionThemePreview.jsx`,
plus the `--reader-muted` CSS variable).

## Schema Change

Add three nullable columns to `poetry_collections` (append the migration to
`docs/DatabaseUpdatesHistory.sql` when running it):

```sql
alter table poetry_collections add column theme_text_color text;    -- primary poem text
alter table poetry_collections add column theme_heading_color text; -- poem titles / headings
alter table poetry_collections add column theme_muted_color text;   -- captions / supporting text
```

- `null` means "use the `theme_text_mode` default" — this is also how **reset
  to theme defaults** works (set the column back to `null`).
- Values are stored as normalized hex (`#rrggbb`), whatever input format the
  author typed.
- `accent_color` already exists and stays the accent for links, underlines,
  and decorative elements.

## Theme Layer Model (what the editor must make legible)

The editor UI must clearly distinguish these five layers:

1. **Background** — image (`theme_background_url`)
2. **Overlay** — colour derived from `theme_text_mode` + strength
   (`theme_overlay_opacity`)
3. **Primary text** — `theme_text_color` (new)
4. **Secondary / muted text** — `theme_muted_color` (new), with
   `theme_heading_color` (new) for titles/headings
5. **Accent** — `accent_color` (existing)

## Modified Files

| File | Change |
|------|--------|
| `src/services/contentService.js` | Add the three new columns to every collection projection (the explicit column lists at ~lines 60, 148, 221, 242, 258, 274, 290) and to the `createCollection`/`updateCollection` payload allow-list. No `select("*")`. |
| `src/hooks/useCollectionTheme.js` | Return `textColor`, `headingColor`, `mutedColor` with `theme_text_mode`-derived fallbacks when columns are null. |
| `src/layouts/ReaderLayout.jsx` | Replace hard-coded `text-neutral-100/900` tones with CSS variables (`--reader-text`, `--reader-heading`, `--reader-muted`) set from the resolved theme; keep the existing text-shadow behavior. Applies to collection pages, poem pages, and shared public pages since they all render through this layout. |
| `src/components/dashboard/CollectionThemePreview.jsx` | Mirror the same variables so the inline preview updates live from unsaved form state (existing draft-state pattern — see `docs/COLLECTION_THEME_PREVIEW.md`). |
| `src/pages/dashboard/CollectionEditorPage.jsx` | Extend the form state + the "Reader contrast" section with the new colour controls (see UI below). |
| `src/index.css` | Point `reader-muted` and related utilities at the new variables; include the variables in print styles so exported/printed pages match. |

## New Files

| File | Purpose |
|------|---------|
| `src/utils/color.js` | Parse HEX / `rgb()` / `hsl()` input to normalized hex; relative luminance; WCAG contrast ratio. Reuse/absorb `hexToRgb.js` behavior without breaking its existing import sites. |

## Editor UI (CollectionEditorPage)

Rename the "Reader contrast" section to **"Colours & contrast"** and group
controls by layer:

- **Overlay** — existing strength slider + light/dark text mode toggle
  (the mode toggle now doubles as the source of default text colours).
- **Text colours** — one row each for *Poem text*, *Titles & headings*,
  *Captions & supporting text*:
  - native colour picker (`<input type="color">`, same pattern as accent)
  - free-text input accepting HEX, RGB, or HSL (parsed via `utils/color.js`,
    invalid input shows a field error and is not saved)
  - a **Reset** action per row that returns the colour to the theme default
    (stores `null`)
- **Accent** — existing accent control, relabelled so its role (links,
  controls, highlights, decoration) is explicit.
- **Contrast warning** — for each text colour, compute the WCAG contrast ratio
  against the effective background (overlay colour at its opacity composited
  over a background estimate). Show a non-blocking warning below 4.5:1
  ("This colour may be hard to read against the background"). Warnings never
  prevent saving — the author decides.

The inline `CollectionThemePreview` already re-renders from draft form state
on every change, so colour edits preview immediately before saving.

## Application Surfaces

The saved colours must apply consistently wherever the collection theme
renders:

- [ ] Collection detail page (`CollectionDetailPage.jsx`)
- [ ] Poem pages (`PoemPage.jsx`)
- [ ] Protected preview route (`/preview/collections/:slug`)
- [ ] Inline dashboard preview (`CollectionThemePreview.jsx`)
- [ ] Shared/public pages (same reader layout)
- [ ] Print/export output via print CSS (where supported)

Books have theming parity fields today; extending the new colour columns to
`books` is a **stretch goal** — do it only if it stays mechanical, otherwise
record it as follow-up.

## Acceptance Criteria

- [ ] An author can set primary, heading, and muted text colours with either
      the picker or a typed HEX/RGB/HSL value.
- [ ] The inline preview updates immediately while editing, before saving.
- [ ] Reset returns each colour to the `theme_text_mode` default and stores
      `null`.
- [ ] A contrast warning appears for low-contrast choices but never blocks
      saving.
- [ ] Saved colours render identically on the collection page, poem pages,
      preview route, and public/shared pages.
- [ ] Existing collections (all-null colour columns) look exactly as they do
      today.

---

# Execution Order

1. **B1** — Schema migration + service projections + `useCollectionTheme`
   fallbacks (invisible groundwork, zero visual change).
2. **B2** — `utils/color.js` + editor colour controls + contrast warning +
   preview wiring.
3. **B3** — Reader surface application (`ReaderLayout`, `index.css`, print).
4. **A1** — `useTextSelection` + `replaceSelection` + assistant popover shell
   in `EditorPanel`.
5. **A2** — Client-side lookups (Datamuse + dictionary): spelling, thesaurus,
   definitions, part of speech, related words.
6. **A3** — `writing-assist` Edge Function wrapping LanguageTool for
   grammar/punctuation and usage-in-sentence review.

# Practical Validation Plan (No Unit Tests)

- [ ] `npm run lint` and `npm run build` pass after each workstream.
- [ ] Poem editor journey: select word → view all lookup categories → apply a
      synonym → verify exact-range replacement → undo restores original.
- [ ] Chapter editor journey: same tools available with no extra wiring.
- [ ] Offline/provider-failure check: block network to the lookup APIs and
      verify the editor stays fully usable with quiet error states.
- [ ] Colour journey: set all three text colours (one via HEX, one via RGB,
      one via HSL) → preview matches → save → verify on collection page, poem
      page, and preview route → reset all → verify defaults return.
- [ ] Contrast check: pick a deliberately unreadable colour and verify the
      warning appears and saving still works.
- [ ] Regression: open an untouched existing collection and confirm its
      reader rendering is pixel-identical to pre-Phase-6.
- [ ] RLS/projection check: confirm new columns appear only in intended
      projections and anon users can read them only on published collections.
