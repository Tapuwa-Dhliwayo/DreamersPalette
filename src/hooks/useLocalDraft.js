import { useEffect, useMemo, useRef, useState } from "react"

export function useLocalDraft({ key, form, initialForm, setForm, enabled = true }) {
    const [recoveredAt, setRecoveredAt] = useState(null)
    const [persistedAt, setPersistedAt] = useState(null)
    const restoredKeyRef = useRef(null)

    const isDirty = useMemo(
        () => Boolean(initialForm) && JSON.stringify(form) !== JSON.stringify(initialForm),
        [form, initialForm]
    )

    useEffect(() => {
        if (!enabled || !key || !initialForm || restoredKeyRef.current === key) return
        restoredKeyRef.current = key
        const stored = window.localStorage.getItem(key)
        if (!stored) return

        try {
            const draft = JSON.parse(stored)
            if (
                draft?.form
                && draft.savedAt
                && JSON.stringify(draft.form) !== JSON.stringify(initialForm)
            ) {
                window.queueMicrotask(() => {
                    setForm(draft.form)
                    setRecoveredAt(draft.savedAt)
                })
            }
        } catch {
            window.localStorage.removeItem(key)
        }
    }, [enabled, initialForm, key, setForm])

    useEffect(() => {
        if (!enabled || !key || !initialForm || !isDirty) return undefined

        const timeout = window.setTimeout(() => {
            const savedAt = new Date().toISOString()
            window.localStorage.setItem(key, JSON.stringify({ form, savedAt }))
            setPersistedAt(savedAt)
        }, 500)

        return () => window.clearTimeout(timeout)
    }, [enabled, form, initialForm, isDirty, key])

    function clearDraft() {
        if (key) window.localStorage.removeItem(key)
        setRecoveredAt(null)
        setPersistedAt(null)
    }

    return {
        isDirty,
        recoveredAt,
        persistedAt,
        clearDraft
    }
}
