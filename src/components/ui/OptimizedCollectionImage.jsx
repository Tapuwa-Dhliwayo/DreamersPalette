import { useState } from "react"

const FALLBACK_IMAGE = "/assets/global_atmosphere.png"

export default function OptimizedCollectionImage({
    src,
    alt,
    className,
    priority = false,
    ...props
}) {
    const [failedSrc, setFailedSrc] = useState(null)

    if (!src) return null

    const resolvedSrc = failedSrc === src ? FALLBACK_IMAGE : src

    return (
        <img
            src={resolvedSrc}
            alt={alt}
            loading={priority ? "eager" : "lazy"}
            decoding="async"
            fetchPriority={priority ? "high" : "auto"}
            onError={resolvedSrc !== FALLBACK_IMAGE ? () => setFailedSrc(src) : undefined}
            className={className}
            {...props}
        />
    )
}
