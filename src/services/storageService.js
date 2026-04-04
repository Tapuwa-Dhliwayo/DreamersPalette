import { supabase } from "./supabaseClient"

/* ================================
   IMAGE UPLOAD CONSTRAINTS
================================ */

const ALLOWED_IMAGE_TYPES = ["image/jpeg", "image/png", "image/webp", "image/gif"]
const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024 // 5 MB
const MAX_IMAGE_DIMENSION = 4096 // px
const COMPRESSION_TARGET_BYTES = 2 * 1024 * 1024 // compress if > 2 MB
const BACKGROUNDS_BUCKET = "backgrounds"
const IMAGE_CACHE_CONTROL = "31536000"

/**
 * Validate an image file before upload.
 * Throws a user-friendly Error when constraints are violated.
 *
 * @param {File} file
 * @returns {Promise<void>}
 */
export async function validateImageFile(file) {
    if (!file) throw new Error("No file selected.")

    // Type check
    if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
        throw new Error(
            `Unsupported file type "${file.type}". Accepted: JPEG, PNG, WebP, GIF.`
        )
    }

    // Size check
    if (file.size > MAX_FILE_SIZE_BYTES) {
        const sizeMB = (file.size / (1024 * 1024)).toFixed(1)
        throw new Error(
            `File is too large (${sizeMB} MB). Maximum allowed size is 5 MB.`
        )
    }

    // Dimension check
    const dimensions = await getImageDimensions(file)
    if (
        dimensions.width > MAX_IMAGE_DIMENSION ||
        dimensions.height > MAX_IMAGE_DIMENSION
    ) {
        throw new Error(
            `Image dimensions (${dimensions.width}×${dimensions.height}) exceed the ${MAX_IMAGE_DIMENSION}px limit.`
        )
    }
}

/**
 * Read image dimensions from a File without rendering it visibly.
 *
 * @param {File} file
 * @returns {Promise<{width: number, height: number}>}
 */
function getImageDimensions(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file)
        const img = new Image()
        img.onload = () => {
            URL.revokeObjectURL(url)
            resolve({ width: img.naturalWidth, height: img.naturalHeight })
        }
        img.onerror = () => {
            URL.revokeObjectURL(url)
            reject(new Error("Unable to read image dimensions."))
        }
        img.src = url
    })
}

async function createImageVariant(file, opts = {}) {
    const compressible = ["image/jpeg", "image/png", "image/webp"]
    if (!compressible.includes(file.type)) {
        return null
    }

    const maxDimension = opts.maxDimension ?? 768
    const quality = opts.quality ?? 0.72

    const { width, height } = await getImageDimensions(file)

    let targetWidth = width
    let targetHeight = height

    if (width > maxDimension || height > maxDimension) {
        const ratio = Math.min(maxDimension / width, maxDimension / height)
        targetWidth = Math.round(width * ratio)
        targetHeight = Math.round(height * ratio)
    }

    const bitmap = await createImageBitmap(file)
    const canvas = new OffscreenCanvas(targetWidth, targetHeight)
    const ctx = canvas.getContext("2d")
    ctx.drawImage(bitmap, 0, 0, targetWidth, targetHeight)
    bitmap.close()

    const blob = await canvas.convertToBlob({ type: "image/webp", quality })
    const extensionIndex = file.name.lastIndexOf(".")
    const baseName = extensionIndex > 0
        ? file.name.slice(0, extensionIndex)
        : file.name
    const variantName = `${baseName}-preview.webp`

    return new File([blob], variantName, {
        type: "image/webp",
        lastModified: Date.now()
    })
}

/**
 * Compress / resize an image on the client using a canvas.
 * Returns a new File that is within COMPRESSION_TARGET_BYTES if possible.
 * Falls back to the original file if compression is unnecessary.
 *
 * @param {File} file
 * @param {Object} [opts]
 * @param {number} [opts.maxDimension] - scale down the longest side (default: 2048)
 * @param {number} [opts.quality]      - JPEG/WebP quality 0-1 (default: 0.82)
 * @returns {Promise<{file: File, wasCompressed: boolean}>}
 */
