import { useEffect, useState } from "react"
import { useParams, Link } from "react-router-dom"

import {
    getBookBySlug,
    getPublishedChaptersByBookPaginated
} from "@/services/bookService"

import Pagination, { DEFAULT_PAGE_SIZE } from "@/components/ui/Pagination"
import { PUBLIC_ROUTES } from "@/app/routes"

export default function BookDetailPage() {
    const { slug } = useParams()

    const [book, setBook] = useState(null)
    const [chapters, setChapters] = useState([])
    const [loading, setLoading] = useState(true)
    const [chaptersLoading, setChaptersLoading] = useState(false)
    const [notFound, setNotFound] = useState(false)
    const [page, setPage] = useState(1)
    const [totalCount, setTotalCount] = useState(0)

    useEffect(() => {
        async function loadBook() {
            try {
                setLoading(true)
                setNotFound(false)
                setBook(null)
                setChapters([])
                setTotalCount(0)

                const bookData = await getBookBySlug(slug)
                setBook(bookData)
            } catch (err) {
                console.error("Book not found:", err)
                setNotFound(true)
            } finally {
                setLoading(false)
            }
        }

        setPage(1)
        loadBook()
    }, [slug])

    useEffect(() => {
        if (!book || book.slug !== slug) return

        async function loadChapters() {
            try {
                setChaptersLoading(true)
                const { data, count } = await getPublishedChaptersByBookPaginated(slug, page, DEFAULT_PAGE_SIZE)
                setChapters(data)
                setTotalCount(count)
            } catch (err) {
                console.error("Failed to load chapters:", err)
                setChapters([])
                setTotalCount(0)
            } finally {
                setChaptersLoading(false)
            }
        }

        loadChapters()
    }, [slug, page, book])

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

                {chaptersLoading && (
                    <p className="opacity-60 text-center">
                        Loading chapters...
                    </p>
                )}

                {!chaptersLoading && chapters.length === 0 && (
                    <p className="opacity-60 text-center">
                        No published chapters yet.
                    </p>
                )}

                {!chaptersLoading && chapters.map((chapter) => (
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

            <Pagination
                page={page}
                pageSize={DEFAULT_PAGE_SIZE}
                totalCount={totalCount}
                onPageChange={setPage}
            />

        </article>
    )
}
