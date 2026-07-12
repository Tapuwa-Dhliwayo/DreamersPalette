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
 *  - alwaysShow  keep the bar visible even with a single page (default false)
 *  - showRange   show an item-range count instead of "Page X of Y" (default false)
 *  - className   optional wrapper class
 */
export default function Pagination({
    page,
    pageSize = DEFAULT_PAGE_SIZE,
    totalCount,
    onPageChange,
    alwaysShow = false,
    showRange = false,
    className
}) {
    const totalPages = Math.max(1, Math.ceil(totalCount / pageSize))

    if (totalPages <= 1 && !alwaysShow) return null

    if (showRange) {
        const rangeStart = (page - 1) * pageSize + 1
        const rangeEnd = Math.min(page * pageSize, totalCount)
        const rangeLabel = totalCount === 0
            ? "0 items"
            : totalPages <= 1
                ? `${totalCount} items`
                : `${rangeStart}–${rangeEnd} of ${totalCount}`

        return (
            <nav
                aria-label="Pagination"
                className={clsx("flex items-center justify-between gap-3", className)}
            >
                <span className="text-sm text-neutral-600 tabular-nums">{rangeLabel}</span>
                {totalPages > 1 && (
                    <div className="flex items-center gap-3">
                        <Button
                            variant="subtle"
                            size="sm"
                            disabled={page <= 1}
                            onClick={() => onPageChange(page - 1)}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="subtle"
                            size="sm"
                            disabled={page >= totalPages}
                            onClick={() => onPageChange(page + 1)}
                        >
                            Next
                        </Button>
                    </div>
                )}
            </nav>
        )
    }

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
