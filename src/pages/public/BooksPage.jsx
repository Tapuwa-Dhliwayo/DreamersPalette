import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { PUBLIC_ROUTES } from "@/app/routes"
import { getPublishedBooks } from "@/services/bookService"

export default function BooksPage() {
    const [books, setBooks] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getPublishedBooks()
                setBooks(data)
            } catch (err) {
                console.error("Failed to load books:", err)
            } finally {
                setLoading(false)
            }
        }

        load()
    }, [])

    return (
        <div className="space-y-20 pt-6 md:pt-12">

            {/* Header */}
            <header className="space-y-6 text-center">
                <h1 className="text-5xl font-serif tracking-tight">
                    Books
                </h1>

                <p className="text-lg opacity-70 max-w-2xl mx-auto leading-relaxed">
                    Long-form works — immersive journeys that unfold
                    beyond a single poem.
                </p>
            </header>

            {/* Books Grid */}
            {loading ? (
                <div className="text-sm text-neutral-500 text-center">
                    Loading books...
                </div>
            ) : books.length === 0 ? (
                <section className="flex justify-center">
                    <div className="
                        rounded-3xl
                        bg-neutral-400/85 dark:bg-neutral-900/80
                        backdrop-blur-md
                        shadow-xl
                        px-8 md:px-12 py-12 md:py-16
                        text-center
                        space-y-6
                        max-w-xl
                    ">

                        <p className="text-base opacity-70 leading-relaxed">
                            Books are being shaped in quiet rooms.
                            Chapters will arrive soon.
                        </p>

                        <div className="pt-4">
                            <Link
                                to={PUBLIC_ROUTES.COLLECTIONS}
                                className="accent-button"
                            >
                                Explore Poetry Collections
                            </Link>
                        </div>

                    </div>
                </section>
            ) : (
                <section className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {books.map((book, index) => (
                        <Link
                            key={book.id}
                            to={PUBLIC_ROUTES.BOOK_DETAIL(book.slug)}
                            className="group block rounded-3xl overflow-hidden bg-neutral-400/85 dark:bg-neutral-900/80 backdrop-blur-md shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-[1.01]"
                            style={{
                                animationDelay: `${index * 80}ms`,
                                animationFillMode: "backwards"
                            }}
                        >
                            {book.cover_image_url && (
                                <div className="h-48 overflow-hidden">
                                    <img
                                        src={book.cover_image_url}
                                        alt={book.title}
                                        className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    />
                                </div>
                            )}

                            <div className="p-6 md:p-10 space-y-3">
                                <h2 className="text-xl md:text-2xl font-serif tracking-tight group-hover:opacity-70 transition">
                                    {book.title}
                                </h2>

                                {book.synopsis && (
                                    <p className="text-sm opacity-60 leading-relaxed line-clamp-3">
                                        {book.synopsis}
                                    </p>
                                )}
                            </div>
                        </Link>
                    ))}
                </section>
            )}

        </div>
    )
}