import Container from "@/components/ui/Container";
import {Outlet} from "react-router-dom";

export default function ReaderLayout() {
    return (
        <div className="min-h-screen bg-white dark:bg-neutral-950 text-neutral-900
        dark:text-neutral-100 transition-colors">

            {/* Top spacing */}
            <div className="pt-20 pb-24">
                <Container size="default">
                    <Outlet />
                </Container>
            </div>

        </div>
    );
}