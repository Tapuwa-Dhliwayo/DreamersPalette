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
        (level === "poem" && collection)

    const showSiblingNav =
        level === "poem" && (previous || next)

    return (
        <header className="space-y-6">

            {/* ---------- GLOBAL HEADER ---------- */}
            <div className="flex items-center justify-between text-sm">

                <Link
                    to={PUBLIC_ROUTES.HOME}
                    className="hover:opacity-80 transition-opacity"
                >
                    <Logo size="sm" />
                </Link>

                <nav className="flex items-center gap-3 md:gap-8">
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
                        Books
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

            {/* ---------- CONTEXT NAV ---------- */}
            {showContextNav && (
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between text-xs opacity-70 border-t border-white/10 p-4 mb-2">

                    <div>
                        {level === "collection" && (
                            <Link
                                to={PUBLIC_ROUTES.COLLECTIONS}
                                className="accent-button"
                            >
                                ← All Collections
                            </Link>
                        )}

                        {level === "poem" && collection && (
                            <Link
                                to={PUBLIC_ROUTES.COLLECTION_DETAIL(collection.slug)}
                                className="accent-button"
                            >
                                ← {collection.title}
                            </Link>
                        )}
                    </div>

                    {showSiblingNav && (
                        <div className="flex flex-wrap gap-2 md:gap-4">
                            {previous && (
                                <Link
                                    to={PUBLIC_ROUTES.POEM(previous.slug)}
                                    className="accent-button"
                                >
                                    Previous
                                </Link>
                            )}

                            {next && (
                                <Link
                                    to={PUBLIC_ROUTES.POEM(next.slug)}
                                    className="accent-button"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            )}

        </header>
    )
}