import { useEffect, useState } from "react"
import { useParams, Link } from "react-router-dom"

import {
    getBookBySlug,
    getPublishedChaptersByBook
} from "@/services/bookService"

import { PUBLIC_ROUTES } from "@/app/routes"

export default function BookDetailPage() {
    const { slug } = useParams()

    const [book, setBook] = useState(null)
    const [chapters, setChapters] = useState([])
    const [loading, setLoading] = useState(true)
    const [notFound, setNotFound] = useState(false)

    useEffect(() => {
        async function load() {
            try {
                const bookData = await getBookBySlug(slug)
                const chaptersData = await getPublishedChaptersByBook(slug)

                setBook(bookData)
                setChapters(chaptersData)
            } catch (err) {
                console.error("Book not found:", err)
                setNotFound(true)
            } finally {
                setLoading(false)
            }
        }

        load()
    }, [slug])

    if (loading) {
        return (
            <div className="text-sm text-neutral-500">
                Loading book...
            </div>
        )
    }

    if (notFound || !book) {
        return (
            <div className="py-20 text-center space-y-4">
                <h2 className="text-2xl font-semibold">
                    Book not found
                </h2>
                <p className="text-neutral-500">
                    It may not be published yet.
                </p>
            </div>
        )
    }

    return (
        <article className="reader-fade-in space-y-8 md:space-y-20 pt-4 md:pt-12">

            {/* Header */}
            <header className="space-y-4 md:space-y-6 text-center">

                {book.cover_image_url && (
                    <div className="flex justify-center">
                        <img
                            src={book.cover_image_url}
                            alt={book.title}
                            className="max-h-56 md:max-h-72 rounded-2xl shadow-lg object-cover"
                        />
                    </div>
                )}

                <h1 className="text-2xl sm:text-3xl md:text-5xl font-serif tracking-tight accent-underline">
                    {book.title}
                </h1>

                {book.synopsis && (
                    <p className="text-sm md:text-lg opacity-70 max-w-2xl mx-auto leading-relaxed">
                        {book.synopsis}
                    </p>
                )}
            </header>

            {/* Chapters */}
            <section className="space-y-5 md:space-y-8 border-t border-white/10 pt-6 md:pt-12">

                {chapters.length === 0 && (
                    <p className="opacity-60 text-center">
                        No published chapters yet.
                    </p>
                )}

                {chapters.map((chapter) => (
                    <Link
                        key={chapter.id}
                        to={PUBLIC_ROUTES.CHAPTER(slug, chapter.chapter_number)}
                        className="block group transition-all duration-200"
                    >
                        <div className="space-y-2">

                            <h2 className="text-xl md:text-2xl font-serif leading-relaxed group-hover:opacity-70 transition">
                                <span className="opacity-50 mr-3">
                                    {chapter.chapter_number}.
                                </span>
                                {chapter.title}
                            </h2>

                            {chapter.is_preview && (
                                <span className="text-xs opacity-50">
                                    Preview
                                </span>
                            )}

                        </div>
                    </Link>
                ))}

            </section>

        </article>
    )
}
