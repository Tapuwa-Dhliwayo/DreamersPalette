export function filterAndSortItems(items, search, sort) {
    const normalized = search.trim().toLocaleLowerCase()
    const filtered = normalized
        ? items.filter((item) => item.title?.toLocaleLowerCase().includes(normalized))
        : items

    return [...filtered].sort((a, b) => {
        if (sort === "title-asc") return a.title.localeCompare(b.title)
        if (sort === "status") return Number(b.is_published) - Number(a.is_published)
        return new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at)
    })
}
