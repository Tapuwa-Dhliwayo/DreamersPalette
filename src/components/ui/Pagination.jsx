import clsx from "clsx"
import Button from "./Button"

const DEFAULT_PAGE_SIZE = 12

/**
 * Reusable pagination controls.
 *
 * Props:
 *  - page        current 1-based page number
 *  - pageSize    items per page (default 12)
 *  - totalCount  total number of items
 *  - onPageChange(newPage) callback
 *  - className   optional wrapper class
 */
export default function Pagination({
    page,
    pageSize = DEFAULT_PAGE_SIZE,
    totalCount,
    onPageChange,
    className
}) {
    const totalPages = Math.max(1, Math.ceil(totalCount / pageSize))

    if (totalPages <= 1) return null

    return (
        <nav
            aria-label="Pagination"
            className={clsx("flex items-center justify-center gap-3", className)}
        >
            <Button
                variant="subtle"
                size="sm"
                disabled={page <= 1}
                onClick={() => onPageChange(page - 1)}
            >
                Previous
            </Button>

            <span className="text-sm text-neutral-500">
                Page {page} of {totalPages}
            </span>

            <Button
                variant="subtle"
                size="sm"
                disabled={page >= totalPages}
                onClick={() => onPageChange(page + 1)}
            >
                Next
            </Button>
        </nav>
    )
}

export { DEFAULT_PAGE_SIZE }
