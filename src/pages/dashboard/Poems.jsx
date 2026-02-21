import { useEffect, useState } from "react"
import { useNavigate } from "react-router-dom"

import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"

import {
    getMyPoems,
    deletePoem,
    togglePoemPublish
} from "@/services/contentService"

export default function Poems() {
    const navigate = useNavigate()

    const [poems, setPoems] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        fetchPoems()
    }, [])

    async function fetchPoems() {
        try {
            const data = await getMyPoems()
            setPoems(data)
        } catch (err) {
            console.error("Failed to load poems:", err)
        } finally {
            setLoading(false)
        }
    }

    async function handleDelete(id) {
        try {
            await deletePoem(id)
            await fetchPoems()
        } catch (err) {
            console.error("Delete failed:", err)
        }
    }

    async function handleTogglePublish(poem) {
        try {
            await togglePoemPublish(poem.id, !poem.is_published)
            await fetchPoems()
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
                        No poems yet
                    </div>
                    <div className="text-sm text-neutral-500">
                        Begin your first verse.
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
                {poems.map((poem) => (
                    <Card key={poem.id} className="p-6 space-y-4">

                        <div className="flex items-start justify-between">

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

        </div>
    )
}