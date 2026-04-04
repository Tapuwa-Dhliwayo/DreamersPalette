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
   AUTHOR QUERIES
================================ */

/**
 * Get collections owned by current author
 * RLS: auth.uid() = author_id
 */
export async function getMyCollections() {
    const { data, error } = await supabase
        .from("poetry_collections")
        .select("id, title, slug, description, theme_background_url, theme_overlay_opacity, accent_color, theme_text_mode, is_published, created_at, updated_at")
        .order("created_at", { ascending: false })

    if (error) throw error
    return data
}

/**
 * Get poem counts per collection (lightweight — no row data transferred).
 * Returns { [collectionId]: { total, published } }
 */
export async function getPoemCountsByCollection() {
    // Fetch all collection IDs owned by the author
    const { data: collections, error: colErr } = await supabase
        .from("poetry_collections")
        .select("id")

    if (colErr) throw colErr
    if (!collections || collections.length === 0) return {}

    // Fire two count queries per collection in parallel (total + published)
    const counts = {}
    await Promise.all(
        collections.map(async (col) => {
            const [totalRes, pubRes] = await Promise.all([
                supabase
                    .from("poems")
                    .select("id", { count: "exact", head: true })
                    .eq("collection_id", col.id),
                supabase
                    .from("poems")
                    .select("id", { count: "exact", head: true })
                    .eq("collection_id", col.id)
                    .eq("is_published", true),
            ])

            counts[col.id] = {
                total: totalRes.count ?? 0,
                published: pubRes.count ?? 0,
            }
        })
    )

    return counts
}

/**
 * Create new collection
 */
export async function createCollection(payload) {
    const {
        data: { user },
        error: userError
    } = await supabase.auth.getUser()

    if (userError || !user) {
        throw new Error("User not authenticated")
    }

    return await insertWithUniqueSlug({
        table: "poetry_collections",
        payload,
        userId: user.id
    })
}

/**
 * Update collection (author-owned only)
 */
export async function updateCollection(id, updates) {
    const { data, error } = await supabase
        .from("poetry_collections")
        .update(updates)
        .eq("id", id)
        .select()
        .single()

    if (error) throw error
    return data
}

/**
 * Delete collection (author-owned only)
 */
export async function deleteCollection(id) {
    const { error } = await supabase
        .from("poetry_collections")
        .delete()
        .eq("id", id)

    if (error) throw error
}

/**
 * Toggle publish state
 */
export async function togglePublish(id, isPublished) {
    const { data, error } = await supabase
        .from("poetry_collections")
        .update({ is_published: isPublished })
        .eq("id", id)
        .select()
        .single()

    if (error) throw error
    return data
}


/* ================================
   PUBLIC QUERIES
================================ */

/**
 * Fetch only published collections
 */
export async function getPublishedCollections() {
    const { data, error } = await supabase
        .from("poetry_collections")
        .select("id, title, slug, description, theme_background_url")
        .eq("is_published", true)
        .order("created_at", { ascending: false })

    if (error) throw error
    return data
}

/**
 * Fetch published collections with pagination
 * @param {number} page - 1-based page number
 * @param {number} pageSize - items per page
 * @returns {{ data: Array, count: number }}
 */
export async function getPublishedCollectionsPaginated(page = 1, pageSize = 12) {
    const from = (page - 1) * pageSize
    const to = from + pageSize - 1

    const { data, error, count } = await supabase
        .from("poetry_collections")
        .select("id, title, slug, description, theme_background_url", { count: "exact" })
        .eq("is_published", true)
        .order("created_at", { ascending: false })
        .range(from, to)

    if (error) throw error
    return { data, count }
}

/**
 * Fetch single collection by slug (published only)
 */
export async function getCollectionBySlug(slug) {
    const { data, error } = await supabase
        .from("poetry_collections")
        .select("id, title, slug, description, theme_background_url, theme_overlay_opacity, accent_color, theme_text_mode, author_id")
        .eq("slug", slug)
        .eq("is_published", true)
        .single()

    if (error) throw error
    return data
}

/**
 * Fetch collection by slug (author preview, no publish filter)
 */
export async function getCollectionBySlugPreview(slug) {
    const { data, error } = await supabase
        .from("poetry_collections")
        .select("id, title, slug, description, theme_background_url, theme_overlay_opacity, accent_color, theme_text_mode, author_id")
        .eq("slug", slug)
        .single()

    if (error) throw error
    return data
}

