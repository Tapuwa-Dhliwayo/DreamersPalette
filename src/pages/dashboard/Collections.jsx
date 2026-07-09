import { useEffect, useState } from "react"
import { useLocation, useNavigate } from "react-router-dom"
import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Badge from "@/components/ui/Badge"
import Pagination from "@/components/ui/Pagination"
import ConfirmDialog from "@/components/ui/ConfirmDialog"
import StatusMessage from "@/components/ui/StatusMessage"
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton"
import { ContentList, ContentListRow, ContentListToolbar } from "@/components/dashboard/ContentList"
import { useContentListControls } from "@/hooks/useContentListControls"
import {
    archiveCollection,
    getMyCollections,
    getPoemCountsByCollection,
    togglePublish
} from "@/services/contentService"
import { DASHBOARD_ROUTES } from "@/app/routes"

const PAGE_SIZE = 10

function readinessFor(collection, counts) {
    const blockers = []
    const warnings = []
    if ((counts?.total || 0) === 0) blockers.push("Add at least one poem.")
    if (!collection.description?.trim()) warnings.push("Add a description to introduce the collection.")
    if (!collection.theme_background_url) warnings.push("No custom background is set; the global atmosphere will be used.")
    if (!collection.accent_color) warnings.push("Choose an accent color for reader links and underlines.")
    return { blockers, warnings }
}

