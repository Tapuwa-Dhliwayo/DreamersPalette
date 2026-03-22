import { useEffect, useState } from "react"
import { useNavigate } from "react-router-dom"

import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Select from "@/components/ui/Select"

import {
    getMyBooks,
    getMyChaptersByBook,
    deleteChapter,
    toggleChapterPublish
} from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"

export default function Chapters() {
    const navigate = useNavigate()

    const [books, setBooks] = useState([])
    const [chapters, setChapters] = useState([])
    const [selectedBookId, setSelectedBookId] = useState("")
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            await initialize()
        }
        load()
    }, [])

    async function initialize() {
        try {
            const booksData = await getMyBooks()
            setBooks(booksData)

            const defaultBookId = booksData[0]?.id || ""
            setSelectedBookId(defaultBookId)

            if (defaultBookId) {
                const chaptersData = await getMyChaptersByBook(defaultBookId)
                setChapters(chaptersData)
            } else {
                setChapters([])
            }
        } catch (err) {
            console.error("Failed to load chapters:", err)
        } finally {
            setLoading(false)
        }
    }

    async function handleBookChange(bookId) {
        setSelectedBookId(bookId)
        setLoading(true)

        try {
            if (!bookId) {
                setChapters([])
                return
            }

            const data = await getMyChaptersByBook(bookId)
            setChapters(data)
        } catch (err) {
            console.error("Filter failed:", err)
        } finally {
            setLoading(false)
        }
    }

    async function handleDelete(id) {
        try {
            await deleteChapter(id)
            await handleBookChange(selectedBookId)
        } catch (err) {
            console.error("Delete failed:", err)
        }
    }

    async function handleTogglePublish(chapter) {
        try {
            await toggleChapterPublish(chapter.id, !chapter.is_published)
            await handleBookChange(selectedBookId)
        } catch (err) {
            console.error("Toggle failed:", err)
        }
    }

    return (
        <div className="space-y-10">
            <div className="flex items-center justify-between">
                <h2 className="text-2xl font-semibold tracking-tight">
                    Chapters
                </h2>
                <Button
                    onClick={() => navigate(`${DASHBOARD_ROUTES.CHAPTER_NEW}${selectedBookId ? `?bookId=${selectedBookId}` : ""}`)}
                    disabled={!selectedBookId}
                >
                    New Chapter
                </Button>
            </div>

            {books.length > 0 && (
                <div className="max-w-xs">
                    <Select
                        value={selectedBookId}
                        onChange={(e) => handleBookChange(e.target.value)}
                    >
                        {books.map((book) => (
                            <option key={book.id} value={book.id}>
                                {book.title}
                            </option>
                        ))}
                    </Select>
                </div>
            )}

            {loading && (
                <div className="text-sm text-neutral-500">
                    Loading chapters...
                </div>
            )}

            {!loading && books.length === 0 && (
                <Card className="p-10 text-center space-y-3">
                    <div className="text-lg font-medium">
                        No books found
                    </div>
                    <div className="text-sm text-neutral-500">
                        Create a book before adding chapters.
                    </div>
                    <div>
                        <Button onClick={() => navigate(DASHBOARD_ROUTES.BOOK_NEW)}>
                            Create Book
                        </Button>
                    </div>
                </Card>
            )}

            {!loading && books.length > 0 && chapters.length === 0 && (
                <Card className="p-10 text-center space-y-3">
                    <div className="text-lg font-medium">
                        No chapters yet
                    </div>
                    <div className="text-sm text-neutral-500">
                        Add the first chapter to this book.
                    </div>
                    <div>
                        <Button onClick={() => navigate(`${DASHBOARD_ROUTES.CHAPTER_NEW}?bookId=${selectedBookId}`)}>
                            Create Chapter
                        </Button>
                    </div>
                </Card>
            )}

            <div className="grid gap-6">
                {chapters.map((chapter) => (
                    <Card key={chapter.id} className="p-4 md:p-6 space-y-4">
                        <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 className="text-lg font-medium">
                                    {chapter.chapter_number}. {chapter.title}
                                </h3>
                                <p className="text-xs text-neutral-400 mt-2">
                                    {chapter.is_preview ? "Preview enabled" : "Full chapter"}
                                </p>
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => navigate(DASHBOARD_ROUTES.CHAPTER_EDIT(chapter.id))}
                                >
                                    Edit
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => handleDelete(chapter.id)}
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
                            <span className="text-xs text-neutral-500">
                                {chapter.is_published ? "Published" : "Draft"}
                            </span>

                            <Button
                                variant="subtle"
                                size="sm"
                                onClick={() => handleTogglePublish(chapter)}
                            >
                                {chapter.is_published ? "Unpublish" : "Publish"}
                            </Button>
                        </div>
                    </Card>
                ))}
            </div>
        </div>
    )
}
