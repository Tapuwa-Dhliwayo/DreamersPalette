-- Dreamer’s Palette — Storage Policies (v1)
-- Buckets: covers, backgrounds, avatars, generated

-- 1. PUBLIC READ ACCESS (For published platform content)

create policy "Public read covers"
on storage.objects for select
using (bucket_id = 'covers');

create policy "Public read backgrounds"
on storage.objects for select
using (bucket_id = 'backgrounds');

create policy "Public read avatars"
on storage.objects for select
using (bucket_id = 'avatars');

create policy "Public read generated assets"
on storage.objects for select
using (bucket_id = 'generated');

---

-- 2. AUTHENTICATED UPLOAD (Authors Only)

create policy "Authenticated users can upload covers"
on storage.objects for insert
to authenticated
with check (bucket_id = 'covers');

create policy "Authenticated users can upload backgrounds"
on storage.objects for insert
to authenticated
with check (bucket_id = 'backgrounds');

create policy "Authenticated users can upload avatars"
on storage.objects for insert
to authenticated
with check (bucket_id = 'avatars');

create policy "Authenticated users can upload generated assets"
on storage.objects for insert
to authenticated
with check (bucket_id = 'generated');

---

-- 3. AUTHORS CAN UPDATE/DELETE THEIR OWN FILES
-- (Uses file path convention: userId/filename.png)
---------------------------------------------------

create policy "Users update own files"
on storage.objects for update
to authenticated
using (auth.uid()::text = (storage.foldername(name))[1]);

create policy "Users delete own files"
on storage.objects for delete
to authenticated
using (auth.uid()::text = (storage.foldername(name))[1]);

-- Storage setup complete for Dreamer’s Palette
