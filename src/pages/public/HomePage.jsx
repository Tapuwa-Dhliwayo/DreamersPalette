import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { PUBLIC_ROUTES } from "@/app/routes";
import { SITES } from "@/app/sites";
import Logo from "@/components/ui/Logo.jsx";
import OptimizedCollectionImage from "@/components/ui/OptimizedCollectionImage";
import { getPublishedBooksPaginated } from "@/services/bookService";
import {
    getPublishedCollectionsPaginated,
    getRecentlyAddedPoems,
} from "@/services/contentService";

const HOME_TEXT = "#f4f4f2";
const HOME_MUTED = "#c1c8d2";
const HOME_ACCENT = "#aabed8";

function formatDate(value) {
    if (!value) return null;

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;

    return new Intl.DateTimeFormat(undefined, {
        month: "short",
        day: "numeric",
        year: "numeric",
    }).format(date);
}

function getCollection(poem) {
    return Array.isArray(poem.poetry_collections)
        ? poem.poetry_collections[0]
        : poem.poetry_collections;
}

function getExhibitTheme(collection) {
    const hasImage = Boolean(collection?.theme_background_url);
    const textMode = collection?.theme_text_mode || "light";
    const overlayOpacity = Math.max(
        textMode === "light" ? 0.62 : 0.72,
        collection?.theme_overlay_opacity ?? 0.65,
    );

    return {
        accent: collection?.accent_color || HOME_ACCENT,
        backgroundUrl: collection?.theme_background_url || null,
        heading: collection?.theme_heading_color || (textMode === "light" ? HOME_TEXT : "#171717"),
        muted: collection?.theme_muted_color || (textMode === "light" ? HOME_MUTED : "#525252"),
        overlay: hasImage
            ? textMode === "light"
                ? `rgba(2, 7, 15, ${overlayOpacity})`
                : `rgba(244, 244, 242, ${overlayOpacity})`
            : null,
        text: collection?.theme_text_color || (textMode === "light" ? HOME_TEXT : "#171717"),
        textMode,
    };
}

function GalleryHeading({ id, title, description, to, linkLabel }) {
    return (
        <div className="gallery-section-heading">
            <div className="min-w-0">
                <h2 id={id} className="gallery-section-title">{title}</h2>
                {description && <p className="gallery-section-description">{description}</p>}
            </div>
            <Link to={to} className="gallery-text-link shrink-0">
                {linkLabel} <span aria-hidden="true">→</span>
            </Link>
        </div>
    );
}

function ErrorMessage({ children, onRetry }) {
    return (
        <div role="alert" aria-live="polite" className="gallery-rule border-y py-6 text-sm">
            <p className="gallery-muted max-w-prose leading-relaxed">{children}</p>
            <button type="button" onClick={onRetry} className="gallery-text-link mt-3 cursor-pointer">
                Try again
            </button>
        </div>
    );
}

function ExhibitBackdrop({ theme, alt = "", priority = false }) {
    if (!theme.backgroundUrl) {
        return <div className="gallery-exhibit-fallback absolute inset-0" />;
    }

    return (
        <>
            <OptimizedCollectionImage
                src={theme.backgroundUrl}
                alt={alt}
                usePreviewVariant
                priority={priority}
                className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.015]"
            />
            <div className="absolute inset-0" style={{ backgroundColor: theme.overlay }} />
        </>
    );
}

