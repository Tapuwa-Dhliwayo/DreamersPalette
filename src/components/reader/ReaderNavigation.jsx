import { Link } from "react-router-dom"
import { useReaderNavigation } from "@/hooks/useReaderNavigation"

export default function ReaderNavigation() {
    const { level, collection, previous, next } =
        useReaderNavigation()

    // Index page → no navigation
    if (level === "index" || level === "none") {
        return null
    }

    return (
        <div className="flex items-center justify-between text-sm opacity-70 transition-opacity duration-200 hover:opacity-100">

            {/* LEFT SIDE — Parent Navigation */}
            <div>
                {level === "collection" && (
                    <Link
                        to="/collections"
                        className="hover:underline"
                        style={{ color: "var(--accent-color)" }}
                    >
                        ← All Collections
                    </Link>
                )}

                {level === "poem" && collection && (
                    <Link
                        to={`/collections/${collection.slug}`}
                        className="hover:underline"
                        style={{ color: "var(--accent-color)" }}
                    >
                        ← {collection.title}
                    </Link>
                )}
            </div>

            {/* RIGHT SIDE — Sibling Navigation */}
            {level === "poem" && (previous || next) && (
                <div className="flex gap-6">
                    {previous && (
                        <Link
                            to={`/poems/${previous.slug}`}
                            className="hover:underline"
                            style={{ color: "var(--accent-color)" }}
                        >
                            Previous
                        </Link>
                    )}

                    {next && (
                        <Link
                            to={`/poems/${next.slug}`}
                            className="hover:underline"
                            style={{ color: "var(--accent-color)" }}
                        >
                            Next
                        </Link>
                    )}
                </div>
            )}
        </div>
    )
}