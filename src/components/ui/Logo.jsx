export default function Logo({
                                 size = "md",
                                 variant = "default", // default | subtle | accent
                                 stacked = false,
                                 className = ""
                             }) {
    const sizes = {
        sm: "text-lg",
        md: "text-2xl",
        lg: "text-4xl",
        xl: "text-6xl"
    }

    const colorVariants = {
        default: "text-current",
        subtle: "opacity-70 text-current",
        accent: ""
    }

    const accentStyle =
        variant === "accent"
            ? { color: "var(--accent-color)" }
            : undefined

    return (
        <div
            className={`inline-flex items-center gap-3 ${stacked ? "flex-col gap-4" : ""} ${className}`}
            style={accentStyle}
        >
            {/* Symbol */}
            <svg
                width="28"
                height="28"
                viewBox="0 0 100 100"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                className="opacity-90"
            >
                <path
                    d="M15 60 C 40 20, 60 20, 85 60"
                    stroke="currentColor"
                    strokeWidth="6"
                    strokeLinecap="round"
                />
            </svg>

            {/* Wordmark */}
            <span
                className={`font-serif tracking-tight font-medium ${sizes[size]} ${colorVariants[variant]}`}
            >
                Dreamer’s Palette
            </span>
        </div>
    )
}