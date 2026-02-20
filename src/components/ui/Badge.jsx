import clsx from "clsx";

export default function Badge({ children, variant = "default" }) {
    const variants = {
        default:
            "bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300",
        success:
            "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
        danger:
            "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
    };

    return (
        <span
            className={clsx(
                "px-2 py-1 text-xs rounded-full font-medium",
                variants[variant]
            )}
        >
      {children}
    </span>
    );
}