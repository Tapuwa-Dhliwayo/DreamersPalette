import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { Card } from "@/components/ui/Card"
import { getPublishedCollections } from "@/services/contentService"
import { PUBLIC_ROUTES } from "@/app/routes"

export default function CollectionsPage() {
    const [collections, setCollections] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getPublishedCollections()
                setCollections(data)
            } catch (err) {
                console.error("Failed to load collections:", err)
            } finally {
                setLoading(false)
            }
        }

        load()
    }, [])

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
        <div className="space-y-10">

            <header className="space-y-3">
                <h1 className="text-3xl font-semibold tracking-tight">
                    Collections
                </h1>
                <p className="text-neutral-500">
                    Explore curated poetic worlds.
                </p>
            </header>

            <div className="grid gap-8">
                {collections.map((collection) => (
                    <Link
                        key={collection.id}
                        to={PUBLIC_ROUTES.COLLECTION_DETAIL(collection.slug)}
                        className="block"
                    >
                        <Card className="transition hover:shadow-md cursor-pointer">

                            <h3 className="text-xl font-medium">
                                {collection.title}
                            </h3>

                            {collection.description && (
                                <p className="mt-3 text-neutral-600 dark:text-neutral-400">
                                    {collection.description}
                                </p>
                            )}

                        </Card>
                    </Link>
                ))}
            </div>

        </div>
    )
}