export default function Collections() {
    const navigate = useNavigate()
    const location = useLocation()
    const [collections, setCollections] = useState([])
    const [poemCounts, setPoemCounts] = useState({})
    const [loading, setLoading] = useState(true)
    const [pending, setPending] = useState(false)
    const [error, setError] = useState("")
    const [status, setStatus] = useState(location.state?.message || "")
    const [archiveTargets, setArchiveTargets] = useState([])
    const [publishTargets, setPublishTargets] = useState([])
    const list = useContentListControls(collections, PAGE_SIZE)

    useEffect(() => {
        loadCollections()
        if (location.state?.message) window.history.replaceState({}, document.title)
    }, [location.state?.message])

    async function loadCollections() {
        try {
            setLoading(true)
            setError("")
            const [data, counts] = await Promise.all([getMyCollections(), getPoemCountsByCollection()])
            setCollections(data)
            setPoemCounts(counts)
        } catch (err) {
            setError(err.message || "Collections could not be loaded.")
        } finally {
            setLoading(false)
        }
    }

    async function handleArchive() {
        try {
            setPending(true)
            await Promise.all(archiveTargets.map((item) => archiveCollection(item.id)))
            setStatus(`${archiveTargets.length} ${archiveTargets.length === 1 ? "collection was" : "collections were"} moved to Trash.`)
            setArchiveTargets([])
            list.setSelectedIds([])
            await loadCollections()
        } catch (err) {
            setError(err.message || "The selected collections could not be archived.")
        } finally {
            setPending(false)
        }
    }

    async function handlePublish() {
        try {
            setPending(true)
            await Promise.all(publishTargets.map((item) => togglePublish(item.id, !item.is_published)))
            setStatus(`${publishTargets.length} ${publishTargets.length === 1 ? "collection was" : "collections were"} updated.`)
            setPublishTargets([])
            list.setSelectedIds([])
            await loadCollections()
        } catch (err) {
            setError(err.message || "Publication state could not be changed.")
        } finally {
            setPending(false)
        }
    }

    const selectedItems = collections.filter((item) => list.selectedIds.includes(item.id))
    const publishReadiness = publishTargets.map((item) => ({
        item,
        ...readinessFor(item, poemCounts[item.id])
    }))
    const hasPublishBlockers = publishReadiness.some((entry) => !entry.item.is_published && entry.blockers.length > 0)

    return (
        <div className="space-y-8">
            <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-2xl font-semibold tracking-tight">Collections</h2>
                    <p className="mt-1 text-sm text-neutral-600">Shape each poetic world and verify its reader experience.</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="ghost" onClick={() => navigate(DASHBOARD_ROUTES.TRASH)}>Trash</Button>
                    <Button onClick={() => navigate(DASHBOARD_ROUTES.COLLECTION_NEW)}>New Collection</Button>
                </div>
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

            <StatusMessage tone="error" action={error ? <Button size="sm" variant="ghost" onClick={loadCollections}>Try again</Button> : null}>{error}</StatusMessage>
            <StatusMessage tone="success">{status}</StatusMessage>
            {loading && <DashboardSkeleton />}

            {!loading && list.filteredItems.length === 0 && (
                <Card className="p-8 text-center">
                    <h3 className="text-lg font-medium">No collections found</h3>
                    <p className="mt-2 text-sm text-neutral-600">Adjust the search or create your first poetic world.</p>
                    <Button className="mt-5" onClick={() => navigate(DASHBOARD_ROUTES.COLLECTION_NEW)}>Create Collection</Button>
                </Card>
            )}

            {!loading && list.filteredItems.length > 0 && (
                <ContentList items={list.visibleItems} selectedIds={list.selectedIds} onToggleAll={list.toggleAll}>
                    {list.visibleItems.map((collection) => {
                        const readiness = readinessFor(collection, poemCounts[collection.id])
                        return (
                            <ContentListRow
                                key={collection.id}
                                item={collection}
                                selected={list.selectedIds.includes(collection.id)}
                                onToggle={list.toggleItem}
                                title={collection.title}
                                description={collection.description}
                                metadata={`Updated ${new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(new Date(collection.updated_at || collection.created_at))}`}
                                onEdit={() => navigate(DASHBOARD_ROUTES.COLLECTION_EDIT(collection.id))}
                                onArchive={() => setArchiveTargets([collection])}
                                onPublish={() => setPublishTargets([collection])}
                                pending={pending}
                            >
                                <Badge>{poemCounts[collection.id]?.total || 0} poems</Badge>
                                {!collection.is_published && readiness.blockers.length > 0 && <Badge variant="danger">Not ready</Badge>}
                            </ContentListRow>
                        )
                    })}
                </ContentList>
            )}

            <Pagination page={list.page} pageSize={PAGE_SIZE} totalCount={list.filteredItems.length} onPageChange={list.setPage} />

            <ConfirmDialog
                open={archiveTargets.length > 0}
                title={`Move ${archiveTargets.length === 1 ? "collection" : `${archiveTargets.length} collections`} to Trash?`}
                description="Selected collections will be unpublished and can be restored from Trash."
                confirmLabel="Move to Trash"
                busy={pending}
                onClose={() => setArchiveTargets([])}
                onConfirm={handleArchive}
            />
            <ConfirmDialog
                open={publishTargets.length > 0}
                title={publishTargets[0]?.is_published ? "Unpublish collection?" : "Publication readiness"}
                description={publishTargets[0]?.is_published
                    ? "This collection will no longer be visible to readers."
                    : (
                        <div className="space-y-3">
                            {publishReadiness.map(({ item, blockers, warnings }) => (
                                <div key={item.id}>
                                    <p className="m-0 font-medium text-neutral-900">{item.title}</p>
                                    {blockers.map((message) => <p key={message} className="mt-1 text-red-700">Required: {message}</p>)}
                                    {warnings.map((message) => <p key={message} className="mt-1 text-amber-800">Check: {message}</p>)}
                                    {blockers.length === 0 && warnings.length === 0 && <p className="mt-1 text-green-700">Ready to publish.</p>}
                                </div>
                            ))}
                        </div>
                    )}
                confirmLabel={publishTargets[0]?.is_published ? "Unpublish" : "Publish"}
                confirmDisabled={hasPublishBlockers}
                variant="primary"
                busy={pending}
                onClose={() => setPublishTargets([])}
                onConfirm={handlePublish}
            />
        </div>
    )
}
