import { useEffect, useState } from "react"
import { useParams, Link } from "react-router-dom"

import {
    getCollectionBySlug,
    getPublishedPoemsByCollection
} from "@/services/contentService"

import { PUBLIC_ROUTES } from "@/app/routes"

export default function CollectionDetailPage() {
    const { slug } = useParams()

    const [collection, setCollection] = useState(null)
    const [poems, setPoems] = useState([])
    const [loading, setLoading] = useState(true)
    const [notFound, setNotFound] = useState(false)

    useEffect(() => {
        async function load() {
            try {
                const collectionData = await getCollectionBySlug(slug)
                const poemsData = await getPublishedPoemsByCollection(slug)

                setCollection(collectionData)
                setPoems(poemsData)
            } catch (err) {
                console.error("Collection not found:", err)
                setNotFound(true)
            } finally {
                setLoading(false)
            }
        }

        load()
    }, [slug])

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
        <article className="space-y-20 pt-8">

            {/* Header */}
            <header className="space-y-6 text-center">

                <h1 className="text-5xl font-serif tracking-tight">
                    {collection.title}
                </h1>

                {collection.description && (
                    <p className="text-lg opacity-70 max-w-2xl mx-auto leading-relaxed">
                        {collection.description}
                    </p>
                )}
            </header>

            {/* Poems */}
            <section className="space-y-8 border-t border-white/10 pt-12">

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

                            <h2 className="text-2xl font-serif group-hover:opacity-70 transition">
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

        </article>
    )
}