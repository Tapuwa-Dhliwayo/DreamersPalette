import clsx from "clsx";
import { forwardRef } from "react";

const Button = forwardRef(function Button({
                                   children,
                                   variant = "primary",
                                   size = "md",
                                   className,
                                   ...props
                               }, ref) {
    const base =
        "inline-flex min-h-11 items-center justify-center rounded-xl font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 active:bg-neutral-300 disabled:pointer-events-none disabled:opacity-50";

    const variants = {
        primary:
            "bg-neutral-900 text-white hover:bg-neutral-800",
        subtle:
            "bg-neutral-100 text-neutral-900 hover:bg-neutral-200",
        ghost:
            "bg-transparent text-neutral-900 hover:bg-neutral-100",
        danger:
            "bg-red-600 text-white hover:bg-red-500",
    };

    const sizes = {
        sm: "px-3 py-1.5 text-sm",
        md: "px-4 py-2 text-sm",
        lg: "px-6 py-3 text-base",
    };

    return (
        <button
            ref={ref}
            className={clsx(base, variants[variant], sizes[size], className)}
            {...props}
        >
            {children}
        </button>
    );
});

export default Button;
