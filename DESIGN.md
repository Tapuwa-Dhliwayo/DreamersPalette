---
name: "Dreamer’s Palette"
description: "A calm author studio that turns literary work into immersive reading worlds."
colors:
  atmospheric-charcoal: "#171717"
  deep-atmosphere: "#0a0a0a"
  quiet-white: "#ffffff"
  studio-canvas: "#fafafa"
  soft-surface: "#f5f5f5"
  subtle-divider: "#e5e5e5"
  control-border: "#d4d4d4"
  muted-ink: "#737373"
  body-ink: "#525252"
  strong-ink: "#171717"
  reader-light: "#f5f5f5"
  default-accent: "#cbd5e1"
  gallery-ink: "#080d16"
  gallery-raised: "#101827"
  gallery-skeleton: "#162238"
  gallery-white: "#f4f4f2"
  gallery-body: "#c1c8d2"
  gallery-muted: "#8e9aaa"
  gallery-accent: "#aabed8"
  reader-shadow-strong: "#00000080"
  reader-shadow-soft: "#00000040"
  reader-highlight-strong: "#ffffff4d"
  reader-highlight-soft: "#ffffff26"
  success: "#15803d"
  danger: "#dc2626"
typography:
  display:
    fontFamily: "ui-serif, Georgia, Cambria, Times New Roman, Times, serif"
    fontSize: "3.75rem"
    fontWeight: 500
    lineHeight: 1
    letterSpacing: "-0.025em"
  headline:
    fontFamily: "ui-serif, Georgia, Cambria, Times New Roman, Times, serif"
    fontSize: "2.25rem"
    fontWeight: 400
    lineHeight: 1.15
    letterSpacing: "-0.025em"
  title:
    fontFamily: "ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "-0.025em"
  body:
    fontFamily: "ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.625
  label:
    fontFamily: "ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 500
    lineHeight: 1.5
rounded:
  control: "12px"
  surface: "16px"
  feature: "24px"
  pill: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  section: "48px"
  section-large: "80px"
components:
  button-primary:
    backgroundColor: "{colors.atmospheric-charcoal}"
    textColor: "{colors.quiet-white}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
  button-subtle:
    backgroundColor: "{colors.soft-surface}"
    textColor: "{colors.strong-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
  button-ghost:
    backgroundColor: "#00000000"
    textColor: "{colors.strong-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
  card:
    backgroundColor: "{colors.quiet-white}"
    textColor: "{colors.strong-ink}"
    rounded: "{rounded.surface}"
    padding: "16px 24px"
  input:
    backgroundColor: "{colors.quiet-white}"
    textColor: "{colors.strong-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
  badge:
    backgroundColor: "{colors.soft-surface}"
    textColor: "{colors.body-ink}"
    rounded: "{rounded.pill}"
    padding: "4px 8px"
---

# Design System: Dreamer’s Palette

## 1. Overview

**Creative North Star: "The Author’s Reading Room"**

Dreamer’s Palette should feel like a private room where an author can work without interruption and then open the door to readers. The studio is quiet, capable, and familiar: neutral surfaces, compact controls, clear states, and enough breathing room to support long creative sessions. The reader is more atmospheric, but it inherits the same restraint and gives the writing visual priority.

The system uses a restrained product register in the dashboard and controlled immersion in the public reader. Collection imagery and author-selected accents provide variation; permanent interface chrome remains neutral so it does not compete with the content. Refinement must strengthen hierarchy, consistency, responsiveness, and accessibility without replacing the application’s established literary character.

It explicitly rejects generic CMS dashboards, flashy AI generators, social-publishing attention mechanics, excessive cards, decorative gradients, ornamental glass effects, and atmospheric themes that compromise legibility.

**Key Characteristics:**

- Calm, creator-first product density
- Serif emphasis for literary identity and reading surfaces
- Neutral, familiar controls for author workflows
- Collection-specific atmosphere with guarded contrast
- Restrained motion that communicates state
- Mobile-safe layouts with narrow, readable content measures

## 2. Colors

The palette is built from Atmospheric Charcoal, Quiet White, and Ink Neutrals; author-selected collection accents are contextual and never become permanent dashboard decoration.

### Primary

- **Atmospheric Charcoal:** The primary action, browser-chrome, and deep framing color. It supplies authority without using a saturated brand hue.
- **Default Accent:** A quiet cool-gray fallback used when a collection has not supplied its own accent.
- **Gallery Ink:** The homepage's subtle ink-blue night shell. Collection imagery supplies its changing color; permanent gallery chrome stays within this restrained tonal family.

### Secondary

- **Author-Selected Collection Accent:** A runtime color chosen per collection. Use it for reader links, underlines, small controls, and preview cues only after confirming contrast.