function FeaturedPoem({ poem }) {
    const collection = getCollection(poem);
    const theme = getExhibitTheme(collection);
    const dateLabel = formatDate(poem.created_at);

    return (
        <article className="gallery-feature group relative isolate min-h-[31rem] overflow-hidden md:min-h-[36rem]">
            <ExhibitBackdrop theme={theme} priority />
            <Link
                to={PUBLIC_ROUTES.POEM(poem.slug)}
                className="relative z-10 flex min-h-[31rem] items-end p-6 no-underline md:min-h-[36rem] md:p-12 lg:p-16"
                style={{ color: theme.text, "--exhibit-accent": theme.accent }}
            >
                <div className="max-w-2xl">
                    <p className="mb-5 flex flex-wrap gap-x-2 text-sm" style={{ color: theme.muted }}>
                        <span>Featured poem</span>
                        {collection && <span>· {collection.title}</span>}
                        {dateLabel && <time dateTime={poem.created_at}>· {dateLabel}</time>}
                    </p>
                    <h2 className="gallery-feature-title" style={{ color: theme.heading }}>
                        {poem.title}
                    </h2>
                    {poem.excerpt && (
                        <p className="mt-6 max-w-xl text-base leading-relaxed text-pretty md:text-lg">
                            {poem.excerpt}
                        </p>
                    )}
                    <span className="gallery-exhibit-action mt-8">
                        Read the poem <span aria-hidden="true">→</span>
                    </span>
                </div>
            </Link>
        </article>
    );
}

function PoemExhibit({ poem, align = "left" }) {
    const collection = getCollection(poem);
    const theme = getExhibitTheme(collection);
    const dateLabel = formatDate(poem.created_at);

    return (
        <article className="gallery-poem-exhibit group relative isolate min-h-80 overflow-hidden">
            <ExhibitBackdrop theme={theme} />
            <Link
                to={PUBLIC_ROUTES.POEM(poem.slug)}
                className={`relative z-10 flex min-h-80 items-end p-6 no-underline md:p-10 ${align === "right" ? "md:justify-end" : ""}`}
                style={{ color: theme.text, "--exhibit-accent": theme.accent }}
            >
                <div className={`max-w-lg ${align === "right" ? "md:text-right" : ""}`}>
                    <p className="mb-3 flex flex-wrap gap-x-2 text-sm md:justify-[inherit]" style={{ color: theme.muted }}>
                        {collection && <span>{collection.title}</span>}
                        {dateLabel && <time dateTime={poem.created_at}>· {dateLabel}</time>}
                    </p>
                    <h3 className="gallery-poem-title break-words" style={{ color: theme.heading }}>
                        {poem.title}
                    </h3>
                    {poem.excerpt && (
                        <p className="mt-4 line-clamp-3 text-sm leading-relaxed md:text-base">
                            {poem.excerpt}
                        </p>
                    )}
                    <span className="gallery-exhibit-action mt-6">
                        Read the poem <span aria-hidden="true">→</span>
                    </span>
                </div>
            </Link>
        </article>
    );
}

function CollectionDirectory({ collections }) {
    return (
        <div className="gallery-rule border-t">
            {collections.map((collection) => (
                <article key={collection.id} className="gallery-rule border-b">
                    <Link
                        to={PUBLIC_ROUTES.COLLECTION_DETAIL(collection.slug)}
                        className="group flex min-h-28 items-center gap-4 py-4 no-underline sm:gap-6"
                    >
                        <div className="gallery-directory-image relative h-20 w-28 shrink-0 overflow-hidden sm:w-36">
                            {collection.theme_background_url ? (
                                <OptimizedCollectionImage
                                    src={collection.theme_background_url}
                                    alt=""
                                    usePreviewVariant
                                    className="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.03]"
                                />
                            ) : (
                                <div className="gallery-exhibit-fallback h-full w-full" />
                            )}
                        </div>
                        <div className="min-w-0 flex-1 md:grid md:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_auto] md:items-center md:gap-8">
                            <h3 className="gallery-heading line-clamp-2 text-lg leading-snug sm:text-xl">
                                {collection.title}
                            </h3>
                            <p className="gallery-muted mt-1 line-clamp-2 text-sm leading-relaxed md:mt-0">
                                {collection.description || "A distinct world of poems and imagined places."}
                            </p>
                            <span className="gallery-text-link mt-2 hidden md:inline-flex">
                                Enter <span aria-hidden="true">→</span>
                            </span>
                        </div>
                    </Link>
                </article>
            ))}
        </div>
    );
}

