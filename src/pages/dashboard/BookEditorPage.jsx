import { useEffect, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"

import Button from "@/components/ui/Button"
import Input from "@/components/ui/Input"
import Textarea from "@/components/ui/Textarea"
import { generateBookCover, getAiProviderLabel } from "@/services/aiAssetService"

import {
    getMyBooks,
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
    const [generatingCover, setGeneratingCover] = useState(false)
    const [coverPrompt, setCoverPrompt] = useState("")
    const [generationError, setGenerationError] = useState("")

    const [form, setForm] = useState({
        title: "",
        synopsis: "",
        cover_image_url: ""
    })

    useEffect(() => {
        async function initialize() {
            try {
                if (isEditMode) {
                    const books = await getMyBooks()
                    const existingBook = books.find((book) => book.id === id)

                    if (!existingBook) {
                        navigate(DASHBOARD_ROUTES.BOOKS)
                        return
                    }

                    setForm((prev) => ({
                        ...prev,
                        title: existingBook.title || "",
                        synopsis: existingBook.synopsis || "",
                        cover_image_url: existingBook.cover_image_url || ""
                    }))
                }
            } catch (err) {
                console.error("Failed to initialize editor:", err)
            } finally {
                setLoading(false)
            }
        }

        initialize()
    }, [id, isEditMode, navigate])

    async function handleSave() {
        if (!form.title.trim()) return

        try {
            setSaving(true)

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

            navigate(DASHBOARD_ROUTES.BOOKS)
        } catch (err) {
            console.error("Save failed:", err)
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
            console.error("Cover generation failed:", err)
            setGenerationError(err.message || "Failed to generate cover image.")
        } finally {
            setGeneratingCover(false)
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
                        {isEditMode ? "Edit Book" : "New Book"}
                    </h2>
                    <p className="text-sm text-neutral-500 mt-1">
                        {isEditMode
                            ? "Refine your long-form world."
                            : "Begin your next literary journey."}
                    </p>
                </div>

                <div className="flex gap-3">
                    <Button
                        variant="ghost"
                        onClick={() => navigate(DASHBOARD_ROUTES.BOOKS)}
                    >
                        Cancel
                    </Button>
                    <Button onClick={handleSave} disabled={saving}>
                        {saving ? "Saving..." : "Save"}
                    </Button>
                </div>
            </div>

            <div className="space-y-4 max-w-full md:max-w-2xl">
                <Input
                    placeholder="Book title"
                    value={form.title}
                    onChange={(e) =>
                        setForm({ ...form, title: e.target.value })
                    }
                />

                <Textarea
                    placeholder="Synopsis"
                    value={form.synopsis}
                    onChange={(e) =>
                        setForm({ ...form, synopsis: e.target.value })
                    }
                    rows={5}
                />

                <Input
                    placeholder="Cover image URL (optional)"
                    value={form.cover_image_url}
                    onChange={(e) =>
                        setForm({ ...form, cover_image_url: e.target.value })
                    }
                />

                <div className="space-y-2">
                    <Input
                        placeholder="AI prompt for cover (optional)"
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
                        <span className="text-xs text-neutral-500">
                            Provider: {providerLabel}
                        </span>
                    </div>
                    {generationError && (
                        <p className="text-xs text-red-500">
                            {generationError}
                        </p>
                    )}
                </div>

                {form.cover_image_url && (
                    <div className="space-y-2">
                        <img
                            src={form.cover_image_url}
                            alt="Cover preview"
                            className="w-full h-56 object-cover rounded-lg border border-neutral-200 dark:border-neutral-800"
                        />
                    </div>
                )}
            </div>
        </div>
    )
}
