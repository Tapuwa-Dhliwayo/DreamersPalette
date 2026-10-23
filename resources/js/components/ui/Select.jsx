import clsx from "clsx";

export default function Select({ className, children, ...props }) {
    return (
        <select
            className={clsx(
                "min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-500",
                className
            )}
            {...props}
        >
            {children}
        </select>
    );
}
