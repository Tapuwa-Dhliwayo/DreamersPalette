import clsx from "clsx";

export default function Input({ className, ...props }) {
    return (
        <input
            className={clsx(
                "w-full px-4 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 dark:focus:ring-neutral-600",
                className
            )}
            {...props}
        />
    );
}