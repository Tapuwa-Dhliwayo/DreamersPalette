import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { getPublishedCollections, getRecentlyAddedPoems } from "@/services/contentService"
import { getPublishedBooks } from "@/services/bookService"
import { PUBLIC_ROUTES } from "@/app/routes"
import Logo from "@/components/ui/Logo.jsx";
import OptimizedCollectionImage from "@/components/ui/OptimizedCollectionImage"

function formatDate(value) {
    if (!value) return null

    return new Intl.DateTimeFormat("en", {
        month: "short",
        day: "numeric",
        year: "numeric"
    }).format(new Date(value))
}

function FeaturedCard({ to, title, description, imageUrl, usePreviewVariant = false }) {
    return (
        <Link
            to={to}
            className="block overflow-hidden rounded-2xl bg-white/85 shadow-sm backdrop-blur-sm transition hover:shadow-md text-neutral-900!"
        >
            {imageUrl && (
                <OptimizedCollectionImage
                    src={imageUrl}
                    alt={title}
                    usePreviewVariant={usePreviewVariant}
                    className="h-40 w-full object-cover"
                />
            )}

            <div className="p-4 md:p-6">
                <h3 className="mb-2 font-serif text-xl">
                    {title}
                </h3>
                {description && (
                    <p className="line-clamp-3 text-sm opacity-70">
                        {description}
                    </p>
                )}
            </div>
        </Link>
    )
}

