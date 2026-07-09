import { useEffect, useState } from "react"
import { useNavigate } from "react-router-dom"
import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Select from "@/components/ui/Select"
import Pagination from "@/components/ui/Pagination"
import ConfirmDialog from "@/components/ui/ConfirmDialog"
import StatusMessage from "@/components/ui/StatusMessage"
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton"
import { ContentList, ContentListRow, ContentListToolbar } from "@/components/dashboard/ContentList"
import { useContentListControls } from "@/hooks/useContentListControls"
import {
    getMyBooks,
    getMyChaptersByBook,
    archiveChapter,
    toggleChapterPublish
} from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"

const PAGE_SIZE = 10

export default function Chapters() {
    const navigate = useNavigate()
    const [books, setBooks] = useState([])
    const [chapters, setChapters] = useState([])
    const [selectedBookId, setSelectedBookId] = useState("")
    const [loading, setLoading] = useState(true)
    const [pending, setPending] = useState(false)
    const [error, setError] = useState("")
    const [status, setStatus] = useState("")
    const [archiveTargets, setArchiveTargets] = useState([])
    const [publishTargets, setPublishTargets] = useState([])
    const list = useContentListControls(chapters, PAGE_SIZE)

    useEffect(() => {
        async function loadInitial() {
            try {
                setLoading(true)
                setError("")
                const bookData = await getMyBooks()
                setBooks(bookData)
                const bookId = bookData[0]?.id || ""
                setSelectedBookId(bookId)
                setChapters(bookId ? await getMyChaptersByBook(bookId) : [])
            } catch (err) {
                setError(err.message || "Chapters could not be loaded.")
            } finally {
                setLoading(false)
            }
        }
        loadInitial()
    }, [])

    async function initialize() {
        try {
            setLoading(true)
            setError("")
            const bookData = await getMyBooks()
            setBooks(bookData)
            const bookId = selectedBookId || bookData[0]?.id || ""
            setSelectedBookId(bookId)
            setChapters(bookId ? await getMyChaptersByBook(bookId) : [])
        } catch (err) {
            setError(err.message || "Chapters could not be loaded.")
        } finally {
            setLoading(false)
        }
    }

    async function handleBookChange(bookId) {
        setSelectedBookId(bookId)
        list.setSelectedIds([])
        setLoading(true)
        try {
            setChapters(bookId ? await getMyChaptersByBook(bookId) : [])
        } catch (err) {
            setError(err.message || "The selected novel could not be loaded.")
        } finally {
            setLoading(false)
        }
    }

    async function handleArchive() {
        try {
            setPending(true)
            await Promise.all(archiveTargets.map((item) => archiveChapter(item.id)))
            setStatus(`${archiveTargets.length} ${archiveTargets.length === 1 ? "chapter was" : "chapters were"} moved to Trash.`)
            setArchiveTargets([])
            list.setSelectedIds([])
            await handleBookChange(selectedBookId)
        } catch (err) {
            setError(err.message || "The selected chapters could not be archived.")
        } finally {
            setPending(false)
        }
    }

    async function handlePublish() {
        try {
            setPending(true)
            await Promise.all(publishTargets.map((item) => toggleChapterPublish(item.id, !item.is_published)))
            setStatus(`${publishTargets.length} ${publishTargets.length === 1 ? "chapter was" : "chapters were"} updated.`)
            setPublishTargets([])
            list.setSelectedIds([])
            await handleBookChange(selectedBookId)
        } catch (err) {
            setError(err.message || "Publication state could not be changed.")
        } finally {
            setPending(false)
        }
    }

    const selectedItems = chapters.filter((item) => list.selectedIds.includes(item.id))

    return (
        <div className="space-y-8">
            <header className="flex items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-semibold tracking-tight">Chapters</h2>
                    <p className="mt-1 text-sm text-neutral-600">Organize and publish chapters within each novel.</p>
                </div>
                <Button
                    disabled={!selectedBookId}
                    onClick={() => navigate(`${DASHBOARD_ROUTES.CHAPTER_NEW}?bookId=${selectedBookId}`)}
                >
                    New Chapter
                </Button>
            </header>

            <ContentListToolbar
                search={list.search}
                onSearchChange={list.setSearch}
                sort={list.sort}
                onSortChange={list.setSort}
                selectedCount={list.selectedIds.length}
                pending={pending}
                onBulkPublish={() => setPublishTargets(selectedItems.filter((item) => !item.is_published))}
                onBulkArchive={() => setArchiveTargets(selectedItems)}
            >
                <label className="w-full text-sm font-medium text-neutral-700 md:w-56">
                    Novel
                    <Select className="mt-2" value={selectedBookId} onChange={(event) => handleBookChange(event.target.value)}>
                        {books.map((book) => <option key={book.id} value={book.id}>{book.title}</option>)}
                    </Select>
                </label>
            </ContentListToolbar>

            <StatusMessage tone="error" action={error ? <Button size="sm" variant="ghost" onClick={initialize}>Try again</Button> : null}>{error}</StatusMessage>
            <StatusMessage tone="success">{status}</StatusMessage>
            {loading && <DashboardSkeleton />}

            {!loading && books.length === 0 && (
                <Card className="p-8 text-center">
                    <h3 className="text-lg font-medium">Create a novel first</h3>
                    <p className="mt-2 text-sm text-neutral-600">Chapters need a novel to belong to.</p>
                    <Button className="mt-5" onClick={() => navigate(DASHBOARD_ROUTES.BOOK_NEW)}>Create Novel</Button>
                </Card>
            )}

            {!loading && books.length > 0 && list.filteredItems.length === 0 && (
                <Card className="p-8 text-center">
                    <h3 className="text-lg font-medium">No chapters found</h3>
                    <p className="mt-2 text-sm text-neutral-600">Adjust the search or add the first chapter.</p>
                    <Button className="mt-5" onClick={() => navigate(`${DASHBOARD_ROUTES.CHAPTER_NEW}?bookId=${selectedBookId}`)}>Create Chapter</Button>
                </Card>
            )}

            {!loading && list.filteredItems.length > 0 && (
                <ContentList items={list.visibleItems} selectedIds={list.selectedIds} onToggleAll={list.toggleAll}>
                    {list.visibleItems.map((chapter) => (
                        <ContentListRow
                            key={chapter.id}
                            item={chapter}
                            selected={list.selectedIds.includes(chapter.id)}
                            onToggle={list.toggleItem}
                            title={`${chapter.chapter_number}. ${chapter.title}`}
                            metadata={`${chapter.is_preview ? "Reader preview" : "Full chapter"} · Updated ${new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(new Date(chapter.updated_at || chapter.created_at))}`}
                            onEdit={() => navigate(DASHBOARD_ROUTES.CHAPTER_EDIT(chapter.id))}
                            onArchive={() => setArchiveTargets([chapter])}
                            onPublish={() => setPublishTargets([chapter])}
                            pending={pending}
                        />
                    ))}
                </ContentList>
            )}

            <Pagination page={list.page} pageSize={PAGE_SIZE} totalCount={list.filteredItems.length} onPageChange={list.setPage} />

            <ConfirmDialog
                open={archiveTargets.length > 0}
                title={`Move ${archiveTargets.length === 1 ? "chapter" : `${archiveTargets.length} chapters`} to Trash?`}
                description="Selected chapters will be unpublished and can be restored from Trash."
                confirmLabel="Move to Trash"
                busy={pending}
                onClose={() => setArchiveTargets([])}
                onConfirm={handleArchive}
            />
            <ConfirmDialog
                open={publishTargets.length > 0}
                title={publishTargets[0]?.is_published ? "Unpublish chapter?" : `Publish ${publishTargets.length === 1 ? "chapter" : `${publishTargets.length} chapters`}?`}
                description="This changes what readers can see immediately."
                confirmLabel={publishTargets[0]?.is_published ? "Unpublish" : "Publish"}
                variant="primary"
                busy={pending}
                onClose={() => setPublishTargets([])}
                onConfirm={handlePublish}
            />
        </div>
    )
}
