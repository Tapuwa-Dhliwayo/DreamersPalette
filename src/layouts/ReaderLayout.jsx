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

    const outerSurface =
        textMode === "dark"
            ? "bg-neutral-50 text-neutral-900"
            : "bg-neutral-950 text-neutral-100"

    return (
        <div
            className={`min-h-screen ${outerSurface}`}
            style={
                accentColor
                    ? { "--accent-color": accentColor }
                    : undefined
            }
        >

            <div className="relative max-w-5xl mx-auto min-h-screen">

                {backgroundUrl && (
                    <div
                        className="absolute inset-0 bg-cover bg-center bg-no-repeat rounded-3xl transition-opacity duration-500"
                        style={{ backgroundImage: `url(${backgroundUrl})` }}
                    />
                )}

                {backgroundUrl && overlayColor && (
                    <div
                        className="absolute inset-0 rounded-3xl transition-colors duration-500"
                        style={{ backgroundColor: overlayColor }}
                    />
                )}

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