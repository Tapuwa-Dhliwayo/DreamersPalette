import { supabase } from "./supabaseClient"

const DEFAULT_PROVIDER = "pollinations"
const DEFAULT_BUCKET = "backgrounds"
const PROMPT_MAX_LENGTH = 500
const DEFAULT_WIDTH = 1536
const DEFAULT_HEIGHT = 1024
const EXTENSION_BY_MIME_SUBTYPE = {
    jpeg: "jpg",
    png: "png",
    webp: "webp"
}

function validatePrompt(prompt) {
    const normalizedPrompt = prompt?.trim()

    if (!normalizedPrompt) {
        throw new Error("Prompt is required.")
    }

    if (normalizedPrompt.length > PROMPT_MAX_LENGTH) {
        throw new Error(`Prompt must be ${PROMPT_MAX_LENGTH} characters or fewer.`)
    }

    return normalizedPrompt
}

function getProvider() {
    return (import.meta.env.VITE_AI_IMAGE_PROVIDER || DEFAULT_PROVIDER).toLowerCase()
}

export function getAiProviderLabel() {
    const provider = getProvider()
    return provider.charAt(0).toUpperCase() + provider.slice(1)
}

function getBucketName() {
    return import.meta.env.VITE_GENERATED_ASSETS_BUCKET || DEFAULT_BUCKET
}

function buildPollinationsUrl(prompt, type) {
    const stylePrompt = type === "background"
        ? `${prompt}, cinematic atmospheric literary background, no text`
        : `${prompt}, elegant literary book cover illustration, no text`
    const encodedPrompt = encodeURIComponent(stylePrompt)

    return `https://image.pollinations.ai/prompt/${encodedPrompt}?width=${DEFAULT_WIDTH}&height=${DEFAULT_HEIGHT}&nologo=true&private=true`
}

async function fetchGeneratedImage(prompt, type) {
    const provider = getProvider()

    if (provider !== DEFAULT_PROVIDER) {
        throw new Error(`Unsupported AI provider "${provider}".`)
    }

    const sourceUrl = buildPollinationsUrl(prompt, type)
    const response = await fetch(sourceUrl)

    if (!response.ok) {
        throw new Error("Image generation request failed.")
    }

    const blob = await response.blob()
    return { blob, provider }
}

async function uploadAssetBlob(blob, userId, type) {
    const mimeType = blob.type?.toLowerCase()
    if (!mimeType?.startsWith("image/")) {
        throw new Error("Generated asset is not a supported image type.")
    }

    const mimeSubtype = mimeType.split("/")[1]
    const extension = EXTENSION_BY_MIME_SUBTYPE[mimeSubtype]

    if (!extension) {
        throw new Error(`Unsupported generated image format "${mimeSubtype}".`)
    }
    const fileName = `${Date.now()}-${type}.${extension}`
    const filePath = `${userId}/${fileName}`
    const bucket = getBucketName()

    const { error: uploadError } = await supabase.storage
        .from(bucket)
        .upload(filePath, blob, {
            upsert: false,
            contentType: blob.type || "image/jpeg"
        })

    if (uploadError) {
        throw uploadError
    }

    const { data } = supabase.storage
        .from(bucket)
        .getPublicUrl(filePath)

    if (!data?.publicUrl) {
        throw new Error(`Could not resolve generated asset public URL for "${filePath}".`)
    }

    return data.publicUrl
}

async function saveGeneratedAssetRecord({
    userId,
    type,
    prompt,
    imageUrl
}) {
    const { data, error } = await supabase
        .from("generated_assets")
        .insert([
            {
                author_id: userId,
                type,
                prompt,
                image_url: imageUrl
            }
        ])
        .select("id, author_id, type, prompt, image_url, created_at")
        .single()

    if (error) {
        throw error
    }

    return data
}

async function generateAndStoreAsset({ prompt, type }) {
    const normalizedPrompt = validatePrompt(prompt)
    const {
        data: { user },
        error: userError
    } = await supabase.auth.getUser()

    if (userError || !user) {
        throw new Error("User not authenticated")
    }

    const { blob, provider } = await fetchGeneratedImage(normalizedPrompt, type)
    const imageUrl = await uploadAssetBlob(blob, user.id, type)
    const asset = await saveGeneratedAssetRecord({
        userId: user.id,
        type,
        prompt: normalizedPrompt,
        imageUrl
    })

    return {
        provider,
        imageUrl,
        asset
    }
}

export async function generateCollectionBackground(prompt) {
    return generateAndStoreAsset({ prompt, type: "background" })
}

export async function generateBookCover(prompt) {
    return generateAndStoreAsset({ prompt, type: "cover" })
}
