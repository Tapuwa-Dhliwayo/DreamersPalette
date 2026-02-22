import { Link } from "react-router-dom"
import { useReaderNavigation } from "@/hooks/useReaderNavigation"
import Logo from "@/components/ui/Logo.jsx";

export default function ReaderNavigation() {
    const { level, collection, previous, next } =
        useReaderNavigation()

    if (level === "index" || level === "none") {
        return null
    }

    const showBreadcrumb =
        level === "collection" ||
        (level === "poem" && collection)

    const showSiblingNav =
        level === "poem" && (previous || next)

    return (
        <div className="flex items-center justify-between text-sm opacity-70 transition-opacity duration-200 hover:opacity-100 mb-4">

            {/* LEFT — Logo (Always Visible) */}
            <Link
                to="/"
                className="hover:opacity-80 transition-opacity"
            >
                <Logo size="sm" />
            </Link>



            {/* CENTER — Breadcrumb */}
            <div>
                {showBreadcrumb && level === "collection" && (
                    <Link
                        to="/collections"
                        className="accent-button"
                    >
                        ← All Collections
                    </Link>
                )}

                {showBreadcrumb && level === "poem" && collection && (
                    <Link
                        to={`/collections/${collection.slug}`}
                        className="accent-button"
                    >
                        ← {collection.title}
                    </Link>
                )}
            </div>

            {/* RIGHT — Previous / Next */}
            {showSiblingNav ? (
                <div className="flex gap-6">
                    {previous && (
                        <Link
                            to={`/poems/${previous.slug}`}
                            className="accent-button"
                        >
                            Previous
                        </Link>
                    )}

                    {next && (
                        <Link
                            to={`/poems/${next.slug}`}
                            className="accent-button"
                        >
                            Next
                        </Link>
                    )}
                </div>
            ) : (
                <div /> // spacer to preserve layout
            )}
        </div>
    )
}