import { useEffect, useState } from "react"
import { useParams, useMatch } from "react-router-dom"
import {
    getCollectionBySlug,
    getPublishedPoemBySlug,
} from "@/services/contentService"

export function useCollectionTheme() {
    const { slug } = useParams()

    const isCollectionRoute = useMatch("/collections/:slug")
    const isPoemRoute = useMatch("/poems/:slug")

    const [backgroundUrl, setBackgroundUrl] = useState(null)
    const [overlayColor, setOverlayColor] = useState("rgba(0,0,0,0.6)")
    const [accentColor, setAccentColor] = useState("#d4d4d8")

    useEffect(() => {
        let isMounted = true

        async function resolveTheme() {
            try {
                let collection = null

                // Collection Page
                if (isCollectionRoute) {
                    collection = await getCollectionBySlug(slug)
                }

                // Poem Page
                if (isPoemRoute) {
                    const poem = await getPublishedPoemBySlug(slug)
                    if (poem?.collection?.slug) {
                        collection = await getCollectionBySlug(
                            poem.collection.slug
                        )
                    }
                }

                if (!collection || !isMounted) return

                const {
                    theme_background_url,
                    theme_overlay_opacity,
                    accent_color,
                } = collection

                const opacity =
                    typeof theme_overlay_opacity === "number"
                        ? theme_overlay_opacity
                        : 0.6

                const accent = accent_color || "#d4d4d8"

                if (!isMounted) return

                setBackgroundUrl(theme_background_url || null)
                setAccentColor(accent)
                setOverlayColor(
                    `rgba(0, 0, 0, ${opacity})`
                )
            } catch (error) {
                console.error("Theme resolution error:", error)
            }
        }

        resolveTheme()

        return () => {
            isMounted = false
        }
    }, [slug, isCollectionRoute, isPoemRoute])

    return {
        backgroundUrl,
        overlayColor,
        accentColor,
    }
}