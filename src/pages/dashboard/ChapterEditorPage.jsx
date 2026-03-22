import { useEffect, useState } from "react"
import { useNavigate, useParams, useSearchParams } from "react-router-dom"

import Button from "@/components/ui/Button"
import Input from "@/components/ui/Input"
import EditorPanel from "@/components/editor/EditorPanel"

import {
    getMyBooks,
    createChapter,
    updateChapter,
    getMyChapterById
} from "@/services/bookService"

import { DASHBOARD_ROUTES } from "@/app/routes"

export default function ChapterEditorPage() {
    const navigate = useNavigate()
    const { id } = useParams()
    const [searchParams] = useSearchParams()

    const isEditMode = Boolean(id)

    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)
    const [books, setBooks] = useState([])

    const [form, setForm] = useState({
        book_id: "",
        title: "",
        chapter_number: 1,
        content_md: "",
        is_preview: false
    })

    useEffect(() => {
        async function initialize() {
            try {
                const booksData = await getMyBooks()
                setBooks(booksData)

                const queryBookId = searchParams.get("bookId")
                const defaultBookId = queryBookId || booksData[0]?.id || ""

                if (isEditMode) {
                    const existingChapter = await getMyChapterById(id)

                    setForm({
                        book_id: existingChapter.book_id || defaultBookId,
                        title: existingChapter.title || "",
                        chapter_number: existingChapter.chapter_number || 1,
                        content_md: existingChapter.content_md || "",
                        is_preview: Boolean(existingChapter.is_preview)
                    })
                } else {
                    setForm((prev) => ({
                        ...prev,
                        book_id: defaultBookId
                    }))
                }
            } catch (err) {
                console.error("Failed to initialize editor:", err)
                navigate(DASHBOARD_ROUTES.CHAPTERS)
            } finally {
                setLoading(false)
            }
        }

        initialize()
    }, [id, isEditMode, navigate, searchParams])

    async function handleSave() {
        const chapterNumber = Number(form.chapter_number)

        if (
            !form.title.trim() ||
            !form.book_id ||
            Number.isNaN(chapterNumber) ||
            chapterNumber < 1
        ) return

        try {
            setSaving(true)

            const payload = {
                book_id: form.book_id,
                title: form.title,
                chapter_number: chapterNumber,
                content_md: form.content_md,
                is_preview: form.is_preview
            }

            if (isEditMode) {
                await updateChapter(id, payload)
            } else {
                await createChapter({
                    ...payload,
                    is_published: false
                })
            }

            navigate(DASHBOARD_ROUTES.CHAPTERS)
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
                        {isEditMode ? "Edit Chapter" : "New Chapter"}
                    </h2>
                    <p className="text-sm text-neutral-500 mt-1">
                        {isEditMode
                            ? "Refine your chapter."
                            : "Write and preview your chapter."}
                    </p>
                </div>

                <div className="flex gap-3">
                    <Button
                        variant="ghost"
                        onClick={() => navigate(DASHBOARD_ROUTES.CHAPTERS)}
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

            <div className="space-y-4 max-w-full md:max-w-xl">
                <select
                    value={form.book_id}
                    onChange={(e) => setForm({ ...form, book_id: e.target.value })}
                    className="w-full border border-neutral-200 rounded-xl p-3 text-sm bg-white"
                >
                    {books.map((book) => (
                        <option key={book.id} value={book.id}>
                            {book.title}
                        </option>
                    ))}
                </select>

                <Input
                    placeholder="Chapter title"
                    value={form.title}
                    onChange={(e) =>
                        setForm({ ...form, title: e.target.value })
                    }
                />

                <Input
                    type="number"
                    min="1"
                    placeholder="Chapter number"
                    value={form.chapter_number}
                    onChange={(e) =>
                        setForm({
                            ...form,
                            chapter_number: Number(e.target.value)
                        })
                    }
                />

                <label className="flex items-center gap-2 text-sm text-neutral-600">
                    <input
                        type="checkbox"
                        checked={form.is_preview}
                        onChange={(e) =>
                            setForm({ ...form, is_preview: e.target.checked })
                        }
                    />
                    Mark chapter as preview
                </label>
            </div>

            <EditorPanel
                value={form.content_md}
                onChange={(value) =>
                    setForm({ ...form, content_md: value })
                }
                placeholder="Write chapter content in markdown..."
            />
        </div>
    )
}
