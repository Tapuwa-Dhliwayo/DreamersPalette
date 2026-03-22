import { useEffect, useState } from "react"
import { useNavigate } from "react-router-dom"

import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"

import {
    getMyBooks,
    deleteBook,
    toggleBookPublish
} from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"

export default function Books() {
    const navigate = useNavigate()

    const [books, setBooks] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            await initialize()
        }
        load()
    }, [])

    async function initialize() {
        try {
            const data = await getMyBooks()
            setBooks(data)
        } catch (err) {
            console.error("Failed to load books:", err)
        } finally {
            setLoading(false)
        }
    }

    async function handleDelete(id) {
        try {
            await deleteBook(id)
            await initialize()
        } catch (err) {
            console.error("Delete failed:", err)
        }
    }

    async function handleTogglePublish(book) {
        try {
            await toggleBookPublish(book.id, !book.is_published)
            await initialize()
        } catch (err) {
            console.error("Toggle failed:", err)
        }
    }

    return (
        <div className="space-y-10">
            <div className="flex items-center justify-between">
                <h2 className="text-2xl font-semibold tracking-tight">
                    Books
                </h2>
                <Button onClick={() => navigate(DASHBOARD_ROUTES.BOOK_NEW)}>
                    New Book
                </Button>
            </div>

            {loading && (
                <div className="text-sm text-neutral-500">
                    Loading books...
                </div>
            )}

            {!loading && books.length === 0 && (
                <Card className="p-10 text-center space-y-3">
                    <div className="text-lg font-medium">
                        No books yet
                    </div>
                    <div className="text-sm text-neutral-500">
                        Start your next long-form world.
                    </div>
                    <div>
                        <Button onClick={() => navigate(DASHBOARD_ROUTES.BOOK_NEW)}>
                            Create Book
                        </Button>
                    </div>
                </Card>
            )}

            <div className="grid gap-6">
                {books.map((book) => (
                    <Card key={book.id} className="p-4 md:p-6 space-y-4">
                        <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 className="text-lg font-medium">
                                    {book.title}
                                </h3>
                                {book.synopsis && (
                                    <p className="text-sm text-neutral-500 mt-1">
                                        {book.synopsis}
                                    </p>
                                )}
                                <p className="text-xs text-neutral-400 mt-2">
                                    /books/{book.slug}
                                </p>
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => navigate(DASHBOARD_ROUTES.BOOK_EDIT(book.id))}
                                >
                                    Edit
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => handleDelete(book.id)}
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
                            <span className="text-xs text-neutral-500">
                                {book.is_published ? "Published" : "Draft"}
                            </span>

                            <Button
                                variant="subtle"
                                size="sm"
                                onClick={() => handleTogglePublish(book)}
                            >
                                {book.is_published ? "Unpublish" : "Publish"}
                            </Button>
                        </div>
                    </Card>
                ))}
            </div>
        </div>
    )
}
