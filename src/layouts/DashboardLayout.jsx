import Container from "@/components/ui/Container";
import Button from "@/components/ui/Button";

export default function DashboardLayout({ children }) {
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

                        <Button variant="subtle" size="sm">
                            Logout
                        </Button>
                    </div>
                </Container>
            </header>

            {/* Main Content */}
            <main className="py-12">
                <Container size="wide">
                    {children}
                </Container>
            </main>

        </div>
    );
}