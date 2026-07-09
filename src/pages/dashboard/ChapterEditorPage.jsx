import { useEffect, useState } from "react"
import { useNavigate, useParams, useSearchParams } from "react-router-dom"

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
    const [initialForm, setInitialForm] = useState(null)
    const [validationError, setValidationError] = useState("")
    const [saveError, setSaveError] = useState("")

    const [form, setForm] = useState({
        book_id: "",
        title: "",
        chapter_number: 1,
        content_md: "",
        is_preview: false
    })
    const localDraft = useLocalDraft({
        key: `dreamers-palette:chapter:${id || "new"}`,
        form,
        initialForm,
        setForm
    })
    const navigationGuard = useUnsavedChanges(localDraft.isDirty && !saving)

    useEffect(() => {
        async function initialize() {
            try {
                const booksData = await getMyBooks()
                setBooks(booksData)

                const queryBookId = searchParams.get("bookId")
                const defaultBookId = queryBookId || booksData[0]?.id || ""

                if (isEditMode) {
                    const existingChapter = await getMyChapterById(id)

                    const nextForm = {
                        book_id: existingChapter.book_id || defaultBookId,
                        title: existingChapter.title || "",
                        chapter_number: existingChapter.chapter_number || 1,
                        content_md: existingChapter.content_md || "",
                        is_preview: Boolean(existingChapter.is_preview)
                    }
                    setForm(nextForm)
                    setInitialForm(nextForm)
                } else {
                    const nextForm = {
                        title: "",
                        chapter_number: 1,
                        content_md: "",
                        is_preview: false,
                        book_id: defaultBookId
                    }
                    setForm(nextForm)
                    setInitialForm(nextForm)
                }
            } catch (err) {
                setSaveError(err.message || "The chapter editor could not be loaded. Try returning to Chapters and opening it again.")
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
        ) {
            setValidationError("Choose a novel, enter a title, and use a chapter number of 1 or greater.")
            return
        }

        try {
            setSaving(true)
            setValidationError("")
            setSaveError("")

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

            localDraft.clearDraft()
            navigationGuard.markSafe()
            navigate(DASHBOARD_ROUTES.CHAPTERS)
        } catch (err) {
            setSaveError(err.message || "The chapter could not be saved. Your writing is still here.")
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
                    <EditorSaveState
                        saving={saving}
                        isDirty={localDraft.isDirty}
                        persistedAt={localDraft.persistedAt}
                        recoveredAt={localDraft.recoveredAt}
                        error={saveError}
                    />
                    <Button
                        variant="ghost"
                        onClick={() => navigate(DASHBOARD_ROUTES.CHAPTERS)}
                    >
                        Cancel
                    </Button>

                    <Button
                        onClick={handleSave}
                        disabled={saving || (isEditMode && !initialForm)}
                    >
                        {saving ? "Saving..." : "Save"}
                    </Button>
                </div>
            </div>

            <div className="space-y-4 max-w-full md:max-w-xl">
                <StatusMessage tone="error">{validationError || saveError}</StatusMessage>
                <FormField label="Novel" htmlFor="chapter-book" required>
                    <Select
                        id="chapter-book"
                        value={form.book_id}
                        onChange={(e) => setForm({ ...form, book_id: e.target.value })}
                    >
                        {books.map((book) => (
                            <option key={book.id} value={book.id}>
                                {book.title}
                            </option>
                        ))}
                    </Select>
                </FormField>

                <FormField label="Chapter title" htmlFor="chapter-title" required>
                    <Input
                        id="chapter-title"
                        value={form.title}
                        maxLength={160}
                        onChange={(e) =>
                            setForm({ ...form, title: e.target.value })
                        }
                    />
                </FormField>

                <FormField label="Chapter number" htmlFor="chapter-number" required>
                    <Input
                        id="chapter-number"
                        type="number"
                        min="1"
                        value={form.chapter_number}
                        onChange={(e) =>
                            setForm({
                                ...form,
                                chapter_number: Number(e.target.value)
                            })
                        }
                    />
                </FormField>

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

            <ConfirmDialog
                open={navigationGuard.blocked}
                title="Discard unsaved chapter?"
                description="Your latest changes have not been saved."
                confirmLabel="Discard changes"
                onClose={navigationGuard.reset}
                onConfirm={navigationGuard.proceed}
            />
        </div>
    )
}
