import { supabase } from "./supabaseClient"

/**
 * Fetch currently authenticated user's profile
 * RLS enforced via auth.uid() = id
 */
export async function getMyProfile() {
    const { data, error } = await supabase
        .from("profiles")
        .select("id, display_name, role, avatar_url")
        .single()

    if (error) {
        throw error
    }

    return data
}