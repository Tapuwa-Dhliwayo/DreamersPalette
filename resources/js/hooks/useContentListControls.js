import { useMemo, useState } from "react"
import { filterAndSortItems } from "@/utils/contentList"

export function useContentListControls(items, pageSize = 10) {
    const [search, setSearch] = useState("")
    const [sort, setSort] = useState("updated-desc")
    const [selectedIds, setSelectedIds] = useState([])
    const [page, setPage] = useState(1)

    const filteredItems = useMemo(
        () => filterAndSortItems(items, search, sort),
        [items, search, sort]
    )
    const totalPages = Math.max(1, Math.ceil(filteredItems.length / pageSize))
    const safePage = Math.min(page, totalPages)
    const visibleItems = useMemo(() => {
        const start = (safePage - 1) * pageSize
        return filteredItems.slice(start, start + pageSize)
    }, [filteredItems, pageSize, safePage])

    const validIds = useMemo(() => new Set(items.map((item) => item.id)), [items])
    const validSelectedIds = selectedIds.filter((id) => validIds.has(id))

    function toggleItem(id, checked) {
        setSelectedIds((current) => checked
            ? [...new Set([...current, id])]
            : current.filter((itemId) => itemId !== id))
    }

    function toggleAll(checked) {
        const visibleIds = visibleItems.map((item) => item.id)
        setSelectedIds((current) => checked
            ? [...new Set([...current, ...visibleIds])]
            : current.filter((id) => !visibleIds.includes(id)))
    }

    return {
        search,
        sort,
        selectedIds: validSelectedIds,
        setSelectedIds,
        page: safePage,
        setPage,
        setSearch: (value) => {
            setSearch(value)
            setPage(1)
        },
        setSort: (value) => {
            setSort(value)
            setPage(1)
        },
        filteredItems,
        visibleItems,
        toggleItem,
        toggleAll
    }
}
