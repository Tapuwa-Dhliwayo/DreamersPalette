import Input from "@/components/ui/Input"
import Select from "@/components/ui/Select"
import Button from "@/components/ui/Button"
import Badge from "@/components/ui/Badge"

export function ContentListToolbar({
    search,
    onSearchChange,
    sort,
    onSortChange,
    selectedCount,
    onBulkPublish,
    onBulkArchive,
    pending = false,
    children
}) {
    return (
        <div className="space-y-3 border-y border-neutral-200 py-4">
            <div className="flex flex-col gap-3 md:flex-row md:items-end">
                <label className="min-w-0 flex-1 text-sm font-medium text-neutral-700">
                    Search
                    <Input
                        className="mt-2"
                        type="search"
                        value={search}
                        placeholder="Search by title"
                        onChange={(event) => onSearchChange(event.target.value)}
                    />
                </label>
                <label className="w-full text-sm font-medium text-neutral-700 md:w-52">
                    Sort
                    <Select className="mt-2" value={sort} onChange={(event) => onSortChange(event.target.value)}>
                        <option value="updated-desc">Recently updated</option>
                        <option value="title-asc">Title A–Z</option>
                        <option value="status">Publication status</option>
                    </Select>
                </label>
                {children}
            </div>
            {selectedCount > 0 && (
                <div className="flex flex-wrap items-center gap-2 rounded-xl bg-neutral-100 px-3 py-2">
                    <span className="mr-auto text-sm font-medium">{selectedCount} selected</span>
                    {onBulkPublish && (
                        <Button size="sm" variant="subtle" disabled={pending} onClick={onBulkPublish}>
                            Publish selected
                        </Button>
                    )}
                    <Button size="sm" variant="ghost" disabled={pending} onClick={onBulkArchive}>
                        Move to Trash
                    </Button>
                </div>
            )}
        </div>
    )
}

export function ContentList({
    items,
    selectedIds,
    onToggleAll,
    children
}) {
    const allSelected = items.length > 0 && items.every((item) => selectedIds.includes(item.id))

    return (
        <div className="border-y border-neutral-200">
            {items.length > 0 && (
                <div className="flex items-center gap-3 border-b border-neutral-200 py-3 text-sm text-neutral-600">
                    <input
                        type="checkbox"
                        checked={allSelected}
                        onChange={(event) => onToggleAll(event.target.checked)}
                        aria-label={allSelected ? "Deselect all visible items" : "Select all visible items"}
                    />
                    Select visible
                </div>
            )}
            <div className="divide-y divide-neutral-200">{children}</div>
        </div>
    )
}

export function ContentListRow({
    item,
    selected,
    onToggle,
    title,
    description,
    metadata,
    onEdit,
    onArchive,
    onPublish,
    pending = false,
    children
}) {
    return (
        <article className="flex flex-col gap-4 py-5 md:flex-row md:items-center">
            <input
                type="checkbox"
                checked={selected}
                onChange={(event) => onToggle(item.id, event.target.checked)}
                aria-label={`Select ${title}`}
            />
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <h3 className="truncate text-lg font-medium">{title}</h3>
                    <Badge variant={item.is_published ? "success" : "default"}>
                        {item.is_published ? "Published" : "Draft"}
                    </Badge>
                    {children}
                </div>
                {description && <p className="mt-1 line-clamp-2 text-sm text-neutral-600">{description}</p>}
                {metadata && <p className="mt-1 text-xs text-neutral-600">{metadata}</p>}
            </div>
            <div className="flex flex-wrap items-center gap-2">
                <Button size="sm" variant="subtle" disabled={pending} onClick={() => onPublish(item)}>
                    {item.is_published ? "Unpublish" : "Publish"}
                </Button>
                <Button size="sm" variant="ghost" onClick={() => onEdit(item)}>Edit</Button>
                <Button size="sm" variant="ghost" onClick={() => onArchive(item)}>Archive</Button>
            </div>
        </article>
    )
}
