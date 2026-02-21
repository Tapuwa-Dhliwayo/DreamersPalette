import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { uploadBackgroundImage } from "@/services/storageService"
import { supabase } from "@/services/supabaseClient"
import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Input from "@/components/ui/Input"
import Textarea from "@/components/ui/Textarea"
import Modal from "@/components/ui/Modal"

import {
    getMyCollections,
    createCollection,
    updateCollection,
    deleteCollection,
    togglePublish
} from "@/services/contentService"

export default function Collections() {
    const [collections, setCollections] = useState([])
    const [loading, setLoading] = useState(true)
    const [uploading, setUploading] = useState(false)

    const [isModalOpen, setIsModalOpen] = useState(false)
    const [editingCollection, setEditingCollection] = useState(null)

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

    async function fetchCollections() {
        try {
            const data = await getMyCollections()
            setCollections(data)
        } catch (err) {
            console.error("Failed to load collections:", err)
        } finally {
            setLoading(false)
        }
    }

    function openCreateModal() {
        setEditingCollection(null)
        setForm({ title: "", description: "" })
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
            theme_text_mode: collection.theme_text_mode
        })
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
                    theme_text_mode: form.theme_text_mode
                })
            } else {
                await createCollection({
                    title: form.title,
                    description: form.description,
                    is_published: false,
                    theme_background_url: form.theme_background_url || null,
                    theme_overlay_opacity: form.theme_overlay_opacity ?? null,
                    accent_color: form.accent_color || null,
                    theme_text_mode: form.theme_text_mode
                })
            }

            setIsModalOpen(false)
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

            const {
                data: { user }
            } = await supabase.auth.getUser()

            if (!user) throw new Error("Not authenticated")

            const publicUrl = await uploadBackgroundImage(
                file,
                user.id
            )

            setForm(prev => ({
                ...prev,
                theme_background_url: publicUrl
            }))
        } catch (error) {
            console.error("Upload failed:", error)
        } finally {
            setUploading(false)
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
                {collections.map((collection) => (
                    <Card key={collection.id} className="p-6 space-y-4">

                        <div className="flex items-start justify-between">

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
                            </div>

                            <div className="flex gap-2 items-center">
                                <Link
                                    to={`/collections/${collection.slug}`}
                                    target="_blank"
                                    className="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 transition no-underline!"
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

            {/* Modal */}

            <Modal
                open={isModalOpen}
                onClose={() => setIsModalOpen(false)}
            >
                <div className="space-y-6">
                    <h3 className="text-lg font-medium">
                        {editingCollection ? "Edit Collection" : "New Collection"}
                    </h3>

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

                    <div className="space-y-6 pt-8 border-t border-neutral-200 dark:border-neutral-800">

                        <h3 className="text-lg font-medium">
                            Theme Settings
                        </h3>

                        {/* Background Upload */}
                        <div className="space-y-4">
                            <label className="block text-sm text-neutral-600 dark:text-neutral-400">
                                Background Image
                            </label>

                            <input
                                type="file"
                                accept="image/*"
                                onChange={handleBackgroundUpload}
                                className="text-sm"
                            />

                            {uploading && (
                                <div className="flex items-center gap-2 text-xs text-neutral-500">
                                    <div className="h-3 w-3 rounded-full border-2 border-neutral-400 border-t-transparent animate-spin" />
                                    Uploading...
                                </div>
                            )}

                            {form.theme_background_url && !uploading && (
                                <div className="space-y-2">
                                    <img
                                        src={form.theme_background_url}
                                        alt="Background preview"
                                        className="w-full h-40 object-cover rounded-lg border border-neutral-200 dark:border-neutral-800"
                                    />
                                    <p className="text-xs text-neutral-500 break-all">
                                        {form.theme_background_url}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Overlay Opacity */}
                        <div className="space-y-2">
                            <label className="text-sm text-neutral-600 dark:text-neutral-400">
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

                        {/* Accent Color */}
                        <div className="space-y-3">
                            <label className="block text-sm text-neutral-600 dark:text-neutral-400">
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
                                    className="h-10 w-10 rounded-md border border-neutral-300 dark:border-neutral-700 bg-transparent cursor-pointer"
                                />

                                <span className="text-xs text-neutral-500">
      {form.accent_color || "#d4d4d8"}
    </span>
                            </div>
                        </div>

                        {/*Text Mode*/}
                        <div className="space-y-3">
                            <label className="block text-sm text-neutral-600 dark:text-neutral-400">
                                Text Mode
                            </label>

                            <div className="flex gap-4">
                                <button
                                    type="button"
                                    onClick={() =>
                                        setForm(prev => ({ ...prev, theme_text_mode: "light" }))
                                    }
                                    className={`px-4 py-2 rounded-lg border text-sm ${
                                        form.theme_text_mode === "light"
                                            ? "border-neutral-900 bg-neutral-900 text-white"
                                            : "border-neutral-300"
                                    }`}
                                >
                                    Light Text
                                </button>

                                <button
                                    type="button"
                                    onClick={() =>
                                        setForm(prev => ({ ...prev, theme_text_mode: "dark" }))
                                    }
                                    className={`px-4 py-2 rounded-lg border text-sm ${
                                        form.theme_text_mode === "dark"
                                            ? "border-neutral-900 bg-neutral-900 text-white"
                                            : "border-neutral-300"
                                    }`}
                                >
                                    Dark Text
                                </button>
                            </div>
                        </div>

                    </div>

                    <div className="flex justify-end gap-3">
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