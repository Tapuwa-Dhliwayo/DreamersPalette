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
        .select("*")
        .order("created_at", { ascending: false })

    if (error) throw error
    return data
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
 * Fetch single collection by slug (published only)
 */
export async function getCollectionBySlug(slug) {
    const { data, error } = await supabase
        .from("poetry_collections")
        .select("*")
        .eq("slug", slug)
        .eq("is_published", true)
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
        .select("*")
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
        .select("*")
        .order("created_at", { ascending: false })

    if (error) throw error
    return data
}

/**
 * Get poems by collection (author view)
 */
export async function getPoemsByCollection(collectionId) {
    const { data, error } = await supabase
        .from("poems")
        .select("*")
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
        .select("*")
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