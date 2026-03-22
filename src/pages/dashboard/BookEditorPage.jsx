import { useEffect, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"

import Button from "@/components/ui/Button"
import Input from "@/components/ui/Input"
import Textarea from "@/components/ui/Textarea"

import {
    getMyBooks,
    createBook,
    updateBook
} from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"

export default function BookEditorPage() {
    const navigate = useNavigate()
    const { id } = useParams()

    const isEditMode = Boolean(id)

    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)

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
            </div>
        </div>
    )
}
