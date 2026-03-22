import { useState } from "react"
import { Outlet } from "react-router-dom"
import Sidebar from "@/components/dashboard/Sidebar"

export default function DashboardLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(false)

    return (
        <div className="h-screen flex overflow-hidden bg-neutral-50 dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 transition-colors">

            {/* Sidebar */}
            <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

            {/* Main Content */}
            <main className="flex-1 overflow-y-auto py-6 md:py-12 px-4 md:px-10">
                {/* Hamburger — mobile only */}
                <button
                    className="md:hidden mb-4 text-xl leading-none"
                    onClick={() => setSidebarOpen(true)}
                    aria-label="Open sidebar"
                >
                    ☰
                </button>

                <div className="max-w-5xl mx-auto">
                    <Outlet />
                </div>
            </main>

        </div>
    )
}