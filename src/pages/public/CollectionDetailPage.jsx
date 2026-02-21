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
        <article className="space-y-12">

            {/* Header */}
            <header className="space-y-4">
                <h1 className="text-4xl font-semibold tracking-tight">
                    {collection.title}
                </h1>

                {collection.description && (
                    <p className="text-lg text-neutral-600 dark:text-neutral-400 leading-relaxed">
                        {collection.description}
                    </p>
                )}
            </header>

            {/* Poems List */}
            <section className="pt-10 border-t border-neutral-200 dark:border-neutral-800 space-y-6">

                {poems.length === 0 && (
                    <p className="text-neutral-500">
                        No published poems yet.
                    </p>
                )}

                {poems.map((poem) => (
                    <Link
                        key={poem.id}
                        to={PUBLIC_ROUTES.POEM(poem.slug)}
                        className="block group transition-opacity duration-200"
                    >
                        <div className="space-y-2">
                            <h2 className="text-xl font-medium group-hover:opacity-70 transition">
                                {poem.title}
                            </h2>

                            {poem.excerpt && (
                                <p className="text-sm text-neutral-500 leading-relaxed">
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