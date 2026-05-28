const samplePoems = [
    {
        title: "After the Rain",
        excerpt: "A quiet line of light returns to the room."
    },
    {
        title: "Nocturne",
        excerpt: "The city lowers its voice and lets the stars speak."
    }
]

function resolveTheme(collection) {
    const backgroundUrl = collection?.theme_background_url || null
    const textMode = collection?.theme_text_mode || "light"
    const accentColor = collection?.accent_color || "#d4d4d8"
    const opacity = collection?.theme_overlay_opacity ?? 0.65

    const overlayColor = backgroundUrl
        ? textMode === "light"
            ? `rgba(0,0,0,${opacity})`
            : `rgba(255,255,255,${opacity})`
        : null

    return {
        accentColor,
        backgroundUrl,
        overlayColor,
        textMode,
        textTone: textMode === "light" ? "text-neutral-100" : "text-neutral-900",
        mutedTone: textMode === "light" ? "text-neutral-300" : "text-neutral-600",
        shadowClass: backgroundUrl
            ? textMode === "light" ? "reader-text-shadow-light" : "reader-text-shadow-dark"
            : ""
    }
}

export default function CollectionThemePreview({ collection }) {
    const theme = resolveTheme(collection)
    const title = collection?.title?.trim() || "Untitled Collection"
    const description = collection?.description?.trim() || "A short description of this poetic world appears here."

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h4 className="text-sm font-medium text-neutral-900">
                        Live collection preview
                    </h4>
                    <p className="mt-1 text-xs text-neutral-500">
                        Mirrors the reader background, overlay, accent, and text contrast.
                    </p>
                </div>
                <div
                    className="h-6 w-6 shrink-0 rounded-full border border-neutral-200"
                    style={{ backgroundColor: theme.accentColor }}
                    aria-label="Accent color preview"
                />
            </div>

            <div
                className="relative aspect-[4/5] min-h-[420px] overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-950 shadow-inner"
                style={{
                    "--accent-color": theme.accentColor,
                    "--reader-muted": theme.textMode === "light" ? "#d4d4d8" : "#525252"
                }}
            >
                <div
                    className="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-60"
                    style={{ backgroundImage: "url('/assets/global_atmosphere.png')" }}
                />

                <div className="absolute inset-0 bg-linear-to-b from-neutral-950/70 to-neutral-900/60" />

                {theme.backgroundUrl && (
                    <div
                        className="absolute inset-0 bg-cover bg-center bg-no-repeat transition-opacity duration-300"
                        style={{ backgroundImage: `url(${theme.backgroundUrl})` }}
                    />
                )}

                {theme.backgroundUrl && theme.overlayColor && (
                    <div
                        className="absolute inset-0 transition-colors duration-300"
                        style={{ backgroundColor: theme.overlayColor }}
                    />
                )}

                <div className={`relative z-10 flex h-full flex-col px-6 py-7 ${theme.textTone} ${theme.shadowClass}`}>
                    <div className="mb-8 flex items-center justify-between text-xs">
                        <span className="font-medium opacity-80">Dreamers Palette</span>
                        <span className="opacity-60">Collection</span>
                    </div>

                    <div className="flex flex-1 flex-col justify-center text-center">
                        <h1 className="font-serif text-3xl tracking-tight accent-underline">
                            {title}
                        </h1>
                        <p className={`mx-auto mt-5 max-w-xs text-sm leading-relaxed opacity-75 ${theme.mutedTone}`}>
                            {description}
                        </p>
                    </div>

                    <div className="border-t border-white/15 pt-5">
                        <div className="space-y-4">
                            {samplePoems.map((poem) => (
                                <div key={poem.title} className="space-y-1">
                                    <h2 className="font-serif text-lg leading-snug">
                                        {poem.title}
                                    </h2>
                                    <p className={`text-xs leading-relaxed opacity-70 ${theme.mutedTone}`}>
                                        {poem.excerpt}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
