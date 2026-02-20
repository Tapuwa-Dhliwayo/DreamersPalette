import { useEffect, useState } from "react"
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

import { slugify } from "@/utils/slugify"

export default function Collections() {
    const [collections, setCollections] = useState([])
    const [loading, setLoading] = useState(true)

    const [isModalOpen, setIsModalOpen] = useState(false)
    const [editingCollection, setEditingCollection] = useState(null)

    const [form, setForm] = useState({
        title: "",
        description: ""
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
            description: collection.description || ""
        })
        setIsModalOpen(true)
    }

    async function handleSubmit() {
        try {
            if (editingCollection) {
                await updateCollection(editingCollection.id, {
                    title: form.title,
                    description: form.description
                })
            } else {
                const slug = slugify(form.title)

                await createCollection({
                    title: form.title,
                    slug,
                    description: form.description,
                    is_published: false
                })
            }

            setIsModalOpen(false)
            fetchCollections()
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

    return (
        <div className="space-y-10">

            {/* Header */}
            <div className="flex items-center justify-between">
                <h2 className="text-2xl font-semibold tracking-tight">
                    Collections
                </h2>

                <Button onClick={() => {
                    console.log("clicked")
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

                            <div className="flex gap-2">
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