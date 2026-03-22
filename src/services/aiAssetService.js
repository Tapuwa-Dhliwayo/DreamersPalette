import { supabase } from "./supabaseClient"

const PROMPT_MAX_LENGTH = 500

// ---------------------------------------------------------------------------
// Validation (fast client-side check before network call)
// ---------------------------------------------------------------------------
function validatePrompt(prompt) {
    const normalized = prompt?.trim()
    if (!normalized) throw new Error("Prompt is required.")
    if (normalized.length > PROMPT_MAX_LENGTH)
        throw new Error(`Prompt must be ${PROMPT_MAX_LENGTH} characters or fewer.`)
    return normalized
}

// ---------------------------------------------------------------------------
// Provider label (kept for UI display)
// ---------------------------------------------------------------------------
export function getAiProviderLabel() {
    return "Hugging Face"
}

// ---------------------------------------------------------------------------
// Core — calls the Edge Function
// ---------------------------------------------------------------------------
async function generateAndStoreAsset({ prompt, type }) {
    const normalizedPrompt = validatePrompt(prompt)

    const { data, error } = await supabase.functions.invoke("generate-asset", {
        body: { prompt: normalizedPrompt, type },
    })

    if (error) {
        // `error` from supabase.functions.invoke is a FunctionsHttpError / FunctionsRelayError
        const message =
            typeof data?.error === "string"
                ? data.error
                : error.message || "Image generation failed."
        throw new Error(message)
    }

    if (!data?.imageUrl) {
        throw new Error("No image URL returned from generation service.")
    }

    return {
        provider: data.provider,
        imageUrl: data.imageUrl,
        asset: data.asset,
    }
}

// ---------------------------------------------------------------------------
// Public API (unchanged signatures — nothing else needs to change)
// ---------------------------------------------------------------------------
export async function generateCollectionBackground(prompt) {
    return generateAndStoreAsset({ prompt, type: "background" })
}

export async function generateBookCover(prompt) {
    return generateAndStoreAsset({ prompt, type: "cover" })
}
