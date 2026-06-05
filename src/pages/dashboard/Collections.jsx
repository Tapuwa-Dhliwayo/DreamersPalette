import { useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import {
    uploadCollectionBackgroundImage,
    validateImageFile,
    compressImageIfNeeded
} from "@/services/storageService"
import { supabase } from "@/services/supabaseClient"
import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Badge from "@/components/ui/Badge"
import Input from "@/components/ui/Input"
import Textarea from "@/components/ui/Textarea"
import Modal from "@/components/ui/Modal"
import Pagination from "@/components/ui/Pagination"
import CollectionThemePreview from "@/components/dashboard/CollectionThemePreview"
import {
    generateCollectionBackground,
    getAiProviderLabel
} from "@/services/aiAssetService"

import {
    getMyCollections,
    getPoemCountsByCollection,
    createCollection,
    updateCollection,
    deleteCollection,
    togglePublish
} from "@/services/contentService"

const DASHBOARD_PAGE_SIZE = 10

export default function Collections() {
    const providerLabel = getAiProviderLabel()
    const [collections, setCollections] = useState([])
    const [loading, setLoading] = useState(true)
    const [uploading, setUploading] = useState(false)
    const [generatingBackground, setGeneratingBackground] = useState(false)
    const [backgroundPrompt, setBackgroundPrompt] = useState("")
    const [generationError, setGenerationError] = useState("")
    const [uploadError, setUploadError] = useState("")
    const [uploadInfo, setUploadInfo] = useState("")

    const [isModalOpen, setIsModalOpen] = useState(false)
    const [editingCollection, setEditingCollection] = useState(null)
    const [poemCounts, setPoemCounts] = useState({})
    const [page, setPage] = useState(1)

    const [form, setForm] = useState({
        title: "",
        description: "",
        theme_background_url: "",
        theme_overlay_opacity: 0.6,
        accent_color: "#d4d4d8",
        theme_text_mode: "light"
    })

    useEffect(() => {
        fetchCollections()
    }, [])

    const paginatedCollections = useMemo(() => {
        const start = (page - 1) * DASHBOARD_PAGE_SIZE
        return collections.slice(start, start + DASHBOARD_PAGE_SIZE)
    }, [collections, page])

    useEffect(() => {
        const totalPages = Math.max(1, Math.ceil(collections.length / DASHBOARD_PAGE_SIZE))
        if (page > totalPages) {
            setPage(totalPages)
        }
    }, [collections.length, page])

    async function fetchCollections() {
        try {
            const [data, counts] = await Promise.all([
                getMyCollections(),
                getPoemCountsByCollection(),
            ])
            setCollections(data)
            setPoemCounts(counts)
        } catch (err) {
            console.error("Failed to load collections:", err)
        } finally {
            setLoading(false)
        }
    }

    function openCreateModal() {
        setEditingCollection(null)
        setForm({
            title: "",
            description: "",
            theme_background_url: "",
            theme_overlay_opacity: 0.6,
            accent_color: "#d4d4d8",
            theme_text_mode: "light"
        })
        setBackgroundPrompt("")
        setGenerationError("")
        setUploadError("")
        setUploadInfo("")
        setIsModalOpen(true)
    }

    function openEditModal(collection) {
        setEditingCollection(collection)
        setForm({
            title: collection.title,
            description: collection.description || "",
            theme_background_url: collection.theme_background_url || "",
            theme_overlay_opacity: collection.theme_overlay_opacity ?? 0.6,
            accent_color: collection.accent_color || "#d4d4d8",
            theme_text_mode: collection.theme_text_mode || "light"
        })
        setBackgroundPrompt("")
        setGenerationError("")
        setUploadError("")
        setUploadInfo("")
        setIsModalOpen(true)
    }

    async function handleSubmit() {
        try {
            if (!form.title.trim()) {
                return
            }

            if (editingCollection) {
                await updateCollection(editingCollection.id, {
                    title: form.title,
                    description: form.description,
                    theme_background_url: form.theme_background_url || null,
                    theme_overlay_opacity: form.theme_overlay_opacity ?? null,
                    accent_color: form.accent_color || null,
                    theme_text_mode: form.theme_text_mode || "light"
                })
            } else {
                await createCollection({
                    title: form.title,
                    description: form.description,
                    is_published: false,
                    theme_background_url: form.theme_background_url || null,
                    theme_overlay_opacity: form.theme_overlay_opacity ?? null,
                    accent_color: form.accent_color || null,
                    theme_text_mode: form.theme_text_mode || "light"
                })
            }

            setIsModalOpen(false)
            setPage(1)
            await fetchCollections()

        } catch (err) {
            console.error("Save failed:", err)
        }
    }

    async function handleDelete(id) {
        try {
            await deleteCollection(id)
            fetchCollections()
        } catch (err) {
            console.error("Delete failed:", err)
        }
    }

    async function handleTogglePublish(collection) {
        try {
            await togglePublish(collection.id, !collection.is_published)
            fetchCollections()
        } catch (err) {
            console.error("Toggle failed:", err)
        }
    }

    async function handleBackgroundUpload(e) {
        const file = e.target.files?.[0]
        if (!file) return

        try {
            setUploading(true)
            setUploadError("")
            setUploadInfo("")

            // Validate file constraints
            await validateImageFile(file)

            // Compress if oversized
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

            const { fullUrl } = await uploadCollectionBackgroundImage(
                processedFile,
                user.id
            )

            setForm(prev => ({
                ...prev,
                theme_background_url: fullUrl
            }))
        } catch (error) {
            console.error("Upload failed:", error)
            setUploadError(error.message || "Upload failed.")
        } finally {
            setUploading(false)
        }
    }

    async function handleGenerateBackground() {
        if (generatingBackground) return

        try {
            setGenerationError("")
            setGeneratingBackground(true)
            const { imageUrl } = await generateCollectionBackground(backgroundPrompt)
            setForm((prev) => ({
                ...prev,
                theme_background_url: imageUrl
            }))
        } catch (error) {
            console.error("AI background generation failed:", error)
            setGenerationError(error.message || "Failed to generate background image.")
        } finally {
            setGeneratingBackground(false)
        }
    }

    return (
        <div className="space-y-10">

            {/* Header */}
            <div className="flex items-center justify-between">
                <h2 className="text-2xl font-semibold tracking-tight">
                    Collections
                </h2>

                <Button onClick={() => {
                    openCreateModal()
                }}>
                    New Collection
                </Button>
            </div>

            {/* Loading */}
            {loading && (
                <div className="text-sm text-neutral-500">
                    Loading collections...
                </div>
            )}

            {/* Empty State */}
            {!loading && collections.length === 0 && (
                <Card className="p-10 text-center space-y-3">
                    <div className="text-lg font-medium">
                        No collections yet
                    </div>
                    <div className="text-sm text-neutral-500">
                        Create your first poetic world.
                    </div>
                    <div>
                        <Button onClick={openCreateModal}>
                            Create Collection
                        </Button>
                    </div>
                </Card>
            )}

            {/* Collection List */}
            <div className="grid gap-6">
                {paginatedCollections.map((collection) => (
                    <Card key={collection.id} className="p-4 md:p-6 space-y-4">

                        <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">

                            <div>
                                <h3 className="text-lg font-medium">
                                    {collection.title}
                                </h3>
                                <p className="text-sm text-neutral-500 mt-1">
                                    {collection.description}
                                </p>
                                <p className="text-xs text-neutral-400 mt-2">
                                    /collections/{collection.slug}
                                </p>
                                <div className="flex items-center gap-2 mt-2">
                                    <Badge>
                                        {poemCounts[collection.id]?.total || 0} {poemCounts[collection.id]?.total === 1 ? "poem" : "poems"}
                                    </Badge>
                                    {(poemCounts[collection.id]?.published || 0) > 0 && (
                                        <Badge variant="success">
                                            {poemCounts[collection.id].published} published
                                        </Badge>
                                    )}
                                </div>
                            </div>

                            <div className="flex gap-2 items-center">
                                <Link
                                    to={`/preview/collections/${collection.slug}`}
                                    target="_blank"
                                    className="text-xs text-neutral-500 hover:text-neutral-900 transition no-underline!"
                                >
                                    View
                                </Link>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => openEditModal(collection)}
                                >
                                    Edit
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => handleDelete(collection.id)}
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
              <span className="text-xs text-neutral-500">
                {collection.is_published ? "Published" : "Draft"}
              </span>

                            <Button
                                variant="subtle"
                                size="sm"
                                onClick={() => handleTogglePublish(collection)}
                            >
                                {collection.is_published ? "Unpublish" : "Publish"}
                            </Button>
                        </div>

                    </Card>
                ))}
            </div>

            <Pagination
                page={page}
                pageSize={DASHBOARD_PAGE_SIZE}
                totalCount={collections.length}
                onPageChange={setPage}
            />

            {/* Modal */}

            <Modal
                open={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                className="md:max-w-6xl"
            >
                <div className="space-y-6">
                    <div>
                        <h3 className="text-lg font-medium">
                            {editingCollection ? "Edit Collection" : "New Collection"}
                        </h3>
                        <p className="mt-1 text-sm text-neutral-500">
                            Tune the collection details and judge the reader theme before saving.
                        </p>
                    </div>

                    <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(360px,420px)] lg:items-start">
                        <div className="space-y-6">
                            <div className="space-y-4">
                                <Input
                                    placeholder="Collection title"
                                    value={form.title}
                                    onChange={(e) =>
                                        setForm({ ...form, title: e.target.value })
                                    }
                                />

                                <Textarea
                                    placeholder="Short description"
                                    value={form.description}
                                    onChange={(e) =>
                                        setForm({ ...form, description: e.target.value })
                                    }
                                />
                            </div>

                            <div className="space-y-6 pt-8 border-t border-neutral-200">

                                <h3 className="text-lg font-medium">
                                    Theme Settings
                                </h3>

                                {/* Background Upload */}
                                <div className="space-y-4">
                                    <label className="block text-sm text-neutral-600">
                                        Background Image
                                    </label>

                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        onChange={handleBackgroundUpload}
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

                                    {form.theme_background_url && !uploading && (
                                        <p className="text-xs text-neutral-500 break-all">
                                            {form.theme_background_url}
                                        </p>
                                    )}

                                    <div className="space-y-2">
                                        <Input
                                            placeholder="AI prompt for background (optional)"
                                            value={backgroundPrompt}
                                            onChange={(e) => setBackgroundPrompt(e.target.value)}
                                            maxLength={500}
                                        />
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Button
                                                type="button"
                                                variant="subtle"
                                                size="sm"
                                                onClick={handleGenerateBackground}
                                                disabled={generatingBackground || !backgroundPrompt.trim()}
                                            >
                                                {generatingBackground ? "Generating..." : "Generate with AI"}
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
                                </div>

                                {/* Overlay Opacity */}
                                <div className="space-y-2">
                                    <label className="text-sm text-neutral-600">
                                        Overlay Opacity
                                    </label>

                                    <input
                                        type="range"
                                        min="0.4"
                                        max="0.75"
                                        step="0.05"
                                        value={form.theme_overlay_opacity || 0.6}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                theme_overlay_opacity: parseFloat(e.target.value)
                                            })
                                        }
                                        className="w-full"
                                    />

                                    <p className="text-xs text-neutral-500">
                                        {form.theme_overlay_opacity || 0.6}
                                    </p>
                                </div>

                                {/* Text Mode */}
                                <div className="space-y-3">
                                    <label className="block text-sm text-neutral-600">
                                        Text Contrast
                                    </label>

                                    <div className="grid grid-cols-2 gap-2 rounded-xl bg-neutral-100 p-1">
                                        {[
                                            { value: "light", label: "Light text" },
                                            { value: "dark", label: "Dark text" }
                                        ].map((option) => (
                                            <button
                                                key={option.value}
                                                type="button"
                                                onClick={() => setForm({ ...form, theme_text_mode: option.value })}
                                                className={`rounded-lg px-3 py-2 text-sm font-medium transition ${
                                                    form.theme_text_mode === option.value
                                                        ? "bg-white text-neutral-950 shadow-sm"
                                                        : "text-neutral-500 hover:text-neutral-900"
                                                }`}
                                            >
                                                {option.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {/* Accent Color */}
                                <div className="space-y-3">
                                    <label className="block text-sm text-neutral-600">
                                        Accent Color
                                    </label>

                                    <div className="flex items-center gap-4">
                                        <input
                                            type="color"
                                            value={form.accent_color || "#d4d4d8"}
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    accent_color: e.target.value
                                                })
                                            }
                                            className="h-10 w-10 rounded-md border border-neutral-300 bg-transparent cursor-pointer"
                                        />

                                        <span className="text-xs text-neutral-500">
                                            {form.accent_color || "#d4d4d8"}
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div className="lg:sticky lg:top-0">
                            <CollectionThemePreview collection={form} />
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-neutral-200 pt-6">
                        <Button
                            variant="ghost"
                            onClick={() => setIsModalOpen(false)}
                        >
                            Cancel
                        </Button>

                        <Button onClick={handleSubmit}>
                            Save
                        </Button>
                    </div>
                </div>
            </Modal>

        </div>
    )
}
