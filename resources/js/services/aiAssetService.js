import { apiRequest } from "./_shared/api";

export const getAiProviderLabel = () => "Hugging Face";
async function generateAndStoreAsset(prompt, type) {
    const normalized = prompt?.trim();
    if (!normalized) throw new Error("Prompt is required.");
    if (normalized.length > 500) throw new Error("Prompt must be 500 characters or fewer.");
    return apiRequest("/api/studio/generated-assets", { method: "POST", body: { prompt: normalized, type } });
}
export const generateCollectionBackground = (prompt) => generateAndStoreAsset(prompt, "background");
export const generateBookCover = (prompt) => generateAndStoreAsset(prompt, "cover");
