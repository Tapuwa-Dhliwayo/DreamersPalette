import { Link } from "react-router-dom"
import { PUBLIC_ROUTES } from "@/app/routes"

export default function BooksPage() {
    return (
        <div className="space-y-20 pt-12">

            {/* Header */}
            <header className="space-y-6 text-center">
                <h1 className="text-5xl font-serif tracking-tight">
                    Books
                </h1>

                <p className="text-lg opacity-70 max-w-2xl mx-auto leading-relaxed">
                    Long-form works are being shaped in quiet rooms.
                    Chapters will arrive soon.
                </p>
            </header>

            {/* Coming Soon Surface */}
            <section className="flex justify-center">
                <div className="
                    rounded-3xl
                    bg-neutral-400/85 dark:bg-neutral-900/80
                    backdrop-blur-md
                    shadow-xl
                    px-12 py-16
                    text-center
                    space-y-6
                    max-w-xl
                ">

                    <p className="text-base opacity-70 leading-relaxed">
                        This space will soon host novels and extended works —
                        immersive journeys that unfold beyond a single poem.
                    </p>

                    <div className="pt-4">
                        <Link
                            to={PUBLIC_ROUTES.COLLECTIONS}
                            className="accent-button"
                        >
                            Explore Poetry Collections
                        </Link>
                    </div>

                </div>
            </section>

        </div>
    )
}