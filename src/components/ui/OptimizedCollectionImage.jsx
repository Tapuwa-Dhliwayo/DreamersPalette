import { useMemo, useState } from "react"
import { getCollectionPreviewImageUrl } from "@/services/storageService"

export default function OptimizedCollectionImage({
    src,
    alt,
    className,
    usePreviewVariant = true,
    priority = false,
    ...props
}) {
    const previewSrc = useMemo(
        () => usePreviewVariant ? getCollectionPreviewImageUrl(src) : src,
        [src, usePreviewVariant]
    )
    const [failedPreviewSrc, setFailedPreviewSrc] = useState(null)

    if (!src) return null

    const hasPreviewFallback = failedPreviewSrc === src
    const resolvedSrc = hasPreviewFallback
        ? src
        : (previewSrc || src)

    return (
        <img
            src={resolvedSrc}
            alt={alt}
            loading={priority ? "eager" : "lazy"}
            decoding="async"
            fetchPriority={priority ? "high" : "auto"}
            onError={resolvedSrc !== src ? () => setFailedPreviewSrc(src) : undefined}
            className={className}
            {...props}
        />
    )
}
