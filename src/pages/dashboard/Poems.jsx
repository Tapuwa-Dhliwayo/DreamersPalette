import { useEffect, useMemo, useState } from "react"
import { useNavigate } from "react-router-dom"

import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Select from "@/components/ui/Select"
import Pagination from "@/components/ui/Pagination"

import {
    getMyPoems,
    getPoemsByCollection,
    getMyCollections,
    deletePoem,
    togglePoemPublish
} from "@/services/contentService"

const DASHBOARD_PAGE_SIZE = 10

export default function Poems() {
    const navigate = useNavigate()

    const [poems, setPoems] = useState([])
    const [collections, setCollections] = useState([])
    const [selectedCollection, setSelectedCollection] = useState("all")
    const [loading, setLoading] = useState(true)
    const [page, setPage] = useState(1)

    useEffect(() => {
        initialize()
    }, [])

    const paginatedPoems = useMemo(() => {
        const start = (page - 1) * DASHBOARD_PAGE_SIZE
        return poems.slice(start, start + DASHBOARD_PAGE_SIZE)
    }, [poems, page])

    useEffect(() => {
        const totalPages = Math.max(1, Math.ceil(poems.length / DASHBOARD_PAGE_SIZE))
        if (page > totalPages) {
            setPage(totalPages)
        }
    }, [poems.length, page])

    async function initialize() {
        try {
            const [poemsData, collectionsData] = await Promise.all([
                getMyPoems(),
                getMyCollections()
            ])

            setPoems(poemsData)
            setCollections(collectionsData)
        } catch (err) {
            console.error("Failed to load poems:", err)
        } finally {
            setLoading(false)
        }
    }

    async function handleFilterChange(value) {
        setSelectedCollection(value)
        setPage(1)
        setLoading(true)

        try {
            if (value === "all") {
                const data = await getMyPoems()
                setPoems(data)
            } else {
                const data = await getPoemsByCollection(value)
                setPoems(data)
            }
        } catch (err) {
            console.error("Filter failed:", err)
        } finally {
            setLoading(false)
        }
    }

    async function handleDelete(id) {
        try {
            await deletePoem(id)
            await handleFilterChange(selectedCollection)
        } catch (err) {
            console.error("Delete failed:", err)
        }
    }

    async function handleTogglePublish(poem) {
        try {
            await togglePoemPublish(poem.id, !poem.is_published)
            await handleFilterChange(selectedCollection)
        } catch (err) {
            console.error("Toggle failed:", err)
        }
    }

    return (
        <div className="space-y-10">

            {/* Header */}
            <div className="flex items-center justify-between">
                <h2 className="text-2xl font-semibold tracking-tight">
                    Poems
                </h2>

                <Button onClick={() => navigate("/dashboard/poems/new")}>
                    New Poem
                </Button>
            </div>

            {/* Filter */}
            <div className="max-w-xs">
                <Select
                    value={selectedCollection}
                    onChange={(e) => handleFilterChange(e.target.value)}
                >
                    <option value="all">All Collections</option>
                    {collections.map((collection) => (
                        <option key={collection.id} value={collection.id}>
                            {collection.title}
                        </option>
                    ))}
                </Select>
            </div>

            {/* Loading */}
            {loading && (
                <div className="text-sm text-neutral-500">
                    Loading poems...
                </div>
            )}

            {/* Empty State */}
            {!loading && poems.length === 0 && (
                <Card className="p-10 text-center space-y-3">
                    <div className="text-lg font-medium">
                        No poems found
                    </div>
                    <div className="text-sm text-neutral-500">
                        Begin your next verse.
                    </div>
                    <div>
                        <Button onClick={() => navigate("/dashboard/poems/new")}>
                            Write a Poem
                        </Button>
                    </div>
                </Card>
            )}

            {/* Poems List */}
            <div className="grid gap-6">
                {paginatedPoems.map((poem) => (
                    <Card key={poem.id} className="p-4 md:p-6 space-y-4">

                        <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">

                            <div>
                                <h3 className="text-lg font-medium">
                                    {poem.title}
                                </h3>

                                <p className="text-xs text-neutral-400 mt-2">
                                    /poems/{poem.slug}
                                </p>
                            </div>

                            <div className="flex gap-2">

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        navigate(`/dashboard/poems/${poem.id}/edit`)
                                    }
                                >
                                    Edit
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => handleDelete(poem.id)}
                                >
                                    Delete
                                </Button>

                            </div>
                        </div>

                        <div className="flex items-center justify-between">

                            <span className="text-xs text-neutral-500">
                                {poem.is_published ? "Published" : "Draft"}
                            </span>

                            <Button
                                variant="subtle"
                                size="sm"
                                onClick={() => handleTogglePublish(poem)}
                            >
                                {poem.is_published ? "Unpublish" : "Publish"}
                            </Button>

                        </div>

                    </Card>
                ))}
            </div>

            <Pagination
                page={page}
                pageSize={DASHBOARD_PAGE_SIZE}
                totalCount={poems.length}
                onPageChange={setPage}
            />

        </div>
    )
}
