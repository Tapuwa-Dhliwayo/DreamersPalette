export default function Logo({
                                 size = "md",
                                 variant = "default", // default | subtle | accent
                                 stacked = false,
                                 // eslint-disable-next-line no-unused-vars -- WordmarkTag is used as a JSX tag name below; not tracked as a reference by no-unused-vars without eslint-plugin-react
                                 as: WordmarkTag = "span",
                                 className = ""
                             }) {
    const sizes = {
        sm: "text-lg",
        md: "text-2xl",
        lg: "text-4xl",
        xl: "text-6xl",
        home: "text-2xl md:text-3xl"
    }

    const colorVariants = {
        default: "",
        subtle: "opacity-70"
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
            <WordmarkTag
                className={`font-serif tracking-tight font-medium opacity-100 ${sizes[size]} ${colorVariants[variant]}`}
            >
                Dreamer’s Palette
            </WordmarkTag>
        </div>
    )
}
