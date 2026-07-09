import { NavLink, useNavigate } from "react-router-dom"
import { useEffect, useState } from "react"
import Button from "@/components/ui/Button"
import { signOut } from "@/services/authService"
import { getMyProfile } from "@/services/profileService"
import { DASHBOARD_ROUTES, PUBLIC_ROUTES } from "@/app/routes"

export default function Sidebar({ isOpen, onClose }) {
    const navigate = useNavigate()
    const [profile, setProfile] = useState(null)
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState("")

    useEffect(() => {
        async function loadProfile() {
            try {
                const data = await getMyProfile()
                setProfile(data)
            } catch (err) {
                setError(err.message || "Profile unavailable")
            } finally {
                setLoading(false)
            }
        }

        loadProfile()
    }, [])

    async function handleLogout() {
        try {
            await signOut()
            navigate(PUBLIC_ROUTES.LOGIN, { replace: true })
        } catch (err) {
            setError(err.message || "Logout failed. Try again.")
        }
    }

    const navItems = [
        { label: "Dashboard", to: DASHBOARD_ROUTES.ROOT },
        { label: "Collections", to: DASHBOARD_ROUTES.COLLECTIONS },
        { label: "Poems", to: DASHBOARD_ROUTES.POEMS },
        { label: "Novels", to: DASHBOARD_ROUTES.BOOKS },
        { label: "Chapters", to: DASHBOARD_ROUTES.CHAPTERS },
        { label: "Trash", to: DASHBOARD_ROUTES.TRASH },
    ]

    const sidebarContent = (
        <aside className="flex h-full w-64 flex-col justify-between overflow-y-auto border-r border-neutral-200 bg-white/95">

            {/* Top */}
            <div className="p-6 space-y-10">

                {/* Brand + close button row */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Dreamer's Palette
                        </h1>
                        <p className="text-sm text-neutral-500">
                            Author Studio
                        </p>
                    </div>
                    {/* Close button — mobile only */}
                    <button
                        className="md:hidden text-xl leading-none mt-1"
                        onClick={onClose}
                        aria-label="Close sidebar"
                    >
                        ✕
                    </button>
                </div>

                {/* Navigation */}
                <nav className="space-y-2">
                    {navItems.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            end={item.to === DASHBOARD_ROUTES.ROOT}
                            onClick={onClose}
                            className={({ isActive }) =>
                                `block px-3 py-2 rounded-lg text-sm transition ${
                                    isActive
                                        ? "bg-neutral-200/60 font-medium"
                                        : "text-neutral-600 hover:bg-neutral-100"
                                }`
                            }
                        >
                            {item.label}
                        </NavLink>
                    ))}
                </nav>
            </div>

            {/* Bottom Profile Area */}
            <div className="p-6 border-t border-neutral-200 space-y-4">

                {loading ? (
                    <div className="text-sm text-neutral-500">
                        Loading profile...
                    </div>
                ) : profile ? (
                    <>
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-neutral-300 flex items-center justify-center text-sm font-medium">
                                {profile.display_name?.charAt(0)?.toUpperCase() || "A"}
                            </div>
                            <div>
                                <div className="text-sm font-medium">
                                    {profile.display_name}
                                </div>
                                <div className="text-xs text-neutral-500">
                                    {profile.role}
                                </div>
                            </div>
                        </div>

                        <Button
                            variant="subtle"
                            size="sm"
                            className="w-full justify-start"
                            onClick={handleLogout}
                        >
                            Logout
                        </Button>
                        {error && (
                            <p role="alert" className="m-0 text-xs text-red-700">
                                {error}
                            </p>
                        )}
                    </>
                ) : (
                    <div role="alert" className="text-sm text-red-700">
                        {error || "Profile not found"}
                    </div>
                )}

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
                    <div
                        className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                        onClick={onClose}
                    />
                    {/* Sidebar panel */}
                    <div className="relative transition-transform duration-300">
                        {sidebarContent}
                    </div>
                </div>
            )}
        </>
    )
}
