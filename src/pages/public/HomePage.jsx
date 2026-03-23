import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { getPublishedCollections } from "@/services/contentService"
import { PUBLIC_ROUTES } from "@/app/routes"
import Logo from "@/components/ui/Logo.jsx";

export default function HomePage() {
    const [collections, setCollections] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadCollections() {
            try {
                const data = await getPublishedCollections()
                setCollections(data || [])
            } catch (err) {
                console.error("Failed to load collections:", err)
            } finally {
                setLoading(false)
            }
        }

        loadCollections()
    }, [])

    return (
        <div className="grid h-full min-h-0 grid-rows-[auto_1fr_auto]">

            {/* ---------------- FIXED TOP AREA ---------------- */}
            <section className="relative py-12 text-center md:py-20">
                <div className="absolute top-2 right-0 md:top-0 md:right-0">
                    <Link
                        to={PUBLIC_ROUTES.LOGIN}
                        className="accent-button"
                    >
                        Author Login
                    </Link>
                </div>

                <Logo size="xl" stacked variant="subtle" />

                <p className="mx-auto mb-10 max-w-2xl text-lg leading-relaxed opacity-70">
                    A sanctuary for poetry, imagined worlds, and quiet reading.
                </p>

                <div className="flex flex-wrap justify-center gap-8 text-sm">
                    <Link to={PUBLIC_ROUTES.COLLECTIONS}>
                        Browse Collections
                    </Link>
                    <Link to={PUBLIC_ROUTES.BOOKS}>
                        Explore Books
                    </Link>
                </div>
            </section>

            {/* ---------------- SCROLLABLE BODY ---------------- */}
            <div className="min-h-0 overflow-y-auto">
                <div className="space-y-12 md:space-y-16">

                    {/* ---------------- FEATURED COLLECTIONS ---------------- */}
                    <section className="py-8 md:pt-2 md:pb-16">
                        <h2 className="mb-8 text-2xl font-serif">
                            Featured Collections
                        </h2>

                        {loading && (
                            <p className="text-sm opacity-60">Loading collections...</p>
                        )}

                        {!loading && collections.length === 0 && (
                            <p className="text-sm opacity-60">
                                No published collections yet.
                            </p>
                        )}

                        {!loading && collections.length > 0 && (
                            <div className="grid gap-6 sm:grid-cols-2">
                                {collections.slice(0, 4).map((collection) => (
                                    <Link
                                        key={collection.id}
                                        to={PUBLIC_ROUTES.COLLECTION_DETAIL(collection.slug)}
                                        className="block overflow-hidden rounded-2xl bg-white/85 shadow-sm backdrop-blur-sm transition hover:shadow-md dark:bg-neutral-900/80"
                                    >
                                        {collection.theme_background_url && (
                                            <div
                                                className="h-40 bg-cover bg-center"
                                                style={{
                                                    backgroundImage: `url(${collection.theme_background_url})`
                                                }}
                                            />
                                        )}

                                        <div className="p-4 md:p-6">
                                            <h3 className="mb-2 font-serif text-xl">
                                                {collection.title}
                                            </h3>
                                            {collection.description && (
                                                <p className="line-clamp-3 text-sm opacity-70">
                                                    {collection.description}
                                                </p>
                                            )}
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>

                    {/* ---------------- BOOKS SECTION ---------------- */}
                    <section className="py-16">
                        <h2 className="mb-6 text-2xl font-serif">
                            Books
                        </h2>

                        <p className="text-sm opacity-60">
                            Books are arriving soon.
                        </p>

                        <div className="mt-6">
                            <Link
                                to={PUBLIC_ROUTES.BOOKS}
                                className="text-sm"
                            >
                                Visit Books →
                            </Link>
                        </div>
                    </section>

                    {/* ---------------- MOBILE FOOTER NOTE ---------------- */}
                    <section className="pt-8 text-center md:hidden">
                        <p className="text-sm opacity-50">
                            Every collection is a world.
                        </p>
                    </section>
                </div>
            </div>

            {/* ---------------- FIXED DESKTOP FOOTER NOTE ---------------- */}
            <section className="py-6 text-center md:block">
                <p className="text-sm opacity-50">
                    Every collection is a world.
                </p>
            </section>

        </div>
    )
}