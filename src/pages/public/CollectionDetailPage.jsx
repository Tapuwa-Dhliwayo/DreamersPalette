import { useEffect, useState } from "react"
import { useParams, Link } from "react-router-dom"

import {
    getCollectionBySlug,
    getPublishedPoemsByCollectionPaginated
} from "@/services/contentService"

import Pagination, { DEFAULT_PAGE_SIZE } from "@/components/ui/Pagination"
import { PUBLIC_ROUTES } from "@/app/routes"

export default function CollectionDetailPage() {
    const { slug } = useParams()

    const [collection, setCollection] = useState(null)
    const [poems, setPoems] = useState([])
    const [loading, setLoading] = useState(true)
    const [notFound, setNotFound] = useState(false)
    const [page, setPage] = useState(1)
    const [totalCount, setTotalCount] = useState(0)

    useEffect(() => {
        async function loadCollection() {
            try {
                const collectionData = await getCollectionBySlug(slug)
                setCollection(collectionData)
            } catch (err) {
                console.error("Collection not found:", err)
                setNotFound(true)
            }
        }

        setPage(1)
        setNotFound(false)
        loadCollection()
    }, [slug])

    useEffect(() => {
        if (!collection) return

        async function loadPoems() {
            try {
                setLoading(true)
                const { data: poemsData, count } = await getPublishedPoemsByCollectionPaginated(slug, page, DEFAULT_PAGE_SIZE)
                setPoems(poemsData)
                setTotalCount(count)
            } catch (err) {
                console.error("Failed to load poems:", err)
            } finally {
                setLoading(false)
            }
        }

        loadPoems()
    }, [slug, page, collection])

    if (loading) {
        return (
            <div className="text-sm text-neutral-500">
                Loading collection...
            </div>
        )
    }

    if (notFound || !collection) {
        return (
            <div className="py-20 text-center space-y-4">
                <h2 className="text-2xl font-semibold">
                    Collection not found
                </h2>
                <p className="text-neutral-500">
                    It may not be published yet.
                </p>
            </div>
        )
    }

    return (
        <article className="reader-fade-in space-y-12 md:space-y-20 pt-8 md:pt-12">

            {/* Header */}
            <header className="space-y-6 text-center">

                <h1 className="text-3xl md:text-5xl font-serif tracking-tight accent-underline">
                    {collection.title}
                </h1>

                {collection.description && (
                    <p className="text-base md:text-lg opacity-70 max-w-2xl mx-auto leading-relaxed">
                        {collection.description}
                    </p>
                )}
            </header>

            {/* Poems */}
            <section className="space-y-8 border-t border-white/10 pt-8 md:pt-12">

                {poems.length === 0 && (
                    <p className="opacity-60 text-center">
                        No published poems yet.
                    </p>
                )}

                {poems.map((poem) => (
                    <Link
                        key={poem.id}
                        to={PUBLIC_ROUTES.POEM(poem.slug)}
                        className="block group transition-all duration-200"
                    >
                        <div className="space-y-3">

                            <h2 className="text-2xl font-serif leading-relaxed group-hover:opacity-70 transition">
                                {poem.title}
                            </h2>

                            {poem.excerpt && (
                                <p className="text-sm opacity-60 leading-relaxed max-w-xl">
                                    {poem.excerpt}
                                </p>
                            )}

                        </div>
                    </Link>
                ))}

            </section>

            {/* Pagination */}
            <Pagination
                page={page}
                pageSize={DEFAULT_PAGE_SIZE}
                totalCount={totalCount}
                onPageChange={setPage}
            />

        </article>
    )
}