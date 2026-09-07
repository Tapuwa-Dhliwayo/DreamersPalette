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

-- Shareable poem cards: preserve the date a poem first became public.
ALTER TABLE poems
    ADD COLUMN IF NOT EXISTS published_at timestamp with time zone;

UPDATE poems
SET published_at = created_at
WHERE is_published = true
  AND published_at IS NULL;

CREATE OR REPLACE FUNCTION public.preserve_poem_published_at()
RETURNS trigger
LANGUAGE plpgsql
SET search_path = ''
AS $$
BEGIN
    IF NEW.is_published = true AND NEW.published_at IS NULL THEN
        NEW.published_at = now();
    END IF;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS preserve_poem_published_at ON public.poems;

CREATE TRIGGER preserve_poem_published_at
    BEFORE INSERT OR UPDATE OF is_published ON public.poems
    FOR EACH ROW
    EXECUTE FUNCTION public.preserve_poem_published_at();
