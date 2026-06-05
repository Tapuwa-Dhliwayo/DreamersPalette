import { supabase } from "./supabaseClient"
import { slugify } from "../utils/slugify"


/* ================================
   INTERNAL — UNIQUE SLUG INSERT
================================ */

async function insertWithUniqueSlug({
    table,
    payload,
    titleField = "title",
    maxRetries = 10,
    userId
}) {
    const baseSlug = slugify(payload[titleField])

    let attempt = 0
    let slug = baseSlug

    while (attempt < maxRetries) {
        const { data, error } = await supabase
            .from(table)
            .insert([
                {
                    ...payload,
                    slug,
                    author_id: userId
                }
            ])
            .select()
            .single()

        if (!error) {
            return data
        }

        if (error.code === "23505") {
            attempt++
            slug = `${baseSlug}-${attempt}`
            continue
        }

        throw error
    }

    throw new Error("Unable to generate unique slug after multiple attempts.")
}

/* ================================
   AUTHOR QUERIES (Books)
================================ */

/**
 * Get books owned by current author
 * RLS: auth.uid() = author_id
 */
export async function getMyBooks() {
    const { data, error } = await supabase
        .from("books")
        .select("id, title, slug, synopsis, cover_image_url, is_published, created_at, updated_at")
        .order("created_at", { ascending: false })

    if (error) throw error
    return data
}

/**
 * Get single book owned by current author
 */
export async function getMyBookById(bookId) {
    const { data, error } = await supabase
        .from("books")
        .select("id, title, slug, synopsis, cover_image_url, is_published, created_at, updated_at")
        .eq("id", bookId)
        .single()

    if (error) throw error
    return data
}

/**
 * Create new book with unique slug
 */
export async function createBook(payload) {
    const {
        data: { user },
        error: userError
    } = await supabase.auth.getUser()

    if (userError || !user) {
        throw new Error("User not authenticated")
    }

    return await insertWithUniqueSlug({
        table: "books",
        payload,
        userId: user.id
    })
}

/**
 * Update book (author-owned via RLS)
 */
export async function updateBook(id, updates) {
    const { data, error } = await supabase
        .from("books")
        .update(updates)
        .eq("id", id)
        .select()
        .single()

    if (error) throw error
    return data
}

/**
 * Delete book (author-owned only)
 */
export async function deleteBook(id) {
    const { error } = await supabase
        .from("books")
        .delete()
        .eq("id", id)

    if (error) throw error
}

/**
 * Toggle book publish state
 */
export async function toggleBookPublish(id, isPublished) {
    const { data, error } = await supabase
        .from("books")
        .update({ is_published: isPublished })
        .eq("id", id)
        .select()
        .single()

    if (error) throw error
    return data
}

/* ================================
   AUTHOR QUERIES (Chapters)
================================ */

/**
 * Get chapters by book (author view)
 */
export async function getMyChaptersByBook(bookId) {
    const { data, error } = await supabase
        .from("chapters")
        .select("id, book_id, title, chapter_number, is_preview, is_published, created_at")
        .eq("book_id", bookId)
        .order("chapter_number", { ascending: true })

    if (error) throw error
    return data
}

/**
 * Get single chapter owned by current author
 */
export async function getMyChapterById(chapterId) {
    const { data, error } = await supabase
        .from("chapters")
        .select("id, book_id, title, chapter_number, content_md, is_preview, is_published, created_at")
        .eq("id", chapterId)
        .single()

    if (error) throw error
    return data
}

/**
 * Create new chapter
 */
export async function createChapter(payload) {
    const {
        data: { user },
        error: userError
    } = await supabase.auth.getUser()

    if (userError || !user) {
        throw new Error("User not authenticated")
    }

    const { data, error } = await supabase
        .from("chapters")
        .insert([
            {
                ...payload,
                author_id: user.id
            }
        ])
        .select()
        .single()

    if (error) throw error
    return data
}

/**
 * Update chapter (author-owned only)
 */
export async function updateChapter(id, updates) {
    const { data, error } = await supabase
        .from("chapters")
        .update(updates)
        .eq("id", id)
        .select()
        .single()

    if (error) throw error
    return data
}

