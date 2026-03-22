import { useEffect, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"

import Button from "@/components/ui/Button"
import Input from "@/components/ui/Input"
import Select from "@/components/ui/Select"
import EditorPanel from "@/components/editor/EditorPanel"

import {
    getMyCollections,
    createPoem,
    updatePoem,
    getMyPoems
} from "@/services/contentService"

import { DASHBOARD_ROUTES } from "@/app/routes"

export default function PoemEditorPage() {
    const navigate = useNavigate()
    const { id } = useParams()

    const isEditMode = Boolean(id)

    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)

    const [collections, setCollections] = useState([])

    const [form, setForm] = useState({
        title: "",
        content_md: "",
        collection_id: ""
    })

    useEffect(() => {
        initialize()
    }, [])

    async function initialize() {
        try {
            const collectionsData = await getMyCollections()
            setCollections(collectionsData)

            if (isEditMode) {
                const poems = await getMyPoems()
                const existingPoem = poems.find(p => p.id === id)

                if (!existingPoem) {
                    navigate(DASHBOARD_ROUTES.POEMS)
                    return
                }

                setForm({
                    title: existingPoem.title,
                    content_md: existingPoem.content_md,
                    collection_id: existingPoem.collection_id
                })
            } else {
                setForm(prev => ({
                    ...prev,
                    collection_id: collectionsData[0]?.id || ""
                }))
            }
        } catch (err) {
            console.error("Failed to initialize editor:", err)
        } finally {
            setLoading(false)
        }
    }

    async function handleSave() {
        if (!form.title.trim()) return

        try {
            setSaving(true)

            if (isEditMode) {
                await updatePoem(id, {
                    title: form.title,
                    content_md: form.content_md,
                    collection_id: form.collection_id
                })
            } else {
                await createPoem({
                    title: form.title,
                    content_md: form.content_md,
                    collection_id: form.collection_id,
                    is_published: false
                })
            }

            navigate(DASHBOARD_ROUTES.POEMS)

        } catch (err) {
            console.error("Save failed:", err)
        } finally {
            setSaving(false)
        }
    }

    if (loading) {
        return (
            <div className="text-sm text-neutral-500">
                Loading editor...
            </div>
        )
    }

    return (
        <div className="space-y-8">

            {/* Header */}
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 className="text-2xl font-semibold tracking-tight">
                        {isEditMode ? "Edit Poem" : "New Poem"}
                    </h2>
                    <p className="text-sm text-neutral-500 mt-1">
                        {isEditMode
                            ? "Refine your verse."
                            : "Begin something luminous."}
                    </p>
                </div>

                <div className="flex gap-3">
                    <Button
                        variant="ghost"
                        onClick={() => navigate(DASHBOARD_ROUTES.POEMS)}
                    >
                        Cancel
                    </Button>

                    <Button
                        onClick={handleSave}
                        disabled={saving}
                    >
                        {saving ? "Saving..." : "Save"}
                    </Button>
                </div>
            </div>

            {/* Metadata */}
            <div className="space-y-4 max-w-full md:max-w-xl">

                <Input
                    placeholder="Poem title"
                    value={form.title}
                    onChange={(e) =>
                        setForm({ ...form, title: e.target.value })
                    }
                />

                <Select
                    value={form.collection_id}
                    onChange={(e) =>
                        setForm({ ...form, collection_id: e.target.value })
                    }
                >
                    {collections.map((collection) => (
                        <option
                            key={collection.id}
                            value={collection.id}
                        >
                            {collection.title}
                        </option>
                    ))}
                </Select>

            </div>

            {/* Editor */}
            <EditorPanel
                value={form.content_md}
                onChange={(value) =>
                    setForm({ ...form, content_md: value })
                }
            />

        </div>
    )
}