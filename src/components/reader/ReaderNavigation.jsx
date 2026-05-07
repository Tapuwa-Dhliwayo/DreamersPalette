import { Link, useLocation } from "react-router-dom"
import { useReaderNavigation } from "@/hooks/useReaderNavigation"
import { PUBLIC_ROUTES, DASHBOARD_ROUTES } from "@/app/routes"
import Logo from "@/components/ui/Logo.jsx"
import { supabase } from "@/services/supabaseClient"
import { useEffect, useState } from "react"

export default function ReaderNavigation() {
    const { level, collection, previous, next } =
        useReaderNavigation()

    const [isAuthenticated, setIsAuthenticated] = useState(false)
    const location = useLocation()
    const isHome = location.pathname === PUBLIC_ROUTES.HOME
    const isLogin = location.pathname === PUBLIC_ROUTES.LOGIN

    useEffect(() => {
        supabase.auth.getSession().then(({ data }) => {
            setIsAuthenticated(!!data.session)
        })
    }, [])

    // 🔹 Hide entire header on Home
    if (isHome || isLogin) {
        return null
    }

    const showContextNav =
        level === "collection" ||
        (level === "poem" && collection) ||
        level === "book" ||
        (level === "chapter" && collection)

    return (
        <header className="space-y-6">

            {/* ---------- GLOBAL HEADER ---------- */}
            <div className="text-sm space-y-3">

                {/* Row 1: Logo (left) + Auth button (right) — always */}
                <div className="flex items-center justify-between gap-x-1">
                    <Link
                        to={PUBLIC_ROUTES.HOME}
                        className="hover:opacity-80 transition-opacity"
                    >
                        <Logo size="sm" />
                    </Link>

                    {/* Nav links — desktop only (center slot) */}
                    <nav className="hidden md:flex items-center gap-8">
                        <Link
                            to={PUBLIC_ROUTES.COLLECTIONS}
                            className="accent-button"
                        >
                            Collections
                        </Link>

                        <Link
                            to={PUBLIC_ROUTES.BOOKS}
                            className="accent-button"
                        >
                            Novels
                        </Link>
                    </nav>

                    <div>
                        {isAuthenticated ? (
                            <Link
                                to={DASHBOARD_ROUTES.ROOT}
                                className="accent-button"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <Link
                                to={PUBLIC_ROUTES.LOGIN}
                                className="accent-button"
                            >
                                Author Login
                            </Link>
                        )}
                    </div>
                </div>

                {/* Row 2: Nav links — mobile only */}
                <nav className="flex md:hidden items-center justify-center gap-3">
                    <Link
                        to={PUBLIC_ROUTES.COLLECTIONS}
                        className="accent-button"
                    >
                        Collections
                    </Link>

                    <Link
                        to={PUBLIC_ROUTES.BOOKS}
                        className="accent-button"
                    >
                        Novels
                    </Link>
                </nav>

            </div>

            {/* ---------- CONTEXT NAV ---------- */}
            {showContextNav && (
                <div className="context-nav-enter flex items-center justify-between text-xs opacity-70 border-t border-white/10 p-4 mb-2">

                    {/* Back Link */}
                    <div>
                        {level === "collection" && (
                            <Link
                                to={PUBLIC_ROUTES.COLLECTIONS}
                                className="accent-button transition-all duration-200 hover:-translate-x-0.5"
                            >
                                <span className="md:hidden">← Back</span>
                                <span className="hidden md:inline">← All Collections</span>
                            </Link>
                        )}

                        {level === "poem" && collection && (
                            <Link
                                to={PUBLIC_ROUTES.COLLECTION_DETAIL(collection.slug)}
                                className="accent-button transition-all duration-200 hover:-translate-x-0.5"
                            >
                                <span className="md:hidden">← Back</span>
                                <span className="hidden md:inline">← {collection.title}</span>
                            </Link>
                        )}

                        {level === "book" && (
                            <Link
                                to={PUBLIC_ROUTES.BOOKS}
                                className="accent-button transition-all duration-200 hover:-translate-x-0.5"
                            >
                                <span className="md:hidden">← Back</span>
                                <span className="hidden md:inline">← All Novels</span>
                            </Link>
                        )}

                        {level === "chapter" && collection && (
                            <Link
                                to={PUBLIC_ROUTES.BOOK_DETAIL(collection.slug)}
                                className="accent-button transition-all duration-200 hover:-translate-x-0.5"
                            >
                                <span className="md:hidden">← Back</span>
                                <span className="hidden md:inline">← {collection.title}</span>
                            </Link>
                        )}
                    </div>

                    {/* Sibling Navigation — Poems */}
                    {level === "poem" && (
                        <div className="flex items-center gap-6">

                            <Link
                                to={previous ? PUBLIC_ROUTES.POEM(previous.slug) : "#"}
                                aria-disabled={!previous}
                                className={`accent-button transition-all duration-200 ${
                                    !previous
                                        ? "opacity-30 pointer-events-none cursor-default"
                                        : "hover:-translate-x-0.5"
                                }`}
                            >
                                Previous
                            </Link>

                            <Link
                                to={next ? PUBLIC_ROUTES.POEM(next.slug) : "#"}
                                aria-disabled={!next}
                                className={`accent-button transition-all duration-200 ${
                                    !next
                                        ? "opacity-30 pointer-events-none cursor-default"
                                        : "hover:translate-x-0.5"
                                }`}
                            >
                                Next
                            </Link>

                        </div>
                    )}

                    {/* Sibling Navigation — Chapters */}
                    {level === "chapter" && collection && (
                        <div className="flex items-center gap-6">

                            <Link
                                to={previous ? PUBLIC_ROUTES.CHAPTER(collection.slug, previous.chapter_number) : "#"}
                                aria-disabled={!previous}
                                className={`accent-button transition-all duration-200 ${
                                    !previous
                                        ? "opacity-30 pointer-events-none cursor-default"
                                        : "hover:-translate-x-0.5"
                                }`}
                            >
                                Previous
                            </Link>

                            <Link
                                to={next ? PUBLIC_ROUTES.CHAPTER(collection.slug, next.chapter_number) : "#"}
                                aria-disabled={!next}
                                className={`accent-button transition-all duration-200 ${
                                    !next
                                        ? "opacity-30 pointer-events-none cursor-default"
                                        : "hover:translate-x-0.5"
                                }`}
                            >
                                Next
                            </Link>

                        </div>
                    )}
                </div>
            )}

        </header>
    )
}