### Neutral

- **Deep Atmosphere:** The darkest reader background layer and image-overlay anchor.
- **Quiet White:** The canonical raised studio surface and high-contrast text on dark controls.
- **Studio Canvas:** The dashboard’s base background beneath working surfaces.
- **Soft Surface:** Secondary controls, inactive navigation, and quiet state fills.
- **Subtle Divider:** Structural separation in cards, sidebars, tabs, and editor panels.
- **Control Border:** The visible edge for form controls where a boundary is required.
- **Body Ink:** Default supporting copy on light studio surfaces.
- **Muted Ink:** Metadata and secondary labels; never use it where text is essential to completing a task.
- **Strong Ink:** Headings, primary content, navigation, and high-priority labels.
- **Reader Light:** Primary light text over dark or image-backed reader themes.
- **Success / Danger:** Reserved semantic colors for publication success and destructive or error states.

**The Neutral Studio Rule.** Permanent dashboard surfaces remain neutral. Collection colors belong to previews and reader outcomes, not general dashboard decoration.

**The Authored Accent Rule.** A collection accent is a supporting voice. It may identify links, thin underlines, focus cues, and small controls; it must never overwhelm the writing.

**The Contrast Gate.** No generated background or author-selected accent ships solely because it looks atmospheric. Reader text, muted text, controls, and focus states must remain legible in both text modes.

## 3. Typography

**Display Font:** System serif stack, led by `ui-serif` with Georgia as the durable fallback  
**Body Font:** System sans stack, led by `ui-sans-serif` and `system-ui`  

**Character:** The serif carries literary identity, titles, and long-form emphasis. The sans-serif carries dashboard navigation, controls, metadata, status, and operational copy. This contrast keeps the reader expressive and the studio efficient.

### Hierarchy

- **Display** (500, 3.75rem, 1): The wordmark and rare large literary moments. It is not used for dashboard labels or data.
- **Headline** (400, 2.25rem, 1.15): Public-page titles and prominent collection or book headings.
- **Title** (600, 1.5rem, 1.3): Dashboard page titles and high-priority workflow headings.
- **Body** (400, 1rem, 1.625): Reading prose and explanatory text. Reading content remains within approximately 65–75 characters per line.
- **Label** (500, 0.875rem, 1.5): Buttons, navigation, form controls, and compact UI labels.
- **Metadata** (400–500, 0.75rem, 1.5): Dates, slugs, publication counts, and secondary state. Uppercase with expanded tracking is reserved for genuinely scannable dashboard summaries.

**The Two-Voices Rule.** Serif speaks for literature; sans-serif operates the product. Never use display typography for routine buttons, form labels, navigation, or status data.

**The Preserved Line Rule.** Poetry line breaks and stanza spacing are content, not decoration. They must survive editing, previewing, responsive layout, and public rendering unchanged.

## 4. Elevation

Elevation is layered and ambient. Tonal separation establishes most hierarchy; soft shadows appear on raised working surfaces, public content tiles, modal layers, and interactive hover states. Reader imagery is pushed backward with overlays rather than elevated forward with ornamental effects.

### Shadow Vocabulary

- **Ambient Rest** (`0 1px 2px rgba(0, 0, 0, 0.05)`): Low separation for cards, editor shells, and quiet public tiles.
- **Ambient Hover** (`0 4px 6px -1px rgba(0, 0, 0, 0.10), 0 2px 4px -2px rgba(0, 0, 0, 0.10)`): Brief feedback for interactive content surfaces.
- **Modal Lift** (`0 20px 25px -5px rgba(0, 0, 0, 0.10), 0 8px 10px -6px rgba(0, 0, 0, 0.10)`): Reserved for dialogs above a darkened backdrop.
- **Reader Legibility** (`0 1px 3px rgba(0, 0, 0, 0.50), 0 0 12px rgba(0, 0, 0, 0.25)`): A conditional text treatment for light text over image-backed themes, not decorative elevation.

**The Layered and Ambient Rule.** Depth must explain structure or state. Never combine a decorative wide shadow with a decorative border on the same resting surface.

**The Flat Workflow Rule.** Dashboard controls remain visually stable at rest. Hover and focus may increase definition, but routine actions must not float, glow, or animate for spectacle.

## 5. Components

Components are refined and restrained. They use familiar product affordances, consistent neutral assignments, and concise state changes so authors can concentrate on their work.

### Buttons

- **Shape:** Gently curved controls (12px radius); pills are reserved for compact accent actions and badges.
- **Primary:** Atmospheric Charcoal with Quiet White text and compact 8px × 16px padding.
- **Hover / Focus:** A slightly lighter charcoal on hover and a visible two-pixel focus ring with offset. State transitions stay within 150–250ms.
- **Subtle:** Soft Surface with Strong Ink; used for secondary workflow actions.
- **Ghost:** Transparent at rest with a Soft Surface hover; used for cancellation and low-priority actions.
- **Danger:** Semantic red with white text; reserved for destructive actions and always paired with explicit copy.

