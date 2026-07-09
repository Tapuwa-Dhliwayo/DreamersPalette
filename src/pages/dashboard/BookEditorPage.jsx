import { useEffect, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"

import Button from "@/components/ui/Button"
import Input from "@/components/ui/Input"
import Textarea from "@/components/ui/Textarea"
import FormField from "@/components/ui/FormField"
import StatusMessage from "@/components/ui/StatusMessage"
import ConfirmDialog from "@/components/ui/ConfirmDialog"
import { useUnsavedChanges } from "@/hooks/useUnsavedChanges"
import { useLocalDraft } from "@/hooks/useLocalDraft"
import EditorSaveState from "@/components/dashboard/EditorSaveState"
import { generateBookCover, getAiProviderLabel } from "@/services/aiAssetService"
import { uploadBackgroundImage, validateImageFile, compressImageIfNeeded } from "@/services/storageService"
import { supabase } from "@/services/supabaseClient"

import {
    getMyBookById,
    createBook,
    updateBook
} from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"

export default function BookEditorPage() {
    const providerLabel = getAiProviderLabel()
    const navigate = useNavigate()
    const { id } = useParams()

    const isEditMode = Boolean(id)

    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)
    const [uploading, setUploading] = useState(false)
    const [uploadError, setUploadError] = useState("")
    const [uploadInfo, setUploadInfo] = useState("")
    const [generatingCover, setGeneratingCover] = useState(false)
    const [coverPrompt, setCoverPrompt] = useState("")
    const [generationError, setGenerationError] = useState("")
    const [saveError, setSaveError] = useState("")
    const [validationError, setValidationError] = useState("")
    const [initialForm, setInitialForm] = useState(null)

    const [form, setForm] = useState({
        title: "",
        synopsis: "",
        cover_image_url: ""
    })
    const localDraft = useLocalDraft({
        key: `dreamers-palette:novel:${id || "new"}`,
        form,
        initialForm,
        setForm
    })
    const navigationGuard = useUnsavedChanges(localDraft.isDirty && !saving)

    useEffect(() => {
        async function initialize() {
            try {
                if (isEditMode) {
                    const existingBook = await getMyBookById(id)

                    if (!existingBook) {
                        navigate(DASHBOARD_ROUTES.BOOKS)
                        return
                    }

                    const nextForm = {
                        title: existingBook.title || "",
                        synopsis: existingBook.synopsis || "",
                        cover_image_url: existingBook.cover_image_url || ""
                    }
                    setForm(nextForm)
                    setInitialForm(nextForm)
                } else {
                    setInitialForm({
                        title: "",
                        synopsis: "",
                        cover_image_url: ""
                    })
                }
            } catch (err) {
                setSaveError(err.message || "The novel editor could not be loaded. Try returning to Novels and opening it again.")
            } finally {
                setLoading(false)
            }
        }

        initialize()
    }, [id, isEditMode, navigate])

    async function handleSave() {
        if (!form.title.trim()) {
            setValidationError("Enter a novel title.")
            return
        }

        try {
            setSaving(true)
            setSaveError("")
            setValidationError("")

            const payload = {
                title: form.title,
                synopsis: form.synopsis,
                cover_image_url: form.cover_image_url || null
            }

            if (isEditMode) {
                await updateBook(id, payload)
            } else {
                await createBook({
                    ...payload,
                    is_published: false
                })
            }

            localDraft.clearDraft()
            navigationGuard.markSafe()
            navigate(DASHBOARD_ROUTES.BOOKS)
        } catch (err) {
            setSaveError(err.message || "The novel could not be saved. Your changes are still here.")
        } finally {
            setSaving(false)
        }
    }

    async function handleGenerateCover() {
        if (generatingCover) return

        try {
            setGenerationError("")
            setGeneratingCover(true)
            const { imageUrl } = await generateBookCover(coverPrompt)
            setForm((prev) => ({
                ...prev,
                cover_image_url: imageUrl
            }))
        } catch (err) {
            setGenerationError(err.message || "Failed to generate cover image.")
        } finally {
            setGeneratingCover(false)
        }
    }

    async function handleCoverUpload(e) {
        const file = e.target.files?.[0]
        if (!file) return

        try {
            setUploading(true)
            setUploadError("")
            setUploadInfo("")

            await validateImageFile(file)

            const { file: processedFile, wasCompressed } = await compressImageIfNeeded(file)
            if (wasCompressed) {
                const originalKB = (file.size / 1024).toFixed(0)
                const compressedKB = (processedFile.size / 1024).toFixed(0)
                setUploadInfo(`Image optimized: ${originalKB} KB → ${compressedKB} KB`)
            }

            const {
                data: { user }
            } = await supabase.auth.getUser()

            if (!user) throw new Error("Not authenticated")

            const publicUrl = await uploadBackgroundImage(processedFile, user.id)
            setForm((prev) => ({
                ...prev,
                cover_image_url: publicUrl
            }))
        } catch (error) {
            setUploadError(error.message || "Upload failed.")
        } finally {
            setUploading(false)
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
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 className="text-2xl font-semibold tracking-tight">
                        {isEditMode ? "Edit Novel" : "New Novel"}
                    </h2>
                    <p className="text-sm text-neutral-500 mt-1">
                        {isEditMode
                            ? "Refine your long-form world."
                            : "Begin your next literary journey."}
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
                        onClick={() => navigate(DASHBOARD_ROUTES.BOOKS)}
                    >
                        Cancel
                    </Button>
                    <Button onClick={handleSave} disabled={saving || (isEditMode && !initialForm)}>
                        {saving ? "Saving..." : "Save"}
                    </Button>
                </div>
            </div>

            <div className="space-y-4 max-w-full md:max-w-2xl">
                <StatusMessage tone="error">{validationError || saveError}</StatusMessage>
                <FormField label="Novel title" htmlFor="book-title" required>
                    <Input
                        id="book-title"
                        value={form.title}
                        maxLength={160}
                        onChange={(e) => {
                            setValidationError("")
                            setForm({ ...form, title: e.target.value })
                        }}
                    />
                </FormField>

                <FormField label="Synopsis" htmlFor="book-synopsis" hint="A concise introduction for readers.">
                    <Textarea
                        id="book-synopsis"
                        value={form.synopsis}
                        maxLength={2000}
                        onChange={(e) =>
                            setForm({ ...form, synopsis: e.target.value })
                        }
                        rows={5}
                    />
                </FormField>

                <div className="space-y-4">
                    <label className="block text-sm text-neutral-600">
                        Cover Image
                    </label>

                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        onChange={handleCoverUpload}
                        className="text-sm"
                    />

                    {uploading && (
                        <div className="flex items-center gap-2 text-xs text-neutral-500">
                            <div className="h-3 w-3 rounded-full border-2 border-neutral-400 border-t-transparent animate-spin" />
                            Uploading...
                        </div>
                    )}

                    {uploadError && (
                        <p className="text-xs text-red-500">
                            {uploadError}
                        </p>
                    )}

                    {uploadInfo && !uploadError && (
                        <p className="text-xs text-green-600">
                            {uploadInfo}
                        </p>
                    )}
                </div>

                <FormField label="Cover image URL" htmlFor="book-cover-url" hint="Optional. Uploading or generating a cover fills this automatically.">
                    <Input
                        id="book-cover-url"
                        type="url"
                        value={form.cover_image_url}
                        onChange={(e) =>
                            setForm({ ...form, cover_image_url: e.target.value })
                        }
                    />
                </FormField>

                <FormField
                    label="Generate a cover with AI"
                    htmlFor="book-cover-prompt"
                    hint={`Describe the setting, mood, light, and composition. Provider: ${providerLabel}.`}
                >
                    <Input
                        id="book-cover-prompt"
                        placeholder="A distant house at dusk, painterly, no text"
                        value={coverPrompt}
                        onChange={(e) => setCoverPrompt(e.target.value)}
                        maxLength={500}
                    />
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="subtle"
                            size="sm"
                            onClick={handleGenerateCover}
                            disabled={generatingCover || !coverPrompt.trim()}
                        >
                            {generatingCover ? "Generating..." : "Generate Cover with AI"}
                        </Button>
                    </div>
                    {generationError && (
                        <p className="text-xs text-red-500">
                            {generationError}
                        </p>
                    )}
                </FormField>

                {form.cover_image_url && (
                    <div className="space-y-2">
                        <img
                            src={form.cover_image_url}
                            alt="Cover preview"
                            className="w-full h-56 object-cover rounded-lg border border-neutral-200"
                        />
                    </div>
                )}
            </div>

            <ConfirmDialog
                open={navigationGuard.blocked}
                title="Discard unsaved novel?"
                description="Your latest changes have not been saved."
                confirmLabel="Discard changes"
                onClose={navigationGuard.reset}
                onConfirm={navigationGuard.proceed}
            />
        </div>
    )
}
