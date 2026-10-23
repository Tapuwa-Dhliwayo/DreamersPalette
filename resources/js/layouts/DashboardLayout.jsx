import { useState } from "react"
import { Outlet } from "react-router-dom"
import Sidebar from "@/components/dashboard/Sidebar"

export default function DashboardLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(false)

    return (
        <div className="relative flex h-frame overflow-hidden bg-cover bg-[center_right_28%] bg-no-repeat text-neutral-900 transition-colors"
             style={{ backgroundImage: "url('/assets/global_atmosphere.png')" }}
        >
            <div
                className="pointer-events-none absolute inset-0 bg-neutral-950/20"
                aria-hidden="true"
            />

            {/* Sidebar */}
            <div className="relative z-10">
                <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
            </div>

            {/* Main Content */}
            <main className="relative z-10 flex min-h-0 flex-1 flex-col overflow-hidden px-4 py-6 md:px-10 md:py-12 xl:px-16">
                {/* Hamburger — mobile only */}
                <button
                    className="mb-4 min-h-11 min-w-11 shrink-0 rounded-xl bg-white text-xl leading-none shadow-sm md:hidden"
                    onClick={() => setSidebarOpen(true)}
                    aria-label="Open sidebar"
                >
                    ☰
                </button>

                <div className="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col overflow-hidden rounded-2xl border border-white/80 bg-neutral-50 p-4 shadow-[0_4px_8px_rgba(0,0,0,0.12)] md:p-8">
                    <Outlet />
                </div>
            </main>

        </div>
    )
}
