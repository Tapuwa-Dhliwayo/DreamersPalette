import { Outlet } from "react-router-dom"
import Sidebar from "@/components/dashboard/Sidebar"

export default function DashboardLayout() {
    return (
        <div className="min-h-screen flex bg-neutral-50 dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 transition-colors">

            {/* Sidebar */}
            <Sidebar />

            {/* Main Content */}
            <main className="flex-1 py-12 px-10">
                <div className="max-w-5xl mx-auto">
                    <Outlet />
                </div>
            </main>

        </div>
    )
}