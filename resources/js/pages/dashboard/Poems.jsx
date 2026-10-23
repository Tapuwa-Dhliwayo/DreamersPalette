import { useEffect, useState } from "react"
import { useNavigate } from "react-router-dom"
import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Select from "@/components/ui/Select"
import Pagination from "@/components/ui/Pagination"
import ConfirmDialog from "@/components/ui/ConfirmDialog"
import StatusMessage from "@/components/ui/StatusMessage"
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton"
import {
    ContentList,
    ContentListRow,
    ContentListToolbar
} from "@/components/dashboard/ContentList"
import {
    ListPanel,
    ListPanelBody,
    ListPanelFooter,
    ListPanelHeader
} from "@/components/dashboard/ListPanel"
import { useContentListControls } from "@/hooks/useContentListControls"
import {
    getMyPoems,
    getPoemsByCollection,
    getMyCollections,
    archivePoem,
    togglePoemPublish
} from "@/services/contentService"
import { DASHBOARD_ROUTES } from "@/app/routes"

const PAGE_SIZE = 10

export default function Poems() {
    const navigate = useNavigate()
    const [poems, setPoems] = useState([])
    const [collections, setCollections] = useState([])
    const [selectedCollection, setSelectedCollection] = useState("all")
    const [loading, setLoading] = useState(true)
    const [pending, setPending] = useState(false)
    const [error, setError] = useState("")
    const [status, setStatus] = useState("")
    const [archiveTargets, setArchiveTargets] = useState([])
    const [publishTargets, setPublishTargets] = useState([])
    const list = useContentListControls(poems, PAGE_SIZE)

    useEffect(() => {
        initialize()
    }, [])

    async function initialize() {
        try {
            setLoading(true)
            setError("")
            const [poemsData, collectionsData] = await Promise.all([getMyPoems(), getMyCollections()])
            setPoems(poemsData)
            setCollections(collectionsData)
        } catch (err) {
            setError(err.message || "Poems could not be loaded.")
        } finally {
            setLoading(false)
        }
    }

    async function handleFilterChange(value) {
        setSelectedCollection(value)
        list.setSelectedIds([])
        setLoading(true)
        try {
            setPoems(value === "all" ? await getMyPoems() : await getPoemsByCollection(value))
        } catch (err) {
            setError(err.message || "The selected collection could not be loaded.")
        } finally {
            setLoading(false)
        }
    }

    async function handleArchive() {
        try {
            setPending(true)
            await Promise.all(archiveTargets.map((item) => archivePoem(item.id)))
            setStatus(`${archiveTargets.length} ${archiveTargets.length === 1 ? "poem was" : "poems were"} moved to Trash.`)
            setArchiveTargets([])
            list.setSelectedIds([])
            await handleFilterChange(selectedCollection)
        } catch (err) {
            setError(err.message || "The selected poems could not be archived.")
        } finally {
            setPending(false)
        }
    }

    async function handlePublish() {
        try {
            setPending(true)
            await Promise.all(publishTargets.map((item) => togglePoemPublish(item.id, !item.is_published)))
            setStatus(`${publishTargets.length} ${publishTargets.length === 1 ? "poem was" : "poems were"} updated.`)
            setPublishTargets([])
            list.setSelectedIds([])
            await handleFilterChange(selectedCollection)
        } catch (err) {
            setError(err.message || "Publication state could not be changed.")
        } finally {
            setPending(false)
        }
    }

    const selectedItems = poems.filter((item) => list.selectedIds.includes(item.id))

    return (
        <ListPanel>
            <ListPanelHeader>
                <header className="flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">Poems</h2>
                        <p className="mt-1 text-sm text-neutral-600">Write, organize, and publish individual pieces.</p>
                    </div>
                    <Button onClick={() => navigate(DASHBOARD_ROUTES.POEM_NEW)}>New Poem</Button>
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
                        Collection
                        <Select className="mt-2" value={selectedCollection} onChange={(event) => handleFilterChange(event.target.value)}>
                            <option value="all">All collections</option>
                            {collections.map((collection) => (
                                <option key={collection.id} value={collection.id}>{collection.title}</option>
                            ))}
                        </Select>
                    </label>
                </ContentListToolbar>

                <StatusMessage tone="error" action={error ? <Button size="sm" variant="ghost" onClick={initialize}>Try again</Button> : null}>{error}</StatusMessage>
                <StatusMessage tone="success">{status}</StatusMessage>
            </ListPanelHeader>

            <ListPanelBody scrollKey={`${list.page}|${list.search}|${list.sort}|${selectedCollection}`}>
                {loading && <DashboardSkeleton />}

                {!loading && list.filteredItems.length === 0 && (
                    <Card className="p-8 text-center">
                        <h3 className="text-lg font-medium">No poems found</h3>
                        <p className="mt-2 text-sm text-neutral-600">Adjust the filters or begin a new poem.</p>
                        <Button className="mt-5" onClick={() => navigate(DASHBOARD_ROUTES.POEM_NEW)}>Write a Poem</Button>
                    </Card>
                )}

                {!loading && list.filteredItems.length > 0 && (
                    <ContentList items={list.visibleItems} selectedIds={list.selectedIds} onToggleAll={list.toggleAll}>
                        {list.visibleItems.map((poem) => (
                            <ContentListRow
                                key={poem.id}
                                item={poem}
                                selected={list.selectedIds.includes(poem.id)}
                                onToggle={list.toggleItem}
                                title={poem.title}
                                description={poem.excerpt}
                                metadata={`Updated ${new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(new Date(poem.updated_at || poem.created_at))}`}
                                onEdit={() => navigate(DASHBOARD_ROUTES.POEM_EDIT(poem.id))}
                                onArchive={() => setArchiveTargets([poem])}
                                onPublish={() => setPublishTargets([poem])}
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
                title={`Move ${archiveTargets.length === 1 ? "poem" : `${archiveTargets.length} poems`} to Trash?`}
                description="Selected poems will be unpublished and can be restored from Trash."
                confirmLabel="Move to Trash"
                busy={pending}
                onClose={() => setArchiveTargets([])}
                onConfirm={handleArchive}
            />
            <ConfirmDialog
                open={publishTargets.length > 0}
                title={publishTargets[0]?.is_published ? "Unpublish poem?" : `Publish ${publishTargets.length === 1 ? "poem" : `${publishTargets.length} poems`}?`}
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
