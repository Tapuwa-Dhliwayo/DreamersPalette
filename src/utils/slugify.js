/**
 * Generate URL-safe slug
 * - lowercase
 * - trim whitespace
 * - remove special characters
 * - collapse multiple spaces/dashes
 */
export function slugify(input) {
    if (!input) return ""

    return input
        .toLowerCase()
        .trim()
        .normalize("NFKD") // handle accents
        .replace(/[\u0300-\u036f]/g, "") // remove diacritics
        .replace(/[^a-z0-9\s-]/g, "") // remove special chars
        .replace(/\s+/g, "-") // spaces -> hyphen
        .replace(/-+/g, "-") // collapse multiple hyphens
}