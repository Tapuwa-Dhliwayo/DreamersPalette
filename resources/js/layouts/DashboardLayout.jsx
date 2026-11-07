import { useState } from "react"
import { Outlet } from "react-router-dom"
import AssistantActivity from "@/components/dashboard/AssistantActivity"
import Sidebar from "@/components/dashboard/Sidebar"

export default function DashboardLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(false)

    return (
        <div className="publishing-app relative flex h-frame overflow-hidden bg-cover bg-[center_right_28%] bg-no-repeat text-neutral-900 transition-colors"
             style={{ backgroundImage: "url('/assets/global_atmosphere.png')" }}
        >
            <div
                className="pointer-events-none absolute inset-0 bg-neutral-950/20"
                aria-hidden="true"
            />

            {/* Sidebar */}
            <div className={`relative ${sidebarOpen ? "z-20" : "z-10"}`}>
                <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
            </div>

            {/* Main Content */}
            <a href="#publishing-content" className="publishing-skip">Skip to content</a>
            <main id="publishing-content" tabIndex={-1} inert={sidebarOpen} className="relative z-10 flex min-h-0 flex-1 flex-col overflow-hidden publishing-main px-3 py-4 md:px-6 md:py-6 xl:px-10">
                {/* Hamburger — mobile only */}
                <button
                    className="mb-4 min-h-11 min-w-11 shrink-0 rounded-xl bg-white text-xl leading-none shadow-sm md:hidden"
                    onClick={() => setSidebarOpen(true)}
                    aria-label="Open sidebar"
                >
                    ☰
                </button>

                <div className="publishing-surface mx-auto flex min-h-0 w-full max-w-7xl flex-1 flex-col overflow-hidden p-4 md:p-7">
                    <AssistantActivity />
                    <Outlet />
                </div>
            </main>

        </div>
    )
}
