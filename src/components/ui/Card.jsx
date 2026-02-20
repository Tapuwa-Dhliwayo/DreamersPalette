import clsx from "clsx";

export function Card({ children, className }) {
    return (
        <div
            className={clsx(
                "bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl shadow-sm p-6",
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
    return <div className="text-sm text-neutral-600 dark:text-neutral-400">{children}</div>;
}