export async function compressImageIfNeeded(file, opts = {}) {
    // Only compress JPEG, PNG, WebP — skip GIF (animated)
    const compressible = ["image/jpeg", "image/png", "image/webp"]
    if (!compressible.includes(file.type) || file.size <= COMPRESSION_TARGET_BYTES) {
        return { file, wasCompressed: false }
    }

    const maxDimension = opts.maxDimension ?? 2048
    const quality = opts.quality ?? 0.82

    const { width, height } = await getImageDimensions(file)

    let targetWidth = width
    let targetHeight = height

    if (width > maxDimension || height > maxDimension) {
        const ratio = Math.min(maxDimension / width, maxDimension / height)
        targetWidth = Math.round(width * ratio)
        targetHeight = Math.round(height * ratio)
    }

    const bitmap = await createImageBitmap(file)
    const canvas = new OffscreenCanvas(targetWidth, targetHeight)
    const ctx = canvas.getContext("2d")
    ctx.drawImage(bitmap, 0, 0, targetWidth, targetHeight)
    bitmap.close()

    // Output as WebP for best compression; fall back to JPEG
    const outputType = "image/webp"
    const blob = await canvas.convertToBlob({ type: outputType, quality })

    const ext = "webp"
    const compressedName = file.name.replace(/\.[^.]+$/, "") + `.${ext}`

    const compressedFile = new File([blob], compressedName, {
        type: outputType,
        lastModified: Date.now()
    })

    return { file: compressedFile, wasCompressed: true }
}

/* ================================
   UPLOAD
================================ */

export async function uploadBackgroundImage(file, userId) {
    const fileExt = file.name.split(".").pop()
    const fileName = `${Date.now()}.${fileExt}`
    const filePath = `${userId}/${fileName}`

    const { error } = await supabase.storage
        .from(BACKGROUNDS_BUCKET)
        .upload(filePath, file, {
            upsert: false,
            cacheControl: IMAGE_CACHE_CONTROL
        })

    if (error) throw error

    const { data } = supabase.storage
        .from(BACKGROUNDS_BUCKET)
        .getPublicUrl(filePath)

    return data.publicUrl
}

export function getCollectionPreviewImageUrl(publicUrl) {
    if (!publicUrl) return null

    const [baseUrl, queryString] = publicUrl.split("?")
    if (!baseUrl.match(/\.(jpe?g|png|webp)$/i)) {
        return null
    }
    const previewBaseUrl = baseUrl.replace(/(\.[^.]+)$/i, "-preview.webp")

    return queryString ? `${previewBaseUrl}?${queryString}` : previewBaseUrl
}

export async function uploadCollectionBackgroundImage(file, userId) {
    const baseName = `${Date.now()}`
    const fileExt = file.name.split(".").pop()
    const fullPath = `${userId}/${baseName}.${fileExt}`
    const previewFile = await createImageVariant(file, {
        maxDimension: 640,
        quality: 0.68
    })
    const previewPath = previewFile ? `${userId}/${baseName}-preview.webp` : null

    const uploads = [
        supabase.storage
            .from(BACKGROUNDS_BUCKET)
            .upload(fullPath, file, {
                upsert: false,
                cacheControl: IMAGE_CACHE_CONTROL
            })
    ]

    if (previewFile && previewPath) {
        uploads.push(
            supabase.storage
                .from(BACKGROUNDS_BUCKET)
                .upload(previewPath, previewFile, {
                    upsert: false,
                    cacheControl: IMAGE_CACHE_CONTROL
                })
        )
    }

    const results = await Promise.all(uploads)
    const uploadError = results.find((result) => result.error)?.error
    if (uploadError) throw uploadError

    const { data: fullData } = supabase.storage
        .from(BACKGROUNDS_BUCKET)
        .getPublicUrl(fullPath)

    return {
        fullUrl: fullData.publicUrl,
        previewUrl: previewPath
            ? supabase.storage.from(BACKGROUNDS_BUCKET).getPublicUrl(previewPath).data.publicUrl
            : null
    }
}
