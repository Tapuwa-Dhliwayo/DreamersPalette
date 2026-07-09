function formatTime(value) {
    if (!value) return null
    return new Intl.DateTimeFormat(undefined, {
        hour: "numeric",
        minute: "2-digit"
    }).format(new Date(value))
}

export default function EditorSaveState({
    saving,
    isDirty,
    persistedAt,
    recoveredAt,
    error
}) {
    let label = "All changes saved"
    let tone = "text-neutral-600"

    if (error) {
        label = "Save failed"
        tone = "text-red-700"
    } else if (saving) {
        label = "Saving…"
    } else if (recoveredAt) {
        label = `Recovered local draft from ${formatTime(recoveredAt)}`
        tone = "text-amber-800"
    } else if (isDirty && persistedAt) {
        label = `Unsaved · recovery copy stored ${formatTime(persistedAt)}`
        tone = "text-amber-800"
    } else if (isDirty) {
        label = "Unsaved changes"
        tone = "text-amber-800"
    }

    return (
        <span role="status" aria-live="polite" className={`text-xs ${tone}`}>
            {label}
        </span>
    )
}
