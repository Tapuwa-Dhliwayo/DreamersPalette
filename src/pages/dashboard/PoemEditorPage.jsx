import { useEffect, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"

import Button from "@/components/ui/Button"
import Input from "@/components/ui/Input"
import Select from "@/components/ui/Select"
import EditorPanel from "@/components/editor/EditorPanel"
import FormField from "@/components/ui/FormField"
import StatusMessage from "@/components/ui/StatusMessage"
import ConfirmDialog from "@/components/ui/ConfirmDialog"
import { useUnsavedChanges } from "@/hooks/useUnsavedChanges"
import { useLocalDraft } from "@/hooks/useLocalDraft"
import EditorSaveState from "@/components/dashboard/EditorSaveState"

import {
    getMyCollections,
    createPoem,
    updatePoem,
    getMyPoemById
} from "@/services/contentService"

import { DASHBOARD_ROUTES } from "@/app/routes"

export default function PoemEditorPage() {
    const navigate = useNavigate()
    const { id } = useParams()

    const isEditMode = Boolean(id)

    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)
    const [validationError, setValidationError] = useState("")
    const [saveError, setSaveError] = useState("")
    const [initialForm, setInitialForm] = useState(null)

    const [collections, setCollections] = useState([])

    const [form, setForm] = useState({
        title: "",
        content_md: "",
        collection_id: ""
    })
    const localDraft = useLocalDraft({
        key: `dreamers-palette:poem:${id || "new"}`,
        form,
        initialForm,
        setForm
    })
    const navigationGuard = useUnsavedChanges(localDraft.isDirty && !saving)

    useEffect(() => {
        async function initialize() {
            try {
                const collectionsData = await getMyCollections()
                setCollections(collectionsData)

                if (isEditMode) {
                    const existingPoem = await getMyPoemById(id)

                    if (!existingPoem) {
                        navigate(DASHBOARD_ROUTES.POEMS)
                        return
                    }

                    setForm({
                        title: existingPoem.title || "",
                        content_md: existingPoem.content_md || "",
                        collection_id: existingPoem.collection_id || collectionsData[0]?.id || ""
                    })
                    setInitialForm({
                        title: existingPoem.title || "",
                        content_md: existingPoem.content_md || "",
                        collection_id: existingPoem.collection_id || collectionsData[0]?.id || ""
                    })
                } else {
                    const nextForm = {
                        title: "",
                        content_md: "",
                        collection_id: collectionsData[0]?.id || ""
                    }
                    setForm({
                        ...nextForm
                    })
                    setInitialForm({
                        ...nextForm
                    })
                }
            } catch (err) {
                setSaveError(err.message || "The poem editor could not be loaded. Try returning to Poems and opening it again.")
            } finally {
                setLoading(false)
            }
        }

        initialize()
    }, [id, isEditMode, navigate])

    async function handleSave() {
        if (!form.title.trim()) {
            setValidationError("A poem title is required.")
            return
        }

        if (!form.collection_id) {
            setValidationError("Select a collection before saving.")
            return
        }

        try {
            setSaving(true)
            setValidationError("")
            setSaveError("")

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

            localDraft.clearDraft()
            navigationGuard.markSafe()
            navigate(DASHBOARD_ROUTES.POEMS)

        } catch (err) {
            setSaveError(err.message || "The poem could not be saved. Your writing is still here.")
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
                    <EditorSaveState
                        saving={saving}
                        isDirty={localDraft.isDirty}
                        persistedAt={localDraft.persistedAt}
                        recoveredAt={localDraft.recoveredAt}
                        error={saveError}
                    />
                    <Button
                        variant="ghost"
                        onClick={() => navigate(DASHBOARD_ROUTES.POEMS)}
                    >
                        Cancel
                    </Button>

                    <Button
                        onClick={handleSave}
                        disabled={saving || collections.length === 0 || (isEditMode && !initialForm)}
                    >
                        {saving ? "Saving..." : "Save"}
                    </Button>
                </div>
            </div>

            {/* Metadata */}
            <div className="space-y-4 max-w-full md:max-w-xl">
                {collections.length === 0 && (
                    <p className="text-sm text-neutral-500">
                        Create a collection before saving a poem.
                    </p>
                )}

                <StatusMessage tone="error">{validationError || saveError}</StatusMessage>

                <FormField label="Poem title" htmlFor="poem-title" required>
                    <Input
                        id="poem-title"
                        value={form.title}
                        maxLength={160}
                        onChange={(e) => {
                            setValidationError("")
                            setForm({ ...form, title: e.target.value })
                        }}
                    />
                </FormField>

                <FormField label="Collection" htmlFor="poem-collection" required>
                    <Select
                        id="poem-collection"
                        value={form.collection_id}
                        onChange={(e) => {
                            setValidationError("")
                            setForm({ ...form, collection_id: e.target.value })
                        }}
                    >
                        {collections.map((collection) => (
                            <option key={collection.id} value={collection.id}>
                                {collection.title}
                            </option>
                        ))}
                    </Select>
                </FormField>

            </div>

            {/* Editor */}
            <EditorPanel
                value={form.content_md}
                onChange={(value) =>
                    setForm({ ...form, content_md: value })
                }
            />

            <ConfirmDialog
                open={navigationGuard.blocked}
                title="Discard unsaved poem?"
                description="Your latest changes have not been saved."
                confirmLabel="Discard changes"
                onClose={navigationGuard.reset}
                onConfirm={navigationGuard.proceed}
            />

        </div>
    )
}
