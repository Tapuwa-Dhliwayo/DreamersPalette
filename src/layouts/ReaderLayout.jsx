import Container from "@/components/ui/Container";

export default function ReaderLayout({ children }) {
    return (
        <div className="min-h-screen bg-white dark:bg-neutral-950 text-neutral-900
        dark:text-neutral-100 transition-colors">

            {/* Top spacing */}
            <div className="pt-16 pb-24">
                <Container size="default">
                    {children}
                </Container>
            </div>

        </div>
    );
}