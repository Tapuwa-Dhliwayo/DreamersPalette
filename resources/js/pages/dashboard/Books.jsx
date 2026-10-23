import { useEffect, useState } from "react"
import { useNavigate } from "react-router-dom"
import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Pagination from "@/components/ui/Pagination"
import ConfirmDialog from "@/components/ui/ConfirmDialog"
import StatusMessage from "@/components/ui/StatusMessage"
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton"
import { ContentList, ContentListRow, ContentListToolbar } from "@/components/dashboard/ContentList"
import { ListPanel, ListPanelBody, ListPanelFooter, ListPanelHeader } from "@/components/dashboard/ListPanel"
import { useContentListControls } from "@/hooks/useContentListControls"
import { getMyBooks, archiveBook, toggleBookPublish } from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"

const PAGE_SIZE = 10

export default function Books() {
    const navigate = useNavigate()
    const [books, setBooks] = useState([])
    const [loading, setLoading] = useState(true)
    const [pending, setPending] = useState(false)
    const [error, setError] = useState("")
    const [status, setStatus] = useState("")
    const [archiveTargets, setArchiveTargets] = useState([])
    const [publishTargets, setPublishTargets] = useState([])
    const list = useContentListControls(books, PAGE_SIZE)

    useEffect(() => {
        loadBooks()
    }, [])

    async function loadBooks() {
        try {
            setLoading(true)
            setError("")
            setBooks(await getMyBooks())
        } catch (err) {
            setError(err.message || "Novels could not be loaded.")
        } finally {
            setLoading(false)
        }
    }

    async function handleArchive() {
        try {
            setPending(true)
            await Promise.all(archiveTargets.map((item) => archiveBook(item.id)))
            setStatus(`${archiveTargets.length} ${archiveTargets.length === 1 ? "novel was" : "novels were"} moved to Trash.`)
            setArchiveTargets([])
            list.setSelectedIds([])
            await loadBooks()
        } catch (err) {
            setError(err.message || "The selected novels could not be archived.")
        } finally {
            setPending(false)
        }
    }

    async function handlePublish() {
        try {
            setPending(true)
            await Promise.all(publishTargets.map((item) => toggleBookPublish(item.id, !item.is_published)))
            setStatus(`${publishTargets.length} ${publishTargets.length === 1 ? "novel was" : "novels were"} updated.`)
            setPublishTargets([])
            list.setSelectedIds([])
            await loadBooks()
        } catch (err) {
            setError(err.message || "Publication state could not be changed.")
        } finally {
            setPending(false)
        }
    }

    const selectedItems = books.filter((item) => list.selectedIds.includes(item.id))

    return (
        <ListPanel>
            <ListPanelHeader>
                <header className="flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">Novels</h2>
                        <p className="mt-1 text-sm text-neutral-600">Manage long-form work and cover imagery.</p>
                    </div>
                    <Button onClick={() => navigate(DASHBOARD_ROUTES.BOOK_NEW)}>New Novel</Button>
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
                />

                <StatusMessage tone="error" action={error ? <Button size="sm" variant="ghost" onClick={loadBooks}>Try again</Button> : null}>{error}</StatusMessage>
                <StatusMessage tone="success">{status}</StatusMessage>
            </ListPanelHeader>

            <ListPanelBody scrollKey={`${list.page}|${list.search}|${list.sort}`}>
                {loading && <DashboardSkeleton />}

                {!loading && list.filteredItems.length === 0 && (
                    <Card className="p-8 text-center">
                        <h3 className="text-lg font-medium">No novels found</h3>
                        <p className="mt-2 text-sm text-neutral-600">Adjust the search or begin a new novel.</p>
                        <Button className="mt-5" onClick={() => navigate(DASHBOARD_ROUTES.BOOK_NEW)}>Create Novel</Button>
                    </Card>
                )}

                {!loading && list.filteredItems.length > 0 && (
                    <ContentList items={list.visibleItems} selectedIds={list.selectedIds} onToggleAll={list.toggleAll}>
                        {list.visibleItems.map((book) => (
                            <ContentListRow
                                key={book.id}
                                item={book}
                                selected={list.selectedIds.includes(book.id)}
                                onToggle={list.toggleItem}
                                title={book.title}
                                description={book.synopsis}
                                metadata={`Updated ${new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(new Date(book.updated_at || book.created_at))}`}
                                onEdit={() => navigate(DASHBOARD_ROUTES.BOOK_EDIT(book.id))}
                                onArchive={() => setArchiveTargets([book])}
                                onPublish={() => setPublishTargets([book])}
                                pending={pending}
                            />
                        ))}
                    </ContentList>
                )}
            </ListPanelBody>

            <ListPanelFooter>
                <Pagination page={list.page} pageSize={PAGE_SIZE} totalCount={list.filteredItems.length} onPageChange={list.setPage} alwaysShow showRange />
            </ListPanelFooter>

            <ConfirmDialog
                open={archiveTargets.length > 0}
                title={`Move ${archiveTargets.length === 1 ? "novel" : `${archiveTargets.length} novels`} to Trash?`}
                description="Selected novels will be unpublished and can be restored from Trash."
                confirmLabel="Move to Trash"
                busy={pending}
                onClose={() => setArchiveTargets([])}
                onConfirm={handleArchive}
            />
            <ConfirmDialog
                open={publishTargets.length > 0}
                title={publishTargets[0]?.is_published ? "Unpublish novel?" : `Publish ${publishTargets.length === 1 ? "novel" : `${publishTargets.length} novels`}?`}
                description="This changes what readers can see immediately."
                confirmLabel={publishTargets[0]?.is_published ? "Unpublish" : "Publish"}
                variant="primary"
                busy={pending}
                onClose={() => setPublishTargets([])}
                onConfirm={handlePublish}
            />
        </ListPanel>
    )
}
