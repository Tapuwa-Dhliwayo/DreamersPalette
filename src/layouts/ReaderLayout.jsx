import { Outlet } from "react-router-dom"
import { useCollectionTheme } from "@/hooks/useCollectionTheme"
import ReaderNavigation from "@/components/reader/ReaderNavigation"

export default function ReaderLayout() {
    const { backgroundUrl, overlayColor } = useCollectionTheme()

    return (
        <div className="relative min-h-screen text-foreground">

            {/* Background Layer */}
            {backgroundUrl && (
                <div
                    className="fixed inset-0 bg-cover bg-center bg-no-repeat"
                    style={{ backgroundImage: `url(${backgroundUrl})` }}
                />
            )}

            {/* Overlay Layer */}
            <div
                className="fixed inset-0"
                style={{ backgroundColor: overlayColor }}
            />

            {/* Reading Surface */}
            <div className="relative z-10">

                {/* Immersive Navigation */}
                <div className="max-w-3xl mx-auto px-6 pt-8">
                    <ReaderNavigation />
                </div>

                {/* Literary Content */}
                <main className="max-w-3xl mx-auto px-6 pb-24">
                    <Outlet />
                </main>

            </div>
        </div>
    )
}