import clsx from "clsx";

export default function Textarea({ className, ...props }) {
    return (
        <textarea
            className={clsx(
                "w-full px-4 py-3 rounded-xl border border-neutral-300 bg-white text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-neutral-400",
                className
            )}
            {...props}
        />
    );
}