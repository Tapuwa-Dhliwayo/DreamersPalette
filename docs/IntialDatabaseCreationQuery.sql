-- Dreamer’s Palette — Full Supabase Migration (v1)
-- Stack: React + Supabase + Markdown + AI Assets
-- Online-only platform (no offline/PWA)

-- Enable required extensions
create extension if not exists "uuid-ossp";

---

## -- 1. PROFILES (extends Supabase auth.users)

create table public.profiles (
id uuid primary key references auth.users(id) on delete cascade,
display_name text not null,
bio text,
avatar_url text,
role text not null default 'author', -- admin | author
created_at timestamp with time zone default now()
);

-- Auto-create profile on signup
create or replace function public.handle_new_user()
returns trigger as $$
begin
insert into public.profiles (id, display_name, role)
values (new.id, coalesce(new.raw_user_meta_data->>'display_name', 'Author'), 'author');
return new;
end;
$$ language plpgsql security definer;

create trigger on_auth_user_created
after insert on auth.users
for each row execute procedure public.handle_new_user();

---

## -- 2. POETRY COLLECTIONS (Themed Worlds)

create table public.poetry_collections (
id uuid primary key default uuid_generate_v4(),
author_id uuid not null references public.profiles(id) on delete cascade,
title text not null,
slug text not null unique,
description text,
theme_background_url text,
theme_overlay_opacity numeric default 0.4,
accent_color text,
is_published boolean default false,
created_at timestamp with time zone default now(),
updated_at timestamp with time zone default now()
);

create index poetry_collections_author_id_idx on public.poetry_collections(author_id);
create index poetry_collections_slug_idx on public.poetry_collections(slug);

---

## -- 3. POEMS (Markdown Content)

create table public.poems (
id uuid primary key default uuid_generate_v4(),
collection_id uuid not null references public.poetry_collections(id) on delete cascade,
author_id uuid not null references public.profiles(id) on delete cascade,
title text not null,
slug text not null unique,
content_md text not null, -- Markdown storage
excerpt text,
is_published boolean default false,
created_at timestamp with time zone default now(),
updated_at timestamp with time zone default now()
);

create index poems_collection_id_idx on public.poems(collection_id);
create index poems_author_id_idx on public.poems(author_id);
create index poems_slug_idx on public.poems(slug);

---

## -- 4. BOOKS (Novels / Story Collections)

create table public.books (
id uuid primary key default uuid_generate_v4(),
author_id uuid not null references public.profiles(id) on delete cascade,
title text not null,
slug text not null unique,
synopsis text,
cover_image_url text,
ai_prompt text,
is_published boolean default false,
created_at timestamp with time zone default now(),
updated_at timestamp with time zone default now()
);

create index books_author_id_idx on public.books(author_id);
create index books_slug_idx on public.books(slug);

---

## -- 5. CHAPTERS (Markdown + Preview Support)

create table public.chapters (
id uuid primary key default uuid_generate_v4(),
book_id uuid not null references public.books(id) on delete cascade,
author_id uuid not null references public.profiles(id) on delete cascade,
title text not null,
chapter_number integer not null,
content_md text not null,
is_preview boolean default true,
is_published boolean default false,
created_at timestamp with time zone default now(),
updated_at timestamp with time zone default now(),
unique(book_id, chapter_number)
);

create index chapters_book_id_idx on public.chapters(book_id);
create index chapters_author_id_idx on public.chapters(author_id);

---

## -- 6. GENERATED ASSETS (AI Images Tracking)

create table public.generated_assets (
id uuid primary key default uuid_generate_v4(),
author_id uuid not null references public.profiles(id) on delete cascade,
type text not null, -- cover | background
prompt text not null,
image_url text not null,
created_at timestamp with time zone default now()
);

create index generated_assets_author_id_idx on public.generated_assets(author_id);

---

## -- 7. UPDATED_AT AUTO TRIGGERS

create or replace function public.set_updated_at()
returns trigger as $$
begin
new.updated_at = now();
return new;
end;
$$ language plpgsql;

create trigger set_updated_at_poetry_collections
before update on public.poetry_collections
for each row execute procedure public.set_updated_at();

create trigger set_updated_at_poems
before update on public.poems
for each row execute procedure public.set_updated_at();

create trigger set_updated_at_books
before update on public.books
for each row execute procedure public.set_updated_at();

create trigger set_updated_at_chapters
before update on public.chapters
for each row execute procedure public.set_updated_at();

---

## -- 8. ROW LEVEL SECURITY (CRITICAL)

alter table public.profiles enable row level security;
alter table public.poetry_collections enable row level security;
alter table public.poems enable row level security;
alter table public.books enable row level security;
alter table public.chapters enable row level security;
alter table public.generated_assets enable row level security;

---

## -- 9. PROFILES POLICIES

create policy "Users can view all profiles"
on public.profiles for select
using (true);

create policy "Users can update own profile"
on public.profiles for update
using (auth.uid() = id);

---

## -- 10. PUBLIC READ (PUBLISHED CONTENT ONLY)

create policy "Public can read published collections"
on public.poetry_collections for select
using (is_published = true);

create policy "Public can read published poems"
on public.poems for select
using (is_published = true);

create policy "Public can read published books"
on public.books for select
using (is_published = true);

create policy "Public can read published chapters"
on public.chapters for select
using (is_published = true);

---

## -- 11. AUTHOR FULL CONTROL (OWN CONTENT)

create policy "Authors manage own collections"
on public.poetry_collections
for all
using (auth.uid() = author_id)
with check (auth.uid() = author_id);

create policy "Authors manage own poems"
on public.poems
for all
using (auth.uid() = author_id)
with check (auth.uid() = author_id);

create policy "Authors manage own books"
on public.books
for all
using (auth.uid() = author_id)
with check (auth.uid() = author_id);

create policy "Authors manage own chapters"
on public.chapters
for all
using (auth.uid() = author_id)
with check (auth.uid() = author_id);

create policy "Authors manage own AI assets"
on public.generated_assets
for all
using (auth.uid() = author_id)
with check (auth.uid() = author_id);

---

-- 12. STORAGE BUCKETS (Run in Supabase Storage UI)
-- Recommended bucket names:
-- covers (public)
-- backgrounds (public)
-- avatars (public)
-- generated (public)
---------------------

-- Migration Complete for Dreamer’s Palette v1
