alter table poetry_collections
    add column theme_text_mode text default 'light';

-- Phase 5: Books Theming Parity
-- Add theming fields to books table (matching poetry_collections theming engine)
alter table books
    add column theme_background_url text;

alter table books
    add column theme_overlay_opacity numeric default 0.65;

alter table books
    add column accent_color text;

alter table books
    add column theme_text_mode text default 'light';

-- For UI/UX Hardening
-- Soft delete columns added

ALTER TABLE poetry_collections
    ADD COLUMN IF NOT EXISTS deleted_at timestamp NULL;

ALTER TABLE poems
    ADD COLUMN IF NOT EXISTS deleted_at timestamp NULL;

ALTER TABLE books
    ADD COLUMN IF NOT EXISTS deleted_at timestamp NULL;

ALTER TABLE chapters
    ADD COLUMN IF NOT EXISTS deleted_at timestamp NULL;

-- Phase 6: Collection Text Colour Controls
-- Null values preserve the existing theme_text_mode-derived reader colours.
ALTER TABLE poetry_collections
    ADD COLUMN IF NOT EXISTS theme_text_color text;

ALTER TABLE poetry_collections
    ADD COLUMN IF NOT EXISTS theme_heading_color text;

ALTER TABLE poetry_collections
    ADD COLUMN IF NOT EXISTS theme_muted_color text;
