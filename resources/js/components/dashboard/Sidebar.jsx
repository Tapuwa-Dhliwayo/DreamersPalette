import { NavLink, useNavigate } from "react-router-dom"
import { useEffect, useRef, useState } from "react"
import Button from "@/components/ui/Button"
import { can, signOut } from "@/services/authService"
import { useAuth } from "@/hooks/useAuth"
import { DASHBOARD_ROUTES, PUBLIC_ROUTES } from "@/app/routes"

const navigationIcons = {
    dashboard: "M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z",
    manuscript: "M4 4h6a3 3 0 0 1 2 1 3 3 0 0 1 2-1h6v15h-6a3 3 0 0 0-2 1 3 3 0 0 0-2-1H4z M12 5v15",
    world: "M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z M3 12h18 M12 3a18 18 0 0 1 0 18 18 18 0 0 1 0-18z",
    review: "M4 4h16v12H9l-5 4z M8 8h8 M8 12h5",
    collection: "M4 7h16v14H4z M7 3h10 M5 5h14 M8 11h8",
    poem: "M6 3h9l3 3v15H6z M9 10h6 M9 14h4 M9 18h5",
    book: "M6 3h13v18H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z M4 17h15 M8 7h7",
    chapter: "M5 3h14v18H5z M9 7h6 M9 11h6 M9 15h6 M9 19h3",
    trash: "M3 6h18 M9 6V3h6v3 M5 6l1 15h12l1-15 M10 10v7 M14 10v7",
    security: "M12 3l8 3v6c0 5-8 9-8 9s-8-4-8-9V6z M9 12l2 2 4-4",
}

function SidebarIcon({ name }) {
    return <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="shrink-0"><path d={navigationIcons[name]} /></svg>
}

