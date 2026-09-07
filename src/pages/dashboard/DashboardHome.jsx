import { useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import Button from "@/components/ui/Button"
import Badge from "@/components/ui/Badge"
import StatusMessage from "@/components/ui/StatusMessage"
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton"
import { getMyProfile } from "@/services/profileService"
import { getMyCollections, getMyPoems } from "@/services/contentService"
import { getMyBooks, getMyChaptersByBook } from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"
import { formatDate } from "@/utils/formatDate"

export default function DashboardHome() {
    const [profile, setProfile] = useState(null)
    const [collections, setCollections] = useState([])
    const [poems, setPoems] = useState([])
    const [books, setBooks] = useState([])
    const [chapters, setChapters] = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState("")

    useEffect(() => {
        loadDashboard()
    }, [])

    async function loadDashboard() {
        try {
            setLoading(true)
            setError("")
            const [profileData, collectionData, poemData, bookData] = await Promise.all([
                getMyProfile(),
                getMyCollections(),
                getMyPoems(),
                getMyBooks()
            ])
            const chapterData = (await Promise.all(
                bookData.map((book) => getMyChaptersByBook(book.id))
            )).flat()
            setProfile(profileData)
            setCollections(collectionData)
            setPoems(poemData)
            setBooks(bookData)
            setChapters(chapterData)
        } catch (err) {
            setError(err.message || "Your studio overview could not be loaded.")
        } finally {
            setLoading(false)
        }
    }

    const continueWriting = useMemo(() => {
        const poemDrafts = poems
            .filter((item) => !item.is_published)
            .map((item) => ({
                ...item,
                type: "Poem",
                to: DASHBOARD_ROUTES.POEM_EDIT(item.id)
            }))
        const bookDrafts = books
            .filter((item) => !item.is_published)
            .map((item) => ({
                ...item,
                type: "Novel",
                to: DASHBOARD_ROUTES.BOOK_EDIT(item.id)
            }))
        const chapterDrafts = chapters
            .filter((item) => !item.is_published)
            .map((item) => ({
                ...item,
                type: "Chapter",
                updated_at: item.created_at,
                to: DASHBOARD_ROUTES.CHAPTER_EDIT(item.id)
            }))

        return [...poemDrafts, ...bookDrafts, ...chapterDrafts]
            .sort((a, b) => new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at))
            .slice(0, 5)
    }, [books, chapters, poems])

    const totals = [
        { label: "Collections", value: collections.length, to: DASHBOARD_ROUTES.COLLECTIONS },
        { label: "Poems", value: poems.length, to: DASHBOARD_ROUTES.POEMS },
        { label: "Novels", value: books.length, to: DASHBOARD_ROUTES.BOOKS },
        { label: "Chapters", value: chapters.length, to: DASHBOARD_ROUTES.CHAPTERS }
    ]

    if (loading) {
        return (
            <div className="min-h-0 flex-1 overflow-y-auto">
                <DashboardSkeleton rows={5} />
            </div>
        )
    }

    return (
        <div className="min-h-0 flex-1 space-y-10 overflow-y-auto">
            <header className="flex flex-col gap-5 border-b border-neutral-200 pb-7 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 className="text-2xl font-semibold tracking-tight">
                        Welcome back, {profile?.display_name || "Author"}
                    </h2>
                    <p className="mt-1 text-sm text-neutral-600">
                        Continue the work in progress, or begin a new piece.
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Link to={DASHBOARD_ROUTES.POEM_NEW} className="no-underline!">
                        <Button>New Poem</Button>
                    </Link>
                    <Link to={DASHBOARD_ROUTES.COLLECTION_NEW} className="no-underline!">
                        <Button variant="subtle">New Collection</Button>
                    </Link>
                </div>
            </header>

            <StatusMessage tone="error" action={
                error ? <Button size="sm" variant="ghost" onClick={loadDashboard}>Try again</Button> : null
            }>
                {error}
            </StatusMessage>

            <section aria-labelledby="continue-writing-title">
                <div className="flex items-end justify-between gap-4">
                    <div>
                        <h3 id="continue-writing-title" className="text-xl font-semibold">Continue writing</h3>
                        <p className="mt-1 text-sm text-neutral-600">Your most recently updated drafts.</p>
                    </div>
                </div>

                {continueWriting.length === 0 ? (
                    <div className="mt-5 border-y border-neutral-200 py-8">
                        <p className="m-0 text-sm text-neutral-600">You have no unfinished writing.</p>
                        <Link to={DASHBOARD_ROUTES.POEM_NEW} className="mt-3 inline-flex text-sm font-medium text-neutral-900">
                            Start a poem
                        </Link>
                    </div>
                ) : (
                    <div className="mt-5 divide-y divide-neutral-200 border-y border-neutral-200">
                        {continueWriting.map((item) => (
                            <Link
                                key={`${item.type}-${item.id}`}
                                to={item.to}
                                className="flex items-center justify-between gap-4 py-4 text-neutral-900 no-underline! transition-colors hover:bg-neutral-100/70"
                            >
                                <div className="min-w-0">
                                    <p className="m-0 truncate font-medium">{item.title}</p>
                                    <p className="mt-1 text-xs text-neutral-600">
                                        {item.type} · Updated {formatDate(item.updated_at || item.created_at)}
                                    </p>
                                </div>
                                <Badge>Draft</Badge>
                            </Link>
                        ))}
                    </div>
                )}
            </section>

            <section className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px]">
                <div>
                    <h3 className="text-lg font-semibold">Create</h3>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Link to={DASHBOARD_ROUTES.BOOK_NEW} className="no-underline!">
                            <Button variant="subtle">New Novel</Button>
                        </Link>
                        <Link to={DASHBOARD_ROUTES.CHAPTER_NEW} className="no-underline!">
                            <Button variant="subtle">New Chapter</Button>
                        </Link>
                        <Link to={DASHBOARD_ROUTES.TRASH} className="no-underline!">
                            <Button variant="ghost">Review Trash</Button>
                        </Link>
                    </div>
                </div>

                <div>
                    <h3 className="text-lg font-semibold">Studio overview</h3>
                    <dl className="mt-4 divide-y divide-neutral-200 border-y border-neutral-200">
                        {totals.map((item) => (
                            <div key={item.label} className="flex items-center justify-between py-3">
                                <dt>
                                    <Link to={item.to} className="text-sm text-neutral-700 no-underline! hover:text-neutral-950">
                                        {item.label}
                                    </Link>
                                </dt>
                                <dd className="m-0 text-sm font-semibold tabular-nums text-neutral-950">{item.value}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>
        </div>
    )
}