function NovelShelf({ books }) {
    return (
        <div className="grid gap-x-8 md:grid-cols-2">
            {books.map((book) => {
                const imageUrl = book.cover_image_url || book.theme_background_url;

                return (
                    <article key={book.id} className="gallery-rule border-y">
                        <Link to={PUBLIC_ROUTES.BOOK_DETAIL(book.slug)} className="group flex gap-5 py-6 no-underline">
                            <div className="gallery-book-cover h-40 w-28 shrink-0 overflow-hidden">
                                {imageUrl ? (
                                    <OptimizedCollectionImage
                                        src={imageUrl}
                                        alt={book.cover_image_url ? `${book.title} cover` : ""}
                                        className="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.03]"
                                    />
                                ) : (
                                    <div className="gallery-exhibit-fallback flex h-full items-center justify-center px-3 text-center">
                                        <span className="gallery-muted font-serif text-sm">A novel</span>
                                    </div>
                                )}
                            </div>
                            <div className="min-w-0 py-1">
                                <h3 className="gallery-heading break-words text-xl leading-tight">{book.title}</h3>
                                {book.synopsis && (
                                    <p className="gallery-muted mt-3 line-clamp-4 text-sm leading-relaxed">{book.synopsis}</p>
                                )}
                                <span className="gallery-text-link mt-4">
                                    Begin reading <span aria-hidden="true">→</span>
                                </span>
                            </div>
                        </Link>
                    </article>
                );
            })}
        </div>
    );
}

function FeaturedPoemSkeleton() {
    return (
        <div className="gallery-feature gallery-skeleton min-h-[31rem] p-6 md:min-h-[36rem] md:p-12" aria-label="Loading the featured poem" aria-busy="true">
            <div className="flex min-h-[26rem] max-w-2xl flex-col justify-end md:min-h-[30rem]">
                <div className="h-4 w-56 rounded bg-white/12" />
                <div className="mt-5 h-14 w-4/5 rounded bg-white/14 md:h-20" />
                <div className="mt-6 h-4 w-full rounded bg-white/10" />
                <div className="mt-3 h-4 w-3/4 rounded bg-white/10" />
            </div>
        </div>
    );
}

