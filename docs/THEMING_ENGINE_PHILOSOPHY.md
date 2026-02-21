# Dreamer’s Palette — Theming Engine Design Philosophy
## Atmospheric Immersion, Elegant Restraint

---

## Core Intent

The Theming Engine is not decoration.

It is atmosphere.

Each poetry collection becomes an immersive emotional world —
a quiet environment where verse exists without distraction.

The background sets the mood.
The overlay shapes the light.
The typography remains sacred.

The poetry must absorb you.

---

# I. The Atmosphere Doctrine

Dreamer’s Palette follows a layered visual hierarchy:

1. Background = Memory
2. Overlay = Emotional Filter
3. Typography = Presence
4. UI Components = Restraint

The reader should feel surrounded — not distracted.

---

# II. Background Philosophy

Each collection may define:

- `theme_background_url`
- `theme_overlay_opacity`
- `accent_color`

The background image must:

- Fill the entire viewport
- Remain static (no parallax)
- Avoid animation
- Avoid motion effects
- Avoid visual competition with text

The image is mood.
It must feel distant.
It must feel atmospheric.
It must never fight the poem.

---

# III. Overlay System — Atmospheric Tinting

The overlay is not merely darkening.

It is an emotional color filter.

## 1. Tinting Based on Accent Color

The overlay is derived from:

- `accent_color`
- `theme_overlay_opacity`

Instead of flat black:
rgba(accent_color, overlayOpacity)


This creates harmony between:

- Background
- Links
- Subtle UI accents
- Emotional tone

The overlay must:

- Push background back
- Pull poetry forward
- Preserve contrast accessibility
- Maintain calm tonal balance

---

## 2. Suggested Overlay Levels

Light Atmosphere
- Opacity: 0.4
- Airy and open

Balanced Atmosphere (Default)
- Opacity: 0.6
- Immersive yet readable

Deep Absorption
- Opacity: 0.75
- Intimate and intense

Opacity must always prioritize readability.

---

## 3. Subtle Gradient Enhancement (Optional)

To deepen immersion without clutter:

- Slight radial vignette
- Soft vertical fade
- Gentle edge darkening

No harsh gradients.
No strong directional lighting.
No visual gimmicks.

---

# IV. Typography as Sacred Layer

Poetry must remain untouched by visual noise.

Rules:

- `max-w-3xl` reading width
- Generous vertical spacing
- Calm line-height
- No heavy borders
- No drop shadows on text
- No glassmorphism

Whitespace is intentional.
Line breaks must be preserved.
Stanza spacing must never collapse.

The poem is the focal point.
Everything else recedes.

---

# V. Component Discipline

UI components must remain:

- Minimal
- Neutral
- Soft
- Typographically focused

Accent color usage must be subtle:

- Link highlights
- Small UI accents
- Thin borders (optional)

Never:

- Large colored buttons over poetry
- High-saturation blocks
- Visual clutter

The interface must feel invisible.

---

# VI. Layout Architecture

Theme application belongs to:

`ReaderLayout`

Not to individual pages.

Structure:

ReaderLayout
├── Background Layer (fixed)
├── Overlay Layer (tinted)
├── Content Container
└── Children (Collection / Poem)

Theme logic must be centralized.
Pages remain clean.
No duplication.

---

# VII. Emotional Goal

The experience should feel like:

- Entering a memory
- Sitting in evening light
- Reading in a quiet room
- The world dimming around the poem

The background should feel like a memory.
The overlay should feel like evening.
The poetry should feel like the only thing that exists.

---

# VIII. What We Avoid

No:

- Animated backgrounds
- Parallax scrolling
- Blur effects on text
- High contrast UI chrome
- Flashy transitions
- Decorative visual clutter
- Overpowering accent colors

Dreamer’s Palette is not a theme gallery.

It is a reading sanctuary.

---

# IX. Strategic Impact

The Theming Engine defines Dreamer’s Palette’s identity.

It differentiates the platform from:

- Generic CMS platforms
- Static blog readers
- Over-designed content apps

It creates:

- Immersive literary worlds
- Emotional atmosphere
- Authorial aesthetic identity
- Foundation for future AI background generation

---

## End of Philosophy Document
