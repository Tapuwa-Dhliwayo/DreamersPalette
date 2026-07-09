import clsx from "clsx";

export default function Input({ className, ...props }) {
    return (
        <input
            className={clsx(
                "min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 placeholder:text-neutral-600 focus:outline-none focus:ring-2 focus:ring-neutral-500",
                className
            )}
            {...props}
        />
    );
}
