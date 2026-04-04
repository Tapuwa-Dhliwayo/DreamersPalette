import { useEffect, useMemo, useState } from "react"
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
    const [currentSrc, setCurrentSrc] = useState(previewSrc || src)

    useEffect(() => {
        setCurrentSrc(previewSrc || src)
    }, [previewSrc, src])

    if (!src) return null

    return (
        <img
            src={currentSrc}
            alt={alt}
            loading={priority ? "eager" : "lazy"}
            decoding="async"
            fetchPriority={priority ? "high" : "auto"}
            onError={() => {
                if (currentSrc !== src) {
                    setCurrentSrc(src)
                }
            }}
            className={className}
            {...props}
        />
    )
}
