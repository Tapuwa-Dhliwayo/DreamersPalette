import { useCallback, useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import Button from "@/components/ui/Button"
import Badge from "@/components/ui/Badge"
import StatusMessage from "@/components/ui/StatusMessage"
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton"
import { getMyProfile } from "@/services/profileService"
import { getMyCollections, getMyPoems } from "@/services/contentService"
import { getMyBooks, getMyChaptersByBook } from "@/services/bookService"
import { DASHBOARD_ROUTES } from "@/app/routes"
import { useAuth } from "@/hooks/useAuth"
import { can } from "@/services/authService"
import { formatDate } from "@/utils/formatDate"

export default function DashboardHome() {
    const { user } = useAuth()
    const mayReadCollections = can(user, "collections.view")
    const mayReadPoems = can(user, "poems.view")
    const mayReadBooks = can(user, "books.view")
    const mayReadChapters = can(user, "chapters.view")
    const [profile, setProfile] = useState(null)
    const [collections, setCollections] = useState([])
    const [poems, setPoems] = useState([])
    const [books, setBooks] = useState([])
    const [chapters, setChapters] = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState("")

    const loadDashboard = useCallback(() => Promise.all([
        getMyProfile(),
        mayReadCollections ? getMyCollections() : [],
        mayReadPoems ? getMyPoems() : [],
        mayReadBooks ? getMyBooks() : [],
    ]).then(async ([profileData, collectionData, poemData, bookData]) => {
        const chapterData = (await Promise.all(
            mayReadChapters ? bookData.map(book => getMyChaptersByBook(book.id)) : []
        )).flat()
        setProfile(profileData)
        setCollections(collectionData)
        setPoems(poemData)
        setBooks(bookData)
        setChapters(chapterData)
    }).catch(err => {
        setError(err.message || "Your studio overview could not be loaded.")
    }).finally(() => setLoading(false)), [mayReadCollections, mayReadPoems, mayReadBooks, mayReadChapters])

    useEffect(() => { void loadDashboard() }, [loadDashboard])

    function retryDashboard() {
        setLoading(true)
        setError("")
        void loadDashboard()
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
                to: item.studio_manuscript_id ? (item.studio_manuscript_archived ? "/studio" : `/studio/manuscripts/${item.studio_manuscript_id}`) : DASHBOARD_ROUTES.BOOK_EDIT(item.id)
            }))
        const chapterDrafts = chapters
            .filter((item) => !item.is_published)
            .map((item) => ({
                ...item,
                type: "Chapter",
                updated_at: item.created_at,
                to: item.studio_manuscript_id ? (item.studio_manuscript_archived ? "/studio" : `/studio/manuscripts/${item.studio_manuscript_id}`) : DASHBOARD_ROUTES.CHAPTER_EDIT(item.id)
            }))

        return [...poemDrafts, ...bookDrafts, ...chapterDrafts]
            .sort((a, b) => new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at))
            .slice(0, 5)
    }, [books, chapters, poems])

    const totals = [
        { label: "Collections", value: collections.length, to: DASHBOARD_ROUTES.COLLECTIONS, allowed: mayReadCollections },
        { label: "Poems", value: poems.length, to: DASHBOARD_ROUTES.POEMS, allowed: mayReadPoems },
        { label: "Novels", value: books.length, to: DASHBOARD_ROUTES.BOOKS, allowed: mayReadBooks },
        { label: "Chapters", value: chapters.length, to: DASHBOARD_ROUTES.CHAPTERS, allowed: mayReadChapters }
    ].filter(total => total.allowed)

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

                </div>
                <div className="flex flex-wrap gap-2">
                    {can(user, "poems.create") && <Link to={DASHBOARD_ROUTES.POEM_NEW} className="no-underline!">
                        <Button>New Poem</Button>
                    </Link>}
                    {can(user, "collections.create") && <Link to={DASHBOARD_ROUTES.COLLECTION_NEW} className="no-underline!">
                        <Button variant="subtle">New Collection</Button>
                    </Link>}
                </div>
            </header>

            <StatusMessage tone="error" action={
                error ? <Button size="sm" variant="ghost" onClick={retryDashboard}>Try again</Button> : null
            }>
                {error}
            </StatusMessage>

            {can(user, "reviews.view") && <a href="/studio/reviews" className="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-neutral-900 hover:bg-neutral-100">Open editorial reviews →</a>}
            {totals.length > 0 && <section aria-labelledby="continue-writing-title">
                <div className="flex items-end justify-between gap-4">
                    <div>
                        <h3 id="continue-writing-title" className="text-xl font-semibold">Continue writing</h3>
                        <p className="mt-1 text-sm text-neutral-600">Your most recently updated drafts.</p>
                    </div>
                </div>

                {continueWriting.length === 0 ? (
                    <div className="mt-5 border-y border-neutral-200 py-8">
                        <p className="m-0 text-sm text-neutral-600">You have no unfinished writing.</p>
                        {can(user, "poems.create") && <Link to={DASHBOARD_ROUTES.POEM_NEW} className="mt-3 inline-flex text-sm font-medium text-neutral-900">
                            Start a poem
                        </Link>}
                    </div>
                ) : (
                    <div className="mt-5 divide-y divide-neutral-200 border-y border-neutral-200">
                        {continueWriting.map((item) => (
                            <a
                                key={`${item.type}-${item.id}`}
                                href={item.to}
                                className="flex items-center justify-between gap-4 py-4 text-neutral-900 no-underline! transition-colors hover:bg-neutral-100/70"
                            >
                                <div className="min-w-0">
                                    <p className="m-0 truncate font-medium">{item.title}</p>
                                    <p className="mt-1 text-xs text-neutral-600">
                                        {item.type} · Updated {formatDate(item.updated_at || item.created_at)}
                                    </p>
                                </div>
                                <Badge>Draft</Badge>
                            </a>
                        ))}
                    </div>
                )}
            </section>}

            {totals.length > 0 && <section className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px]">
                <div>
                    <h3 className="text-lg font-semibold">Create</h3>
                    <div className="mt-4 flex flex-wrap gap-2">
                        {can(user, "books.create") && <Link to={DASHBOARD_ROUTES.BOOK_NEW} className="no-underline!">
                            <Button variant="subtle">New Novel</Button>
                        </Link>}
                        {can(user, "chapters.create") && <Link to={DASHBOARD_ROUTES.CHAPTER_NEW} className="no-underline!">
                            <Button variant="subtle">New Chapter</Button>
                        </Link>}
                        {can(user, "trash.view") && <Link to={DASHBOARD_ROUTES.TRASH} className="no-underline!">
                            <Button variant="ghost">Review Trash</Button>
                        </Link>}
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
            </section>}
        </div>
    )
}
