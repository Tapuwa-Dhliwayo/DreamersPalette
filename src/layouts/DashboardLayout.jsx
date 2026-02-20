import Container from "@/components/ui/Container";
import Button from "@/components/ui/Button";
import {Outlet, useNavigate} from "react-router-dom";
import {signOut} from "../services/authService.js";

export default function DashboardLayout() {
    const navigate = useNavigate();

    async function handleLogout() {
        try {
            await signOut();
            navigate("/login", { replace: true });
        } catch (err) {
            console.error("Logout failed:", err);
        }
    }
    return (
        <div className="min-h-screen bg-neutral-50 dark:bg-neutral-950 text-neutral-900
        dark:text-neutral-100 transition-colors">

            {/* Header */}
            <header className="border-b border-neutral-200 dark:border-neutral-800 bg-white/70
            dark:bg-neutral-900/70 backdrop-blur-sm">
                <Container size="wide">
                    <div className="flex items-center justify-between py-4">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Dreamer’s Palette
                        </h1>

                        <Button variant="subtle" size="sm" onClick={handleLogout}>
                            Logout
                        </Button>
                    </div>
                </Container>
            </header>

            {/* Main Content */}
            <main className="py-12">
                <Container size="wide">
                    <Outlet />
                </Container>
            </main>

        </div>
    );
}