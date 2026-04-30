import clsx from "clsx";

export function Card({ children, className }) {
    return (
        <div
            className={clsx(
                "bg-white border border-neutral-200 rounded-2xl shadow-sm py-2 px-8",
                className
            )}
        >
            {children}
        </div>
    );
}

export function CardHeader({ children }) {
    return <div className="mb-4 space-y-1">{children}</div>;
}

export function CardTitle({ children }) {
    return (
        <h3 className="text-lg font-semibold tracking-tight">
            {children}
        </h3>
    );
}

export function CardContent({ children }) {
    return <div className="text-sm text-neutral-600">{children}</div>;
}