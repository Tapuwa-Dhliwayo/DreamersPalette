import { supabase } from "./supabaseClient"

export async function uploadBackgroundImage(file, userId) {
    const fileExt = file.name.split(".").pop()
    const fileName = `${Date.now()}.${fileExt}`
    const filePath = `${userId}/${fileName}`

    const { error } = await supabase.storage
        .from("backgrounds")
        .upload(filePath, file, {
            upsert: false
        })

    if (error) throw error

    const { data } = supabase.storage
        .from("backgrounds")
        .getPublicUrl(filePath)

    return data.publicUrl
}