/**
 * Delete chapter (author-owned only)
 */
export async function deleteChapter(id) {
    const { error } = await supabase
        .from("chapters")
        .delete()
        .eq("id", id)

    if (error) throw error
}

/**
 * Toggle chapter publish state
 */
export async function toggleChapterPublish(id, isPublished) {
    const { data, error } = await supabase
        .from("chapters")
        .update({ is_published: isPublished })
        .eq("id", id)
        .select()
        .single()

    if (error) throw error
    return data
}

/* ================================
   PUBLIC QUERIES (Books)
================================ */

/**
 * Fetch only published books
 */
export async function getPublishedBooks() {
    const { data, error } = await supabase
        .from("books")
        .select("id, title, slug, synopsis, cover_image_url, theme_background_url")
        .eq("is_published", true)
        .order("created_at", { ascending: false })

    if (error) throw error
    return data
}

/**
 * Fetch published books with pagination
 * @param {number} page - 1-based page number
 * @param {number} pageSize - items per page
 * @returns {{ data: Array, count: number }}
 */
export async function getPublishedBooksPaginated(page = 1, pageSize = 12) {
    const from = (page - 1) * pageSize
    const to = from + pageSize - 1

    const { data, error, count } = await supabase
        .from("books")
        .select("id, title, slug, synopsis, cover_image_url, theme_background_url", { count: "exact" })
        .eq("is_published", true)
        .order("created_at", { ascending: false })
        .range(from, to)

    if (error) throw error
    return { data, count }
}

/**
 * Fetch single published book by slug
 */
export async function getBookBySlug(slug) {
    const { data, error } = await supabase
        .from("books")
        .select("id, title, slug, synopsis, cover_image_url, theme_background_url, theme_overlay_opacity, accent_color, theme_text_mode, author_id")
        .eq("slug", slug)
        .eq("is_published", true)
        .single()

    if (error) throw error
    return data
}

/**
 * Fetch book by slug (author preview, no publish filter)
 */
export async function getBookBySlugPreview(slug) {
    const { data, error } = await supabase
        .from("books")
        .select("id, title, slug, synopsis, cover_image_url, theme_background_url, theme_overlay_opacity, accent_color, theme_text_mode, author_id")
        .eq("slug", slug)
        .single()

    if (error) throw error
    return data
}

/* ================================
   PUBLIC QUERIES (Chapters)
================================ */

/**
 * Fetch published chapters for a book by book slug
 */
export async function getPublishedChaptersByBook(bookSlug) {
    const { data, error } = await supabase
        .from("chapters")
        .select(`
            id,
            title,
            chapter_number,
            is_preview,
            books!inner(slug)
        `)
        .eq("is_published", true)
        .eq("books.slug", bookSlug)
        .order("chapter_number", { ascending: true })

    if (error) throw error
    return data
}

/**
 * Fetch published chapters for a book by book slug with pagination
 * @param {string} bookSlug
 * @param {number} page - 1-based page number
 * @param {number} pageSize - items per page
 * @returns {{ data: Array, count: number }}
 */
export async function getPublishedChaptersByBookPaginated(bookSlug, page = 1, pageSize = 12) {
    const from = (page - 1) * pageSize
    const to = from + pageSize - 1

    const { data, error, count } = await supabase
        .from("chapters")
        .select(`
            id,
            title,
            chapter_number,
            is_preview,
            books!inner(slug)
        `, { count: "exact" })
        .eq("is_published", true)
        .eq("books.slug", bookSlug)
        .order("chapter_number", { ascending: true })
        .range(from, to)

    if (error) throw error
    return { data, count }
}

/**
 * Fetch single published chapter by book slug and chapter number
 */
export async function getPublishedChapter(bookSlug, chapterNumber) {
    const { data, error } = await supabase
        .from("chapters")
        .select(`
            id,
            book_id,
            title,
            chapter_number,
            content_md,
            is_preview,
            books!inner(slug)
        `)
        .eq("is_published", true)
        .eq("books.slug", bookSlug)
        .eq("chapter_number", chapterNumber)
        .single()

    if (error) throw error
    return data
}
