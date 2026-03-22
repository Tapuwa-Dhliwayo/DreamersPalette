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
            className={`relative min-h-screen text-neutral-100`}
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
            <div className="fixed inset-0 -z-10 bg-linear-to-b from-neutral-950/70 to-neutral-900/60" />

            {/* ---------------- FRAMED WORLD ---------------- */}
            <div className="relative max-w-full md:max-w-5xl mx-auto min-h-screen rounded-none md:rounded-3xl">

                {/* Collection Background (if exists) */}
                {backgroundUrl && (
                    <div
                        className="fixed inset-0 md:absolute md:inset-0 md:rounded-3xl bg-cover bg-center bg-no-repeat transition-opacity duration-500"
                        style={{ backgroundImage: `url(${backgroundUrl})` }}
                    />
                )}

                {/* Collection Overlay */}
                {backgroundUrl && overlayColor && (
                    <div
                        className="fixed inset-0 md:absolute md:inset-0 md:rounded-3xl transition-colors duration-500"
                        style={{ backgroundColor: overlayColor }}
                    />
                )}

                {/* Content Layer */}
                <div className="relative z-10">

                    <div className="max-w-3xl mx-auto px-4 md:px-6 pt-6 md:pt-12">
                        <ReaderNavigation />
                    </div>

                    <main className="max-w-3xl mx-auto px-4 md:px-6 pb-32 transition-opacity duration-300">
                        <Outlet />
                    </main>

                </div>
            </div>
        </div>
    )
}