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
        <div>

            {/* ---------------- HERO ---------------- */}
            <section className="relative py-12 md:py-24 text-center">

                {/* Top Right Login */}
                <div className="absolute top-2 right-2 md:top-0 md:right-0">
                    <Link
                        to={PUBLIC_ROUTES.LOGIN}
                        className="accent-button"
                    >
                        Author Login
                    </Link>
                </div>

                <Logo size="xl" stacked variant="subtle" />

                <p className="text-lg opacity-70 leading-relaxed max-w-2xl mx-auto mb-10">
                    A sanctuary for poetry, imagined worlds, and quiet reading.
                </p>

                <div className="flex justify-center gap-8 text-sm">
                    <Link to={PUBLIC_ROUTES.COLLECTIONS}>
                        Browse Collections
                    </Link>
                    <Link to={PUBLIC_ROUTES.BOOKS}>
                        Explore Books
                    </Link>
                </div>
            </section>

            {/* ---------------- FEATURED COLLECTIONS ---------------- */}
            <section className="py-16">
                <h2 className="text-2xl font-serif mb-8">
                    Featured Collections
                </h2>

                {loading && (
                    <p className="opacity-60 text-sm">Loading collections...</p>
                )}

                {!loading && collections.length === 0 && (
                    <p className="opacity-60 text-sm">
                        No published collections yet.
                    </p>
                )}

                {!loading && collections.length > 0 && (
                    <div className="grid gap-6 sm:grid-cols-2">
                        {collections.slice(0, 4).map((collection) => (
                            <Link
                                key={collection.id}
                                to={PUBLIC_ROUTES.COLLECTION_DETAIL(collection.slug)}
                                className="block rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden bg-white/85 dark:bg-neutral-900/80 backdrop-blur-sm"
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
                                    <h3 className="font-serif text-xl mb-2">
                                        {collection.title}
                                    </h3>
                                    {collection.description && (
                                        <p className="text-sm opacity-70 line-clamp-3">
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
                <h2 className="text-2xl font-serif mb-6">
                    Books
                </h2>

                <p className="opacity-60 text-sm">
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

            {/* ---------------- QUIET FOOTER NOTE ---------------- */}
            <section className="pt-24 text-center">
                <p className="text-sm opacity-50">
                    Every collection is a world.
                </p>
            </section>

        </div>
    )
}