### Chips

- **Style:** Full-pill status badges with compact 4px × 8px padding.
- **State:** Neutral for drafts and counts, green for published or successful state, red for danger. Never encode state by color alone.

### Cards / Containers

- **Corner Style:** Softly rounded working surfaces (16px radius). Feature shells may reach 24px only where they frame a complete reader or modal experience.
- **Background:** Quiet White in the dashboard; translucent light surfaces are allowed on atmospheric public pages only when text contrast remains dependable.
- **Shadow Strategy:** Ambient Rest by default, Ambient Hover only for clickable surfaces.
- **Border:** A subtle neutral divider may define dashboard structure. Do not pair it with a wide decorative shadow.
- **Internal Padding:** 16–24px for routine surfaces; 32px only for spacious forms, empty states, or desktop modal content.

### Inputs / Fields

- **Style:** Quiet White or transparent surface, one-pixel Control Border, 12px radius, and 8–12px vertical padding.
- **Focus:** A clearly visible two-pixel neutral or validated collection-accent ring with sufficient contrast.
- **Error / Disabled:** Errors use semantic red text tied to the field in accessible markup. Disabled controls reduce emphasis but remain readable.
- **Placeholder:** Placeholder text must meet usable contrast and never substitute for a persistent label when the field’s purpose could become ambiguous.

### Navigation

- **Dashboard:** A stable 256px sidebar on desktop and an overlay drawer on mobile. Active destinations receive a quiet filled background and medium weight; inactive destinations use Body Ink with a Soft Surface hover.
- **Reader:** Navigation remains narrow, understated, and context-aware. It inherits the active collection’s text mode and uses a restrained backdrop only where needed for legibility.
- **Links:** Prose links may use underline treatment. Component and navigation links must not inherit decorative lift or shadow behavior intended for content surfaces.

### Editor Panel

The editor is the signature authoring surface: a bordered white shell with a quiet toolbar, writing pane, and responsive preview. Desktop favors a side-by-side split; mobile stacks the panes. Markdown controls must remain compact and predictable, while preview typography accurately represents public rendering.

### Collection Theme Preview

The preview is the bridge between author productivity and reader immersion. It shows the real background, overlay, text mode, accent, and representative content together. Changes should appear immediately, and unsafe contrast combinations should be prevented or clearly flagged before publication.

### Dashboard Content List

Collections, poems, novels, and chapters share one compact management pattern: search and sort controls, optional multi-selection, border-separated rows, visible publication state, and consistent Publish, Edit, and Archive actions. Cards are reserved for empty states and exceptional content, not routine library rows.

## 6. Do's and Don'ts

### Do:

- **Do** keep the dashboard neutral, familiar, and task-oriented so authors remain in creative flow.
- **Do** use serif typography for literary identity and sans-serif typography for operational interface text.
- **Do** preserve poetry line breaks, stanza spacing, a narrow reading measure, and mobile safe-area spacing.
- **Do** preview collection background, overlay, accent, and text mode as one complete reader outcome.
- **Do** use author-selected accents sparingly and verify contrast before applying them to text or controls.
- **Do** provide keyboard access, visible focus states, semantic labels, clear errors, and reduced-motion behavior.
- **Do** use skeleton or contextual loading states where content structure is known, and empty states that teach the next action.
- **Do** keep motion between 150–250ms and use it to communicate state, navigation, or feedback.

### Don't:

- **Don't** turn the dashboard into a generic CMS dashboard that reduces literary work to undifferentiated forms and tables.
- **Don't** make AI generation resemble a flashy AI generator where generation becomes the spectacle or the result is unclear.
- **Don't** introduce social publishing platform patterns built around feeds, engagement metrics, or attention capture.
- **Don't** compete with the writing through excessive cards, decorative gradients, ornamental glass effects, saturated chrome, or gratuitous motion.
- **Don't** approve atmospheric themes that sacrifice contrast, legibility, focus visibility, or navigation clarity.
- **Don't** use side-stripe accent borders, gradient text, repeating stripe backgrounds, or decorative sketch-style SVG imagery.
- **Don't** use display fonts in buttons, form labels, navigation, status, or dashboard data.
- **Don't** use radii above 16px on ordinary cards, fields, or controls; 24px is reserved for complete feature shells.
- **Don't** pair a one-pixel decorative border with a soft shadow wider than 8px on the same resting component.
- **Don't** use modals as the first solution when inline editing or progressive disclosure can preserve the author’s context.
