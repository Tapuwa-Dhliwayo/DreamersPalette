import clsx from "clsx";

export default function Input({ className, ...props }) {
    return (
        <input
            className={clsx(
                "w-full px-4 py-2 rounded-xl border border-neutral-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400",
                className
            )}
            {...props}
        />
    );
}