export default function Sidebar({ isOpen, onClose }) {
    const navigate = useNavigate()
    const { user } = useAuth()
    const mobilePanel = useRef(null)
    const closeButton = useRef(null)
    const displayName = user?.profile?.display_name || user?.name || "Account"
    const roleNames = user?.roles?.join(", ") || ""
    const [error, setError] = useState("")

    async function handleLogout() {
        try {
            await signOut()
            navigate(PUBLIC_ROUTES.LOGIN, { replace: true })
        } catch (err) {
            setError(err.message || "Logout failed. Try again.")
        }
    }

    useEffect(() => {
        if (!isOpen) return
        const previousFocus = document.activeElement
        closeButton.current?.focus()
        return () => previousFocus?.focus()
    }, [isOpen])

    function containNavigationFocus(event) {
        if (event.key === "Escape") { onClose(); return }
        if (event.key !== "Tab") return
        const controls = mobilePanel.current?.querySelectorAll("a[href], button:not([disabled])")
        if (!controls?.length) return
        const first = controls[0]
        const last = controls[controls.length - 1]
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
    }

    const navigationGroups = [
        { label: "Writing", items: [
            { label: "Manuscripts", to: "/studio", icon: "manuscript", permission: "manuscripts.view", fullPage: true },
            { label: "Worlds", to: "/studio/worlds", icon: "world", permission: "worlds.view", fullPage: true },
            { label: "Editorial reviews", to: "/studio/reviews", icon: "review", permission: "reviews.view", fullPage: true },
        ] },
        { label: "Publishing", items: [
            { label: "Poems", to: DASHBOARD_ROUTES.POEMS, icon: "poem", permission: "poems.view" },
            { label: "Collections", to: DASHBOARD_ROUTES.COLLECTIONS, icon: "collection", permission: "collections.view" },
            { label: "Novels", to: DASHBOARD_ROUTES.BOOKS, icon: "book", permission: "books.view" },
            { label: "Chapters", to: DASHBOARD_ROUTES.CHAPTERS, icon: "chapter", permission: "chapters.view" },
        ] },
    ].map(group => ({ ...group, items: group.items.filter(item => can(user, item.permission)) })).filter(group => group.items.length)
    const accountItems = [
        { label: "Trash", to: DASHBOARD_ROUTES.TRASH, icon: "trash", permission: "trash.view" },
        { label: "Account security", to: DASHBOARD_ROUTES.SECURITY, icon: "security", permission: "account.security.manage" },
    ].filter(item => can(user, item.permission))
    const navigationClass = "flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-700"
    const activeClass = ({ isActive }) => `${navigationClass} ${isActive ? "bg-neutral-200/60 font-semibold text-neutral-950" : ""}`

    const sidebarContent = (
        <aside className="flex h-full w-64 flex-col justify-between overflow-y-auto border-r border-neutral-200 bg-white/95">

            {/* Top */}
            <div className="p-5 space-y-7">

                {/* Brand + close button row */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Dreamer's Palette
                        </h1>

                    </div>
                    {/* Close button — mobile only */}
                    <button
                        ref={closeButton}
                        type="button"
                        className="-mr-2 -mt-2 flex min-h-11 min-w-11 items-center justify-center rounded-lg text-xl leading-none hover:bg-neutral-100 md:hidden"
                        onClick={onClose}
                        aria-label="Close sidebar"
                    >
                        ✕
                    </button>
                </div>

                <nav aria-label="Dashboard navigation" className="space-y-6">
                    {can(user, "dashboard.access") && <NavLink to={DASHBOARD_ROUTES.ROOT} end onClick={onClose} className={activeClass}><SidebarIcon name="dashboard" />Dashboard</NavLink>}
                    {navigationGroups.map(group => <section key={group.label} aria-label={group.label}>
                        <h2 className="mb-2 px-3 text-xs font-medium text-neutral-600">{group.label}</h2>
                        <div className="space-y-1">{group.items.map(item => item.fullPage
                            ? <a key={item.to} href={item.to} onClick={onClose} className={navigationClass}><SidebarIcon name={item.icon} />{item.label}</a>
                            : <NavLink key={item.to} to={item.to} onClick={onClose} className={activeClass}><SidebarIcon name={item.icon} />{item.label}</NavLink>)}</div>
                    </section>)}
                </nav>
            </div>

            {/* Bottom Profile Area */}
            <div className="border-t border-neutral-200 p-5 space-y-4">
                {accountItems.length > 0 && <nav aria-label="Account and archives" className="space-y-1">{accountItems.map(item => <NavLink key={item.to} to={item.to} onClick={onClose} className={activeClass}><SidebarIcon name={item.icon} />{item.label}</NavLink>)}</nav>}

                <div className="flex items-center gap-3">
                    <div aria-hidden="true" className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-sm font-medium">{displayName.charAt(0).toUpperCase()}</div>
                    <div className="min-w-0"><div className="truncate text-sm font-medium">{displayName}</div>{roleNames && <div className="text-xs text-neutral-600">{roleNames}</div>}</div>
                </div>
                <Button variant="subtle" size="sm" className="w-full justify-start" onClick={handleLogout}>Sign out</Button>
                {error && <p role="alert" className="m-0 text-xs text-red-700">{error}</p>}

            </div>

        </aside>
    )

    return (
        <>
            {/* Desktop: permanent sidebar */}
            <div className="hidden md:flex">
                {sidebarContent}
            </div>

            {/* Mobile: fixed overlay */}
            {isOpen && (
                <div className="fixed inset-0 z-50 md:hidden flex">
                    {/* Backdrop */}
                    <button type="button" tabIndex={-1} aria-label="Close navigation overlay" className="absolute inset-0 bg-black/40" onClick={onClose} />
                    {/* Sidebar panel */}
                    <div ref={mobilePanel} role="dialog" aria-modal="true" aria-label="Navigation" onKeyDown={containNavigationFocus} className="relative h-full">
                        {sidebarContent}
                    </div>
                </div>
            )}
        </>
    )
}
