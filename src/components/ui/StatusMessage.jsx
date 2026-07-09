const tones = {
    error: "border-red-200 bg-red-50 text-red-800",
    success: "border-green-200 bg-green-50 text-green-800",
    info: "border-neutral-200 bg-neutral-50 text-neutral-700"
}

export default function StatusMessage({ tone = "info", children, action }) {
    if (!children) return null

    return (
        <div
            role={tone === "error" ? "alert" : "status"}
            aria-live="polite"
            className={`flex flex-col gap-3 rounded-xl border px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between ${tones[tone]}`}
        >
            <span>{children}</span>
            {action}
        </div>
    )
}
