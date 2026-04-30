import clsx from "clsx";

export default function Select({ className, children, ...props }) {
    return (
        <select
            className={clsx(
                "w-full px-4 py-2 rounded-xl border border-neutral-300 bg-transparent text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400",
                className
            )}
            {...props}
        >
            {children}
        </select>
    );
}

