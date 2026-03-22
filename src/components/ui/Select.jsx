import clsx from "clsx";

export default function Select({ className, children, ...props }) {
    return (
        <select
            className={clsx(
                "w-full px-4 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-transparent text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 dark:focus:ring-neutral-600",
                className
            )}
            {...props}
        >
            {children}
        </select>
    );
}

