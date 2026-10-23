import clsx from "clsx";

export default function Badge({ children, variant = "default" }) {
    const variants = {
        default:
            "bg-neutral-100 text-neutral-700",
        success:
            "bg-green-100 text-green-700",
        danger:
            "bg-red-100 text-red-700",
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