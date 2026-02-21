import { useEffect, useState } from "react"
import { useMatch, useParams } from "react-router-dom"
import {
    getCollectionBySlug,
    getCollectionById,
    getPublishedPoemBySlug
} from "@/services/contentService"

export function useCollectionTheme() {
    const { slug } = useParams()

    const isCollectionDetail = useMatch("/collections/:slug")
    const isPoemRoute = useMatch("/poems/:slug")

    const [accentColor, setAccentColor] = useState(null)
    const [backgroundUrl, setBackgroundUrl] = useState(null)
    const [overlayColor, setOverlayColor] = useState(null)
    const [textMode, setTextMode] = useState("light")

    useEffect(() => {
        let isMounted = true

        async function resolveTheme() {
            try {
                let collection = null

                // Collection Detail
                if (isCollectionDetail) {
                    collection = await getCollectionBySlug(slug)
                }

                // Poem Route
                if (isPoemRoute) {
                    const poem = await getPublishedPoemBySlug(slug)
                    if (poem) {
                        collection = await getCollectionById(poem.collection_id)
                    }
                }

                // 🚨 RESET IF NOT A THEMED ROUTE
                if (!isCollectionDetail && !isPoemRoute) {
                    if (!isMounted) return

                    setAccentColor(null)
                    setBackgroundUrl(null)
                    setOverlayColor(null)
                    setTextMode("light")
                    return
                }

                if (!collection || !isMounted) return

                const accentColor = collection.accent_color || null
                const mode = collection.theme_text_mode || "light"
                const opacity = collection.theme_overlay_opacity ?? 0.65

                setAccentColor(accentColor)
                setTextMode(mode)
                setBackgroundUrl(collection.theme_background_url || null)

                if (collection.theme_background_url) {
                    const overlay =
                        mode === "light"
                            ? `rgba(0,0,0,${opacity})`
                            : `rgba(255,255,255,${opacity})`

                    setOverlayColor(overlay)
                } else {
                    setOverlayColor(null)
                }

            } catch (error) {
                console.error("Theme resolution error:", error)
            }
        }

        resolveTheme()

        return () => {
            isMounted = false
        }
    }, [slug, isCollectionDetail, isPoemRoute])

    return {
        accentColor,
        backgroundUrl,
        overlayColor,
        textMode
    }
}