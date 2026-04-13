import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { Card } from "@/components/ui/Card"
import Pagination, { DEFAULT_PAGE_SIZE } from "@/components/ui/Pagination"
import { getPublishedCollectionsPaginated } from "@/services/contentService"
import { PUBLIC_ROUTES } from "@/app/routes"

export default function CollectionsPage() {
    const [mounted, setMounted] = useState(false)
    const [collections, setCollections] = useState([])
    const [loading, setLoading] = useState(true)
    const [page, setPage] = useState(1)
    const [totalCount, setTotalCount] = useState(0)

    useEffect(() => {
        async function load() {
            try {
                setLoading(true)
                // Reset mounted state to retrigger fade-in animations on page change
                setMounted(false)
                const { data, count } = await getPublishedCollectionsPaginated(page, DEFAULT_PAGE_SIZE)
                setCollections(data)
                setTotalCount(count)
            } catch (err) {
                console.error("Failed to load collections:", err)
            } finally {
                setLoading(false)

                // allow next paint before animating
                requestAnimationFrame(() => {
                    setMounted(true)
                })
            }
        }

        load()
    }, [page])

    if (loading) {
        return (
            <div className="text-sm text-neutral-500">
                Loading collections...
            </div>
        )
    }

    if (!loading && collections.length === 0) {
        return (
            <div className="space-y-4 text-center py-20">
                <h2 className="text-2xl font-semibold tracking-tight">
                    No collections published yet
                </h2>
                <p className="text-neutral-500">
                    The library is still being written.
                </p>
            </div>
        )
    }

    return (
        <div className="space-y-16 pb-safe">

            {/* Header */}
            <header className="space-y-4 text-center pt-8">
                <h1 className="text-4xl font-serif tracking-tight">
                    Collections
                </h1>
                <p className="text-base opacity-70 leading-relaxed max-w-2xl mx-auto">
                    Enter curated poetic worlds shaped by atmosphere and memory.
                </p>
            </header>

            {/* Grid */}
            <div className="grid gap-10">

                {collections.map((collection, index) => (
                    <Link
                        key={collection.id}
                        to={PUBLIC_ROUTES.COLLECTION_DETAIL(collection.slug)}
                        className="block group no-underline! hover:no-underline!"
                    >
                        <div
                            className={`
                            relative overflow-hidden rounded-3xl
                           dark:bg-neutral-900/80 backdrop-blur-sm
                            transition-all duration-500 ease-out
                            shadow-sm group-hover:shadow-xl
                            group-hover:-translate-y-1
                            ${mounted
                                ? "opacity-100 translate-y-0"
                                : "opacity-0 translate-y-2"}
                        `}
                            style={{
                                transitionDelay: `${index * 70}ms`
                            }}
                        >

                            {/* Background Preview */}
                            {collection.theme_background_url && (
                                <div
                                    className="absolute inset-0 bg-cover bg-center opacity-30 group-hover:opacity-40 transition-opacity duration-500"
                                    style={{
                                        backgroundImage: `url(${collection.theme_background_url})`
                                    }}
                                />
                            )}

                            {/* Content Layer */}
                            <div className="relative z-10 p-6 md:p-10 space-y-4">

                                <h3 className="text-xl md:text-2xl font-serif tracking-tight group-hover:opacity-80 transition">
                                    {collection.title}
                                </h3>

                                {collection.description && (
                                    <p className="text-sm opacity-70 max-w-xl leading-relaxed">
                                        {collection.description}
                                    </p>
                                )}

                            </div>

                        </div>
                    </Link>
                ))}

            </div>

            {/* Pagination */}
            <Pagination
                page={page}
                pageSize={DEFAULT_PAGE_SIZE}
                totalCount={totalCount}
                onPageChange={setPage}
            />

        </div>
    )
}