import { useEffect, useState } from "react"
import { useParams } from "react-router-dom"
import { getCollectionBySlug } from "@/services/contentService"

export default function CollectionDetailPage() {
    const { slug } = useParams()
    const [collection, setCollection] = useState(null)
    const [loading, setLoading] = useState(true)
    const [notFound, setNotFound] = useState(false)

    useEffect(() => {
        async function load() {
            try {
                const data = await getCollectionBySlug(slug)
                setCollection(data)
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
        <article className="space-y-10">

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

            {/* Placeholder for future poem list */}
            <section className="pt-10 border-t border-neutral-200 dark:border-neutral-800">
                <p className="text-neutral-500">
                    Poems coming soon.
                </p>
            </section>

        </article>
    )
}