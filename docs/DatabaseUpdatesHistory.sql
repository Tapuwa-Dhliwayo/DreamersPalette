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