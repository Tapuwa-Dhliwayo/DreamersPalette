import { useEffect, useState } from "react"
import { Link } from "react-router-dom"

import { Card } from "@/components/ui/Card"
import Button from "@/components/ui/Button"
import Badge from "@/components/ui/Badge"

import { getMyProfile } from "@/services/profileService"
import { getMyCollections, getMyPoems } from "@/services/contentService"
import { getMyBooks, getMyChaptersByBook } from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"

export default function DashboardHome() {
    const [profile, setProfile] = useState(null)
    const [loading, setLoading] = useState(true)

    const [stats, setStats] = useState({
        collections: 0,
        collectionsPublished: 0,
        poems: 0,
        poemsPublished: 0,
        books: 0,
        booksPublished: 0,
        chapters: 0,
        chaptersPublished: 0,
    })

    const [recentPoems, setRecentPoems] = useState([])
    const [recentBooks, setRecentBooks] = useState([])

    useEffect(() => {
        async function load() {
            try {
                const [profileData, collections, poems, books] = await Promise.all([
                    getMyProfile(),
                    getMyCollections(),
                    getMyPoems(),
                    getMyBooks(),
                ])

                setProfile(profileData)

                // Gather all chapters across books
                const chapterResults = await Promise.all(
                    books.map((book) => getMyChaptersByBook(book.id))
                )
                const allChapters = chapterResults.flat()

                setStats({
                    collections: collections.length,
                    collectionsPublished: collections.filter((c) => c.is_published).length,
                    poems: poems.length,
                    poemsPublished: poems.filter((p) => p.is_published).length,
                    books: books.length,
                    booksPublished: books.filter((b) => b.is_published).length,
                    chapters: allChapters.length,
                    chaptersPublished: allChapters.filter((ch) => ch.is_published).length,
                })

                setRecentPoems(poems.slice(0, 4))
                setRecentBooks(books.slice(0, 4))
            } catch (err) {
                console.error("Dashboard load failed:", err)
            } finally {
                setLoading(false)
            }
        }

        load()
    }, [])

    function getGreeting() {
        const hour = new Date().getHours()
        if (hour < 12) return "Good morning"
        if (hour < 18) return "Good afternoon"
        return "Good evening"
    }

    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        })
    }

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <p className="text-sm text-neutral-500">Loading your studio…</p>
            </div>
        )
    }

    const statCards = [
        {
            label: "Collections",
            total: stats.collections,
            published: stats.collectionsPublished,
            to: DASHBOARD_ROUTES.COLLECTIONS,
        },
        {
            label: "Poems",
            total: stats.poems,
            published: stats.poemsPublished,
            to: DASHBOARD_ROUTES.POEMS,
        },
        {
            label: "Books",
            total: stats.books,
            published: stats.booksPublished,
            to: DASHBOARD_ROUTES.BOOKS,
        },
        {
            label: "Chapters",
            total: stats.chapters,
            published: stats.chaptersPublished,
            to: DASHBOARD_ROUTES.CHAPTERS,
        },
    ]

    return (
        <div className="space-y-10">

            {/* Greeting */}
            <div>
                <h2 className="text-2xl font-semibold tracking-tight">
                    {getGreeting()}, {profile?.display_name || "Author"}
                </h2>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                    Here's an overview of your creative world.
                </p>
            </div>

            {/* Stat cards */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {statCards.map((stat) => (
                    <Link key={stat.label} to={stat.to}>
                        <Card className="p-5 hover:ring-1 hover:ring-neutral-300 dark:hover:ring-neutral-700 transition-all">
                            <p className="text-xs uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {stat.label}
                            </p>
                            <p className="text-3xl font-semibold mt-2">
                                {stat.total}
                            </p>
                            <p className="text-xs text-neutral-400 dark:text-neutral-500 mt-1">
                                {stat.published} published
                            </p>
                        </Card>
                    </Link>
                ))}
            </div>

            {/* Quick actions */}
            <div>
                <h3 className="text-lg font-medium tracking-tight mb-4">
                    Quick Actions
                </h3>
                <div className="flex flex-wrap gap-3">
                    <Link to={DASHBOARD_ROUTES.POEM_NEW}>
                        <Button variant="subtle" size="sm">+ New Poem</Button>
                    </Link>
                    <Link to={DASHBOARD_ROUTES.BOOK_NEW}>
                        <Button variant="subtle" size="sm">+ New Book</Button>
                    </Link>
                    <Link to={DASHBOARD_ROUTES.CHAPTER_NEW}>
                        <Button variant="subtle" size="sm">+ New Chapter</Button>
                    </Link>
                    <Link to={DASHBOARD_ROUTES.COLLECTIONS}>
                        <Button variant="subtle" size="sm">+ New Collection</Button>
                    </Link>
                </div>
            </div>

            {/* Recent content */}
            <div className="grid gap-8 lg:grid-cols-2">

                {/* Recent poems */}
                <div>
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-medium tracking-tight">
                            Recent Poems
                        </h3>
                        <Link
                            to={DASHBOARD_ROUTES.POEMS}
                            className="text-xs text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300 transition-colors"
                        >
                            View all →
                        </Link>
                    </div>

                    {recentPoems.length === 0 ? (
                        <Card className="p-6 text-center">
                            <p className="text-sm text-neutral-500">No poems yet. Start writing!</p>
                        </Card>
                    ) : (
                        <div className="space-y-3">
                            {recentPoems.map((poem) => (
                                <Link
                                    key={poem.id}
                                    to={DASHBOARD_ROUTES.POEM_EDIT(poem.id)}
                                >
                                    <Card className="p-4 flex items-center justify-between hover:ring-1 hover:ring-neutral-300 dark:hover:ring-neutral-700 transition-all">
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium truncate">
                                                {poem.title}
                                            </p>
                                            <p className="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                                                {formatDate(poem.created_at)}
                                            </p>
                                        </div>
                                        <Badge variant={poem.is_published ? "success" : "default"}>
                                            {poem.is_published ? "Live" : "Draft"}
                                        </Badge>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                {/* Recent books */}
                <div>
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-medium tracking-tight">
                            Recent Books
                        </h3>
                        <Link
                            to={DASHBOARD_ROUTES.BOOKS}
                            className="text-xs text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300 transition-colors"
                        >
                            View all →
                        </Link>
                    </div>

                    {recentBooks.length === 0 ? (
                        <Card className="p-6 text-center">
                            <p className="text-sm text-neutral-500">No books yet. Begin your first novel!</p>
                        </Card>
                    ) : (
                        <div className="space-y-3">
                            {recentBooks.map((book) => (
                                <Link
                                    key={book.id}
                                    to={DASHBOARD_ROUTES.BOOK_EDIT(book.id)}
                                >
                                    <Card className="p-4 flex items-center justify-between hover:ring-1 hover:ring-neutral-300 dark:hover:ring-neutral-700 transition-all">
                                        <div className="min-w-0 flex items-center gap-3">
                                            {book.cover_image_url ? (
                                                <img
                                                    src={book.cover_image_url}
                                                    alt=""
                                                    className="h-10 w-8 rounded object-cover shrink-0"
                                                />
                                            ) : (
                                                <div className="h-10 w-8 rounded bg-neutral-200 dark:bg-neutral-800 shrink-0" />
                                            )}
                                            <div className="min-w-0">
                                                <p className="text-sm font-medium truncate">
                                                    {book.title}
                                                </p>
                                                <p className="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">
                                                    {formatDate(book.created_at)}
                                                </p>
                                            </div>
                                        </div>
                                        <Badge variant={book.is_published ? "success" : "default"}>
                                            {book.is_published ? "Live" : "Draft"}
                                        </Badge>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

            </div>

        </div>
    )
}