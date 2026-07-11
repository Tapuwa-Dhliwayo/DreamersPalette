import { useActiveCollection } from "./useActiveCollection"

export function useCollectionTheme() {
    const collection = useActiveCollection()

    const backgroundUrl = collection?.theme_background_url || null
    const textMode = collection?.theme_text_mode || "light"
    const accentColor = collection?.accent_color || null
    const opacity = collection?.theme_overlay_opacity ?? 0.65
    const defaultTextColor = textMode === "light" ? "#f5f5f5" : "#171717"
    const defaultMutedColor = textMode === "light" ? "#a3a3a3" : "#737373"
    const textColor = collection?.theme_text_color || defaultTextColor
    const headingColor = collection?.theme_heading_color || defaultTextColor
    const mutedColor = collection?.theme_muted_color || defaultMutedColor

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
        headingColor,
        mutedColor,
        overlayColor,
        textColor,
        textMode
    }
}
