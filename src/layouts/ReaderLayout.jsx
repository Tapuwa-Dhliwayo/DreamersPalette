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

    const textTone =
        textMode === "dark"
            ? "text-neutral-900"
            : "text-neutral-100"

    return (
        <div
            className={`relative min-h-screen ${textTone}`}
            style={{
                "--accent-color": accentColor || "#cbd5e1"
            }}
        >
            {/* ---------------- GLOBAL ATMOSPHERIC BACKGROUND ---------------- */}
            <div
                className="fixed inset-0 -z-20 bg-cover bg-center bg-no-repeat opacity-60"
                style={{ backgroundImage: "url('/assets/global_atmosphere.png')" }}
            />

            {/* Soft atmospheric fade */}
            <div className="fixed inset-0 -z-10 bg-gradient-to-b from-neutral-900/40 to-neutral-900/60" />

            {/* ---------------- FRAMED WORLD ---------------- */}
            <div className="relative max-w-5xl mx-auto min-h-screen">

                {/* Collection Background (if exists) */}
                {backgroundUrl && (
                    <div
                        className="absolute inset-0 bg-cover bg-center bg-no-repeat rounded-3xl transition-opacity duration-500"
                        style={{ backgroundImage: `url(${backgroundUrl})` }}
                    />
                )}

                {/* Collection Overlay */}
                {backgroundUrl && overlayColor && (
                    <div
                        className="absolute inset-0 rounded-3xl transition-colors duration-500"
                        style={{ backgroundColor: overlayColor }}
                    />
                )}

                {/* Content Layer */}
                <div className="relative z-10">

                    <div className="max-w-3xl mx-auto px-6 pt-12">
                        <ReaderNavigation />
                    </div>

                    <main className="max-w-3xl mx-auto px-6 pb-32">
                        <Outlet />
                    </main>

                </div>
            </div>
        </div>
    )
}