export default function HomePage() {
    const [collections, setCollections] = useState([]);
    const [books, setBooks] = useState([]);
    const [recentPoems, setRecentPoems] = useState([]);
    const [collectionsLoading, setCollectionsLoading] = useState(true);
    const [booksLoading, setBooksLoading] = useState(true);
    const [recentPoemsLoading, setRecentPoemsLoading] = useState(true);
    const [collectionsError, setCollectionsError] = useState(false);
    const [booksError, setBooksError] = useState(false);
    const [recentPoemsError, setRecentPoemsError] = useState(false);

    const loadCollections = useCallback(async () => {
        setCollectionsLoading(true);
        setCollectionsError(false);
        try {
            const result = await getPublishedCollectionsPaginated(1, 4);
            setCollections(result.data || []);
        } catch {
            setCollectionsError(true);
        } finally {
            setCollectionsLoading(false);
        }
    }, []);

    const loadBooks = useCallback(async () => {
        setBooksLoading(true);
        setBooksError(false);
        try {
            const result = await getPublishedBooksPaginated(1, 4);
            setBooks(result.data || []);
        } catch {
            setBooksError(true);
        } finally {
            setBooksLoading(false);
        }
    }, []);

    const loadRecentPoems = useCallback(async () => {
        setRecentPoemsLoading(true);
        setRecentPoemsError(false);
        try {
            const result = await getRecentlyAddedPoems(4);
            setRecentPoems(result || []);
        } catch {
            setRecentPoemsError(true);
        } finally {
            setRecentPoemsLoading(false);
        }
    }, []);

    useEffect(() => {
        void loadCollections();
        void loadBooks();
        void loadRecentPoems();
    }, [loadBooks, loadCollections, loadRecentPoems]);

    const featuredPoem = recentPoems[0];
    const morePoems = recentPoems.slice(1);

    return (
        <div className="home-gallery pb-8 md:pb-12">
            <a href="#featured-poem" className="gallery-skip-link">Skip to the featured poem</a>

            <header className="gallery-rule flex flex-col items-center gap-5 border-b py-5 sm:flex-row sm:justify-between">
                <Logo size="home" as="h1" />
                <nav aria-label="Primary" className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm">
                    <Link to={PUBLIC_ROUTES.COLLECTIONS} className="gallery-nav-link">Collections</Link>
                    <Link to={PUBLIC_ROUTES.BOOKS} className="gallery-nav-link">Novels</Link>
                    <a href={SITES.blog} className="gallery-nav-link" target="_blank" rel="noopener noreferrer">Journal</a>
                    <a href={SITES.concepts} className="gallery-nav-link" target="_blank" rel="noopener noreferrer">Concepts</a>
                    <Link to={PUBLIC_ROUTES.LOGIN} className="gallery-nav-link">Author login</Link>
                </nav>
            </header>

            <p className="gallery-introduction">A private exhibition of poems and imagined worlds.</p>

            <section id="featured-poem" className="scroll-mt-4" aria-label="Featured poem">
                {recentPoemsLoading && <FeaturedPoemSkeleton />}

                {!recentPoemsLoading && recentPoemsError && (
                    <div className="gallery-feature flex min-h-80 items-end p-6 md:p-12">
                        <div className="max-w-xl">
                            <ErrorMessage onRetry={loadRecentPoems}>
                                The featured poem couldn’t load. Check your connection and try again.
                            </ErrorMessage>
                            <Link to={PUBLIC_ROUTES.COLLECTIONS} className="gallery-text-link mt-4">
                                Enter the collections instead <span aria-hidden="true">→</span>
                            </Link>
                        </div>
                    </div>
                )}

                {!recentPoemsLoading && !recentPoemsError && featuredPoem && <FeaturedPoem poem={featuredPoem} />}

                {!recentPoemsLoading && !recentPoemsError && !featuredPoem && (
                    <div className="gallery-feature flex min-h-80 items-end p-6 md:p-12">
                        <div className="max-w-xl">
                            <h2 className="gallery-feature-title">The first poem is still being written.</h2>
                            <p className="gallery-muted mt-5 leading-relaxed">
                                Until it arrives, enter the published collection worlds.
                            </p>
                            <Link to={PUBLIC_ROUTES.COLLECTIONS} className="gallery-text-link mt-6">
                                Enter the collections <span aria-hidden="true">→</span>
                            </Link>
                        </div>
                    </div>
                )}
            </section>

            {morePoems.length > 0 && (
                <section className="gallery-section" aria-labelledby="poem-worlds-heading">
                    <GalleryHeading
                        id="poem-worlds-heading"
                        title="Poem worlds"
                        description="Recent poems, each seen through the world of its collection."
                        to={PUBLIC_ROUTES.COLLECTIONS}
                        linkLabel="Explore poem collections"
                    />
                    <div className="space-y-3 md:space-y-4">
                        {morePoems.map((poem, index) => (
                            <PoemExhibit key={poem.id} poem={poem} align={index % 2 === 1 ? "right" : "left"} />
                        ))}
                    </div>
                </section>
            )}

            <section className="gallery-section" aria-labelledby="collections-heading">
                <GalleryHeading
                    id="collections-heading"
                    title="The collections"
                    description="A personal library arranged by world, mood, and memory."
                    to={PUBLIC_ROUTES.COLLECTIONS}
                    linkLabel="Browse all collections"
                />

                {collectionsLoading && (
                    <div className="gallery-rule border-t" aria-label="Loading collections" aria-busy="true">
                        {Array.from({ length: 3 }, (_, index) => (
                            <div key={index} className="gallery-rule flex gap-5 border-b py-4">
                                <div className="gallery-skeleton h-20 w-28 shrink-0 sm:w-36" />
                                <div className="flex flex-1 flex-col justify-center">
                                    <div className="gallery-skeleton h-5 w-2/5" />
                                    <div className="gallery-skeleton mt-3 h-4 w-4/5" />
                                </div>
                            </div>
                        ))}
                    </div>
                )}
                {!collectionsLoading && collectionsError && (
                    <ErrorMessage onRetry={loadCollections}>Collections couldn’t load. Check your connection and try again.</ErrorMessage>
                )}
                {!collectionsLoading && !collectionsError && collections.length === 0 && (
                    <p className="gallery-muted gallery-rule border-y py-6 text-sm leading-relaxed">
                        The collection rooms are being prepared. In the meantime, explore the published novels.
                    </p>
                )}
                {!collectionsLoading && !collectionsError && collections.length > 0 && (
                    <CollectionDirectory collections={collections} />
                )}
            </section>

            <section className="gallery-section" aria-labelledby="novels-heading">
                <GalleryHeading
                    id="novels-heading"
                    title="Novels"
                    description="Longer journeys from the same imagined library."
                    to={PUBLIC_ROUTES.BOOKS}
                    linkLabel="Browse all novels"
                />

                {booksLoading && (
                    <div className="grid gap-x-8 md:grid-cols-2" aria-label="Loading novels" aria-busy="true">
                        {Array.from({ length: 4 }, (_, index) => (
                            <div key={index} className="gallery-rule flex gap-5 border-t py-6">
                                <div className="gallery-skeleton h-40 w-28 shrink-0" />
                                <div className="flex flex-1 flex-col justify-center">
                                    <div className="gallery-skeleton h-5 w-3/4" />
                                    <div className="gallery-skeleton mt-4 h-4 w-full" />
                                    <div className="gallery-skeleton mt-3 h-4 w-2/3" />
                                </div>
                            </div>
                        ))}
                    </div>
                )}
                {!booksLoading && booksError && (
                    <ErrorMessage onRetry={loadBooks}>Novels couldn’t load. Check your connection and try again.</ErrorMessage>
                )}
                {!booksLoading && !booksError && books.length === 0 && (
                    <p className="gallery-muted gallery-rule border-y py-6 text-sm leading-relaxed">
                        The longer stories are still taking shape. The collection rooms are open now.
                    </p>
                )}
                {!booksLoading && !booksError && books.length > 0 && <NovelShelf books={books} />}
            </section>

            <footer className="gallery-rule mt-14 border-t pt-10 md:mt-20 md:pt-14">
                <div className="md:grid md:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] md:gap-16">
                    <h2 className="gallery-section-title">From Dreamer’s Palette</h2>
                    <div className="mt-5 max-w-2xl md:mt-0">
                        <p className="gallery-body leading-relaxed text-pretty">
                            I write to understand the worlds I cannot stay in. These poems are doors, and sometimes
                            mirrors. Thank you for entering quietly. You are always welcome here.
                        </p>
                        <Link to={PUBLIC_ROUTES.COLLECTIONS} className="gallery-text-link mt-6">
                            Browse the library <span aria-hidden="true">→</span>
                        </Link>
                    </div>
                </div>

                <div className="gallery-rule mt-10 flex flex-col gap-4 border-t pt-8 pb-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <nav aria-label="Dreamer’s Palette sites" className="flex flex-wrap gap-x-7 gap-y-2">
                        <span className="gallery-muted font-serif">All of Dreamer’s Palette</span>
                        <a href={SITES.blog} className="gallery-nav-link" target="_blank" rel="noopener noreferrer">Journal</a>
                        <a href={SITES.concepts} className="gallery-nav-link" target="_blank" rel="noopener noreferrer">Concepts</a>
                    </nav>
                    <p className="gallery-muted">Powered by Alate Digital Systems</p>
                </div>
            </footer>
        </div>
    );
}