export default function HomePage() {
    const [collections, setCollections] = useState([])
    const [books, setBooks] = useState([])
    const [recentPoems, setRecentPoems] = useState([])
    const [collectionsLoading, setCollectionsLoading] = useState(true)
    const [booksLoading, setBooksLoading] = useState(true)
    const [recentPoemsLoading, setRecentPoemsLoading] = useState(true)

    useEffect(() => {
        async function loadHomeContent() {
            setCollectionsLoading(true)
            setBooksLoading(true)
            setRecentPoemsLoading(true)

            try {
                const [collectionsResult, booksResult, recentPoemsResult] = await Promise.allSettled([
                    getPublishedCollections(),
                    getPublishedBooks(),
                    getRecentlyAddedPoems(4),
                ])

                if (collectionsResult.status === "fulfilled") {
                    setCollections(collectionsResult.value || [])
                } else {
                    console.error("Failed to load collections:", collectionsResult.reason)
                    setCollections([])
                }

                if (booksResult.status === "fulfilled") {
                    setBooks(booksResult.value || [])
                } else {
                    console.error("Failed to load books:", booksResult.reason)
                    setBooks([])
                }

                if (recentPoemsResult.status === "fulfilled") {
                    setRecentPoems(recentPoemsResult.value || [])
                } else {
                    console.error("Failed to load recent poems:", recentPoemsResult.reason)
                    setRecentPoems([])
                }
            } finally {
                setCollectionsLoading(false)
                setBooksLoading(false)
                setRecentPoemsLoading(false)
            }
        }

        loadHomeContent()
    }, [])

    return (
        <div className="grid h-full min-h-0 grid-rows-[auto_1fr_auto]">

            {/* ---------------- FIXED TOP AREA ---------------- */}
            <section className="relative py-8 text-center md:py-20">
                <div className="absolute top-2 right-0 md:top-0 md:right-0">
                    <Link
                        to={PUBLIC_ROUTES.LOGIN}
                        className="accent-button"
                    >
                        Author Login
                    </Link>
                </div>

                <div className="md:hidden">
                    <Logo size="md" stacked variant="subtle" />
                </div>
                <div className="hidden md:block">
                    <Logo size="xl" stacked variant="subtle" />
                </div>

                <p className="mx-auto mb-8 md:mb-10 max-w-2xl text-base md:text-lg leading-relaxed opacity-70">
                    A sanctuary for poetry, imagined worlds, and quiet reading.
                </p>

                <div className="flex flex-wrap justify-center gap-8 text-sm text-white">
                    <Link to={PUBLIC_ROUTES.COLLECTIONS}>
                        Browse Collections
                    </Link>
                    <Link to={PUBLIC_ROUTES.BOOKS}>
                        Explore Novels
                    </Link>
                </div>
            </section>

            {/* ---------------- SCROLLABLE BODY ---------------- */}
            <div className="min-h-0 overflow-y-auto">
                <div className="space-y-12 md:space-y-16">

                    {/* ---------------- RECENT POEMS ---------------- */}
                    <section className="py-8 md:pt-2 md:pb-12">
                        <div className="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 className="text-2xl font-serif">
                                    Recently Added Poems
                                </h2>
                                <p className="mt-2 text-sm opacity-60">
                                    New public pieces from the latest collection updates.
                                </p>
                            </div>

                            <Link
                                to={PUBLIC_ROUTES.COLLECTIONS}
                                className="text-sm"
                            >
                                Browse all collections →
                            </Link>
                        </div>

                        {recentPoemsLoading && (
                            <p className="text-sm opacity-60">Loading recent poems...</p>
                        )}

                        {!recentPoemsLoading && recentPoems.length === 0 && (
                            <p className="text-sm opacity-60">
                                No published poems yet.
                            </p>
                        )}

                        {!recentPoemsLoading && recentPoems.length > 0 && (
                            <div className="grid gap-4">
                                {recentPoems.map((poem) => {
                                    const dateLabel = formatDate(poem.created_at)
                                    const collection = Array.isArray(poem.poetry_collections)
                                        ? poem.poetry_collections[0]
                                        : poem.poetry_collections
                                    const hasCollectionBackground = Boolean(collection?.theme_background_url)
                                    const textMode = collection?.theme_text_mode || "light"
                                    const overlayOpacity = collection?.theme_overlay_opacity ?? 0.65
                                    const overlayColor = hasCollectionBackground
                                        ? textMode === "light"
                                            ? `rgba(0,0,0,${overlayOpacity})`
                                            : `rgba(255,255,255,${overlayOpacity})`
                                        : null
                                    const textToneClass = hasCollectionBackground && textMode === "light"
                                        ? "text-neutral-100! reader-text-shadow-light"
                                        : "text-neutral-900!"

                                    return (
                                        <Link
                                            key={poem.id}
                                            to={PUBLIC_ROUTES.POEM(poem.slug)}
                                            className={`group relative block overflow-hidden rounded-2xl p-4 shadow-sm backdrop-blur-sm transition hover:-translate-y-0.5 hover:shadow-md md:p-5 ${hasCollectionBackground ? textToneClass : "bg-white/85 text-neutral-900!"}`}
                                        >
                                            {hasCollectionBackground && (
                                                <>
                                                    <OptimizedCollectionImage
                                                        src={collection.theme_background_url}
                                                        alt={collection.title}
                                                        className="absolute inset-0 h-full w-full object-cover"
                                                    />
                                                    <div
                                                        className="absolute inset-0"
                                                        style={{ backgroundColor: overlayColor }}
                                                    />
                                                </>
                                            )}

                                            <div className="relative z-10 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="min-w-0 space-y-2">
                                                    <h3 className="font-serif text-xl leading-tight group-hover:opacity-75">
                                                        {poem.title}
                                                    </h3>

                                                    {poem.excerpt && (
                                                        <p className="line-clamp-2 text-sm opacity-70">
                                                            {poem.excerpt}
                                                        </p>
                                                    )}

                                                    {collection && (
                                                        <p className="text-xs opacity-55">
                                                            From {collection.title}
                                                        </p>
                                                    )}
                                                </div>

                                                {dateLabel && (
                                                    <time
                                                        dateTime={poem.created_at}
                                                        className="shrink-0 text-xs opacity-55"
                                                    >
                                                        {dateLabel}
                                                    </time>
                                                )}
                                            </div>
                                        </Link>
                                    )
                                })}
                            </div>
                        )}
                    </section>

                    {/* ---------------- FEATURED COLLECTIONS ---------------- */}
                    <section className="py-8 md:pt-2 md:pb-16">
                        <h2 className="mb-8 text-2xl font-serif">
                            Featured Collections
                        </h2>

                        {collectionsLoading && (
                            <p className="text-sm opacity-60">Loading collections...</p>
                        )}

                        {!collectionsLoading && collections.length === 0 && (
                            <p className="text-sm opacity-60">
                                No published collections yet.
                            </p>
                        )}

                        {!collectionsLoading && collections.length > 0 && (
                            <div className="grid gap-6 sm:grid-cols-2">
                                {collections.slice(0, 4).map((collection) => (
                                    <FeaturedCard
                                        key={collection.id}
                                        to={PUBLIC_ROUTES.COLLECTION_DETAIL(collection.slug)}
                                        title={collection.title}
                                        description={collection.description}
                                        imageUrl={collection.theme_background_url}
                                        usePreviewVariant
                                    />
                                ))}
                            </div>
                        )}
                    </section>

                    {/* ---------------- BOOKS SECTION ---------------- */}
                    <section className="py-16">
                        <h2 className="mb-6 text-2xl font-serif">
                            Novels
                        </h2>

                        {booksLoading && (
                            <p className="text-sm opacity-60">Loading novels...</p>
                        )}

                        {!booksLoading && books.length === 0 && (
                            <p className="text-sm opacity-60">
                                Novels are arriving soon.
                            </p>
                        )}

                        {!booksLoading && books.length > 0 && (
                            <div className="grid gap-6 sm:grid-cols-2">
                                {books.slice(0, 4).map((book) => (
                                    <FeaturedCard
                                        key={book.id}
                                        to={PUBLIC_ROUTES.BOOK_DETAIL(book.slug)}
                                        title={book.title}
                                        description={book.synopsis}
                                        imageUrl={book.theme_background_url || book.cover_image_url}
                                    />
                                ))}
                            </div>
                        )}

                        <div className="mt-6">
                            <Link
                                to={PUBLIC_ROUTES.BOOKS}
                                className="text-sm"
                            >
                                Visit Novels →
                            </Link>
                        </div>
                    </section>

                    {/* ---------------- MOBILE FOOTER NOTE ---------------- */}
                    <section className="pt-8 text-center md:hidden">
                        <p className="text-sm opacity-50">
                            Every collection is a world.
                        </p>
                    </section>
                </div>
            </div>

            {/* ---------------- FIXED DESKTOP FOOTER NOTE ---------------- */}
            <section className="py-6 text-center hidden md:block">
                <p className="text-sm opacity-50">
                    Every collection is a world.
                </p>
            </section>

        </div>
    )
}
