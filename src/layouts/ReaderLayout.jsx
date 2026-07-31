import { Outlet, useLocation } from "react-router-dom"
import { useCollectionTheme } from "@/hooks/useCollectionTheme"
import ReaderNavigation from "@/components/reader/ReaderNavigation"

export default function ReaderLayout() {
    const { pathname } = useLocation()
    const {
        backgroundUrl,
        overlayColor,
        textMode,
        accentColor,
        textColor,
        headingColor,
        mutedColor
    } = useCollectionTheme()

    const isHomePage = pathname === "/"
    const isLight = textMode === "light"

    const textShadowClass = backgroundUrl
        ? isLight ? "reader-text-shadow-light" : "reader-text-shadow-dark"
        : ""

    return (
        <div
            className={`reader-surface relative w-full ${isHomePage ? "min-h-dvh bg-[#080d16]" : "h-dvh overflow-hidden"}`}
            style={{
                "--accent-color": isHomePage ? "#aabed8" : accentColor || "#cbd5e1",
                "--reader-heading": isHomePage ? "#f4f4f2" : headingColor,
                "--reader-muted": isHomePage ? "#8e9aaa" : mutedColor,
                "--reader-text": isHomePage ? "#c1c8d2" : textColor
            }}
        >
            {/* ---------------- GLOBAL ATMOSPHERIC BACKGROUND ---------------- */}
            <div
                className={`fixed inset-0 bg-cover bg-center bg-no-repeat ${isHomePage ? "z-0 opacity-100" : "-z-20 opacity-60"}`}
                style={{ backgroundImage: "url('/assets/global_atmosphere.png')" }}
            />

            {/* Soft atmospheric fade */}
            <div className={`fixed inset-0 ${isHomePage ? "z-0 bg-[#080d16]/55" : "-z-10 bg-neutral-950/70"}`} />

            {/* ---------------- FRAMED WORLD ---------------- */}
            <div className={`relative mx-auto w-full max-w-full md:max-w-5xl ${isHomePage ? "z-10 min-h-dvh" : "flex h-full flex-col overflow-hidden rounded-none md:rounded-3xl"}`}>

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

                {/* Fixed Nav */}
                {!isHomePage && (
                <div className={`relative z-10 shrink-0 ${textShadowClass}`}>
                    <div className={`sticky top-0 z-20 reader-sticky-header`}>
                        <div className="max-w-3xl mx-auto px-3 sm:px-4 md:px-6 pt-3 pb-1.5 md:pt-6 md:pb-3">
                            <ReaderNavigation />
                        </div>
                    </div>
                </div>
                )}

                {/* Scrollable Outlet */}
                <div className={`relative z-10 ${isHomePage ? "pt-2" : "min-h-0 flex-1 overflow-y-auto"} ${textShadowClass}`}>
                    <main className={`mx-auto px-4 pb-safe transition-opacity duration-300 sm:px-5 md:px-6 ${isHomePage ? "max-w-5xl" : "h-full max-w-3xl"}`}>
                        <Outlet />
                    </main>
                </div>
            </div>
        </div>
    )
}
