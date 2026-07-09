import { useEffect, useMemo, useState } from "react"
import Button from "@/components/ui/Button"
import StatusMessage from "@/components/ui/StatusMessage"
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton"
import {
    getArchivedCollections,
    getArchivedPoems,
    restoreCollection,
    restorePoem
} from "@/services/contentService"
import {
    getArchivedBooks,
    getArchivedChapters,
    restoreBook,
    restoreChapter
} from "@/services/bookService"

const restoreActions = {
    collection: restoreCollection,
    poem: restorePoem,
    book: restoreBook,
    chapter: restoreChapter
}

function formatArchivedDate(value) {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short"
    }).format(new Date(value))
}

export default function TrashPage() {
    const [items, setItems] = useState([])
    const [loading, setLoading] = useState(true)
    const [pendingId, setPendingId] = useState("")
    const [error, setError] = useState("")
    const [status, setStatus] = useState("")

    const groupedItems = useMemo(
        () => items.reduce((groups, item) => {
            groups[item.type] = [...(groups[item.type] || []), item]
            return groups
        }, {}),
        [items]
    )

    useEffect(() => {
        loadTrash()
    }, [])

    async function loadTrash() {
        try {
            setLoading(true)
            setError("")
            const [collections, poems, books, chapters] = await Promise.all([
                getArchivedCollections(),
                getArchivedPoems(),
                getArchivedBooks(),
                getArchivedChapters()
            ])
            setItems([
                ...collections.map((item) => ({ ...item, type: "collection" })),
                ...poems.map((item) => ({ ...item, type: "poem" })),
                ...books.map((item) => ({ ...item, type: "book" })),
                ...chapters.map((item) => ({ ...item, type: "chapter" }))
            ].sort((a, b) => new Date(b.deleted_at) - new Date(a.deleted_at)))
        } catch (err) {
            setError(err.message || "Trash could not be loaded.")
        } finally {
            setLoading(false)
        }
    }

    async function handleRestore(item) {
        try {
            setPendingId(`${item.type}-${item.id}`)
            setError("")
            await restoreActions[item.type](item.id)
            setStatus(`“${item.title}” was restored as a draft.`)
            await loadTrash()
        } catch (err) {
            setError(err.message || "The item could not be restored.")
        } finally {
            setPendingId("")
        }
    }

    return (
        <div className="space-y-8">
            <header>
                <h2 className="text-2xl font-semibold tracking-tight">Trash</h2>
                <p className="mt-1 max-w-2xl text-sm text-neutral-600">
                    Archived work stays here until restored. Restored content returns as a draft and is never republished automatically.
                </p>
            </header>

            <StatusMessage tone="error" action={
                error ? <Button size="sm" variant="ghost" onClick={loadTrash}>Try again</Button> : null
            }>
                {error}
            </StatusMessage>
            <StatusMessage tone="success">{status}</StatusMessage>

            {loading && <DashboardSkeleton rows={4} />}

            {!loading && !error && items.length === 0 && (
                <div className="border-y border-neutral-200 py-12 text-center">
                    <h3 className="text-lg font-medium">Trash is empty</h3>
                    <p className="mt-2 text-sm text-neutral-600">Archived collections and writing will appear here.</p>
                </div>
            )}

            {!loading && Object.entries(groupedItems).map(([type, typeItems]) => (
                <section key={type} className="space-y-3">
                    <h3 className="text-sm font-semibold capitalize text-neutral-700">
                        {type === "book" ? "Novels" : `${type}s`}
                    </h3>
                    <div className="divide-y divide-neutral-200 border-y border-neutral-200">
                        {typeItems.map((item) => (
                            <article
                                key={`${type}-${item.id}`}
                                className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0">
                                    <p className="m-0 truncate font-medium text-neutral-900">{item.title}</p>
                                    <p className="mt-1 text-xs text-neutral-600">
                                        Archived {formatArchivedDate(item.deleted_at)}
                                    </p>
                                </div>
                                <Button
                                    size="sm"
                                    variant="subtle"
                                    disabled={Boolean(pendingId)}
                                    onClick={() => handleRestore(item)}
                                >
                                    {pendingId === `${item.type}-${item.id}` ? "Restoring…" : "Restore"}
                                </Button>
                            </article>
                        ))}
                    </div>
                </section>
            ))}
        </div>
    )
}
