import { useCallback, useEffect, useRef } from "react"
import { useBlocker } from "react-router-dom"

export function useUnsavedChanges(isDirty) {
    const allowNavigationRef = useRef(false)
    const blocker = useBlocker(() => isDirty && !allowNavigationRef.current)

    useEffect(() => {
        const handleBeforeUnload = (event) => {
            if (!isDirty || allowNavigationRef.current) return
            event.preventDefault()
            event.returnValue = ""
        }

        window.addEventListener("beforeunload", handleBeforeUnload)
        return () => window.removeEventListener("beforeunload", handleBeforeUnload)
    }, [isDirty])

    const proceed = useCallback(() => {
        allowNavigationRef.current = true
        blocker.proceed?.()
    }, [blocker])

    const reset = useCallback(() => blocker.reset?.(), [blocker])

    const markSafe = useCallback(() => {
        allowNavigationRef.current = true
    }, [])

    return {
        blocked: blocker.state === "blocked",
        proceed,
        reset,
        markSafe
    }
}
