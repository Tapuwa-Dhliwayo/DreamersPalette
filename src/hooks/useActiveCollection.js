import { useEffect, useState } from "react"
import { useMatch, useParams } from "react-router-dom"
import {
    getCollectionBySlug,
    getCollectionBySlugPreview,
    getCollectionById,
    getPublishedPoemBySlug
} from "@/services/contentService"

export function useActiveCollection() {
    const { slug } = useParams()

    const isCollectionDetail = useMatch("/collections/:slug")
    const isPoemRoute = useMatch("/poems/:slug")
    const isPreviewRoute = useMatch("/preview/collections/:slug")

    const [collection, setCollection] = useState(null)

    useEffect(() => {
        let isMounted = true

        async function resolve() {
            try {
                if (!isCollectionDetail && !isPoemRoute && !isPreviewRoute) {
                    setCollection(null)
                    return
                }

                let resolved = null

                if (isCollectionDetail) {
                    resolved = await getCollectionBySlug(slug)
                }

                if (isPreviewRoute) {
                    resolved = await getCollectionBySlugPreview(slug)
                }

                if (isPoemRoute) {
                    const poem = await getPublishedPoemBySlug(slug)
                    if (poem) {
                        resolved = await getCollectionById(poem.collection_id)
                    }
                }

                if (!isMounted) return
                setCollection(resolved || null)

            } catch (err) {
                console.error("Active collection error:", err)
            }
        }

        resolve()

        return () => { isMounted = false }
    }, [slug, isCollectionDetail, isPoemRoute, isPreviewRoute])

    return collection
}