/**
 * Fetch single collection by id (published only)
 */

export async function getCollectionById(id) {
    const { data, error } = await supabase
        .from("poetry_collections")
        .select("id, title, slug, description, theme_background_url, theme_overlay_opacity, accent_color, theme_text_mode")
        .eq("id", id)
        .eq("is_published", true)
        .single()

    if (error) throw error
    return data
}

/* ================================
   POEMS — AUTHOR QUERIES
================================ */

/**
 * Get poems owned by current author
 */
export async function getMyPoems() {
    const { data, error } = await supabase
        .from("poems")
        .select("id, collection_id, title, slug, excerpt, is_published, created_at, updated_at")
        .order("created_at", { ascending: false })

    if (error) throw error
    return data
}

/**
 * Get single poem owned by current author
 */
export async function getMyPoemById(id) {
    const { data, error } = await supabase
        .from("poems")
        .select("id, collection_id, title, slug, content_md, excerpt, is_published, created_at, updated_at")
        .eq("id", id)
        .single()

    if (error) throw error
    return data
}

/**
 * Get poems by collection (author view)
 */
export async function getPoemsByCollection(collectionId) {
    const { data, error } = await supabase
        .from("poems")
        .select("id, collection_id, title, slug, excerpt, is_published, created_at")
        .eq("collection_id", collectionId)
        .order("created_at", { ascending: false })

    if (error) throw error
    return data
}

/**
 * Create new poem with slug retry logic
 */
export async function createPoem(payload) {
    const {
        data: { user },
        error: userError
    } = await supabase.auth.getUser()

    if (userError || !user) {
        throw new Error("User not authenticated")
    }

    return await insertWithUniqueSlug({
        table: "poems",
        payload,
        userId: user.id
    })
}

/**
 * Update poem (author-owned only)
 */
export async function updatePoem(id, updates) {
    const { data, error } = await supabase
        .from("poems")
        .update(updates)
        .eq("id", id)
        .select()
        .single()

    if (error) throw error
    return data
}

/**
 * Delete poem (author-owned only)
 */
export async function deletePoem(id) {
    const { error } = await supabase
        .from("poems")
        .delete()
        .eq("id", id)

    if (error) throw error
}

/**
 * Toggle poem publish state
 */
export async function togglePoemPublish(id, isPublished) {
    const { data, error } = await supabase
        .from("poems")
        .update({ is_published: isPublished })
        .eq("id", id)
        .select()
        .single()

    if (error) throw error
    return data
}


/* ================================
   POEMS — PUBLIC QUERIES
================================ */

/**
 * Fetch published poem by slug
 */
export async function getPublishedPoemBySlug(slug) {
    const { data, error } = await supabase
        .from("poems")
        .select("id, collection_id, title, slug, content_md, excerpt, author_id")
        .eq("slug", slug)
        .eq("is_published", true)
        .single()

    if (error) throw error
    return data
}

/**
 * Fetch published poems by collection slug
 */
export async function getPublishedPoemsByCollection(collectionSlug) {
    const { data, error } = await supabase
        .from("poems")
        .select(`
            id,
            title,
            slug,
            excerpt,
            poetry_collections!inner(slug)
        `)
        .eq("is_published", true)
        .eq("poetry_collections.slug", collectionSlug)
        .order("created_at", { ascending: true })

    if (error) throw error
    return data
}

/**
 * Fetch published poems by collection slug with pagination
 * @param {string} collectionSlug
 * @param {number} page - 1-based page number
 * @param {number} pageSize - items per page
 * @returns {{ data: Array, count: number }}
 */
export async function getPublishedPoemsByCollectionPaginated(collectionSlug, page = 1, pageSize = 12) {
    const from = (page - 1) * pageSize
    const to = from + pageSize - 1

    const { data, error, count } = await supabase
        .from("poems")
        .select(`
            id,
            title,
            slug,
            excerpt,
            poetry_collections!inner(slug)
        `, { count: "exact" })
        .eq("is_published", true)
        .eq("poetry_collections.slug", collectionSlug)
        .order("created_at", { ascending: true })
        .range(from, to)

    if (error) throw error
    return { data, count }
}
