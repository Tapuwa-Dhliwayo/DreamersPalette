import { supabase } from "./supabaseClient"

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
    const { data, error } = await supabase
        .from("poetry_collections")
        .insert([payload])
        .select()
        .single()

    if (error) throw error
    return data
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