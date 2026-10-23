import clsx from "clsx";

export default function Textarea({ className, ...props }) {
    return (
        <textarea
            className={clsx(
                "w-full rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm leading-relaxed text-neutral-900 placeholder:text-neutral-600 focus:outline-none focus:ring-2 focus:ring-neutral-500",
                className
            )}
            {...props}
        />
    );
}
