import { Outlet } from "react-router-dom"
import { useCollectionTheme } from "@/hooks/useCollectionTheme"
import ReaderNavigation from "@/components/reader/ReaderNavigation"

export default function ReaderLayout() {
    const {
        backgroundUrl,
        overlayColor,
        textMode,
        accentColor
    } = useCollectionTheme()

    const isLight = textMode === "light"

    const textTone = isLight ? "text-neutral-100" : "text-neutral-900"
    const textShadowClass = backgroundUrl
        ? isLight ? "reader-text-shadow-light" : "reader-text-shadow-dark"
        : ""

    const stickyBg = isLight
        ? "bg-neutral-950/60"
        : "bg-white/60"

    return (
        <div
            className="relative h-frame overflow-hidden"
            style={{
                "--accent-color": accentColor || "#cbd5e1",
                "--reader-muted": isLight ? "#a3a3a3" : "#737373"
            }}
        >
            {/* ---------------- GLOBAL ATMOSPHERIC BACKGROUND ---------------- */}
            <div
                className="fixed inset-0 -z-20 bg-cover bg-center bg-no-repeat opacity-60"
                style={{ backgroundImage: "url('/assets/global_atmosphere.png')" }}
            />

            {/* Soft atmospheric fade */}
            <div className="fixed inset-0 -z-10 bg-linear-to-b from-neutral-950/70 to-neutral-900/60" />

            {/* ---------------- FRAMED WORLD ---------------- */}
            <div className="relative max-w-full md:max-w-5xl mx-auto h-full flex flex-col rounded-none md:rounded-3xl overflow-hidden">

                {/* Collection Background (if exists) */}
                {backgroundUrl && (
                    <div
                        className="absolute inset-0 md:rounded-3xl bg-cover bg-center bg-no-repeat transition-opacity duration-500"
                        style={{ backgroundImage: `url(${backgroundUrl})` }}
                    />
                )}

                {/* Collection Overlay */}
                {backgroundUrl && overlayColor && (
                    <div
                        className="absolute inset-0 md:rounded-3xl transition-colors duration-500"
                        style={{ backgroundColor: overlayColor }}
                    />
                )}

                {/* Scrollable Content */}
                <div className={`relative z-10 flex-1 overflow-y-auto ${textTone} ${textShadowClass}`}>

                    {/* Sticky Header */}
                    <div className={`sticky top-0 z-20 reader-sticky-header ${stickyBg}`}>
                        <div className="max-w-3xl mx-auto px-4 md:px-6 pt-4 pb-2 md:pt-6 md:pb-3">
                            <ReaderNavigation />
                        </div>
                    </div>

                    {/* Main Content */}
                    <main className="max-w-3xl mx-auto px-4 md:px-6 pb-safe transition-opacity duration-300">
                        <Outlet />
                    </main>

                </div>
            </div>
        </div>
    )
}