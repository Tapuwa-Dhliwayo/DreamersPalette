import { useEffect, useState } from "react"
import { useParams, useMatch } from "react-router-dom"

import {
    getCollectionBySlug,
    getCollectionById,
    getPublishedPoemBySlug,
    getPublishedPoemsByCollection
} from "@/services/contentService"

export function useReaderNavigation() {
    const { slug } = useParams()

    // Route classification
    const isCollectionsIndex = useMatch("/collections")
    const isCollectionDetail = useMatch("/collections/:slug")
    const isPoemRoute = useMatch("/poems/:slug")

    const [level, setLevel] = useState("none")
    const [collection, setCollection] = useState(null)
    const [previous, setPrevious] = useState(null)
    const [next, setNext] = useState(null)

    useEffect(() => {
        let isMounted = true

        async function resolveNavigation() {
            try {
                // -----------------------------
                // Level 1 — Collections Index
                // -----------------------------
                if (isCollectionsIndex && !isCollectionDetail) {
                    if (!isMounted) return
                    setLevel("index")
                    setCollection(null)
                    setPrevious(null)
                    setNext(null)
                    return
                }

                // -----------------------------
                // Level 2 — Collection Detail
                // -----------------------------
                if (isCollectionDetail) {
                    const col = await getCollectionBySlug(slug)
                    if (!isMounted) return

                    setLevel("collection")
                    setCollection(col)
                    setPrevious(null)
                    setNext(null)
                    return
                }

                // -----------------------------
                // Level 3 — Poem
                // -----------------------------
                if (isPoemRoute) {
                    const poem = await getPublishedPoemBySlug(slug)
                    if (!poem || !isMounted) return

                    const col = await getCollectionById(poem.collection_id)
                    if (!col || !isMounted) return

                    const poems = await getPublishedPoemsByCollection(col.slug)

                    const index = poems.findIndex(p => p.slug === poem.slug)

                    const prev =
                        index > 0 ? poems[index - 1] : null

                    const nxt =
                        index >= 0 && index < poems.length - 1
                            ? poems[index + 1]
                            : null

                    if (!isMounted) return

                    setLevel("poem")
                    setCollection(col)
                    setPrevious(prev)
                    setNext(nxt)
                    return
                }

                // Default fallback
                if (!isMounted) return
                setLevel("none")
                setCollection(null)
                setPrevious(null)
                setNext(null)

            } catch (error) {
                console.error("Reader navigation error:", error)
            }
        }

        resolveNavigation()

        return () => {
            isMounted = false
        }
    }, [slug, isCollectionsIndex, isCollectionDetail, isPoemRoute])

    return {
        level,
        collection,
        previous,
        next
    }
}