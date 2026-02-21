import { useActiveCollection } from "./useActiveCollection"

export function useCollectionTheme() {
    const collection = useActiveCollection()

    const backgroundUrl = collection?.theme_background_url || null
    const textMode = collection?.theme_text_mode || "light"
    const accentColor = collection?.accent_color || null
    const opacity = collection?.theme_overlay_opacity ?? 0.65

    let overlayColor = null

    if (backgroundUrl) {
        overlayColor =
            textMode === "light"
                ? `rgba(0,0,0,${opacity})`
                : `rgba(255,255,255,${opacity})`
    }

    return {
        accentColor,
        backgroundUrl,
        overlayColor,
        textMode
    }
}