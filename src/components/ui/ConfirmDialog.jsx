import { useEffect, useRef } from "react"
import { createPortal } from "react-dom"
import Button from "./Button"

export default function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel = "Confirm",
    cancelLabel = "Cancel",
    variant = "danger",
    busy = false,
    confirmDisabled = false,
    onConfirm,
    onClose
}) {
    const cancelRef = useRef(null)
    const dialogRef = useRef(null)

    useEffect(() => {
        if (!open) return undefined

        const previousFocus = document.activeElement
        const handleKeyDown = (event) => {
            if (event.key === "Escape" && !busy) {
                onClose()
            }
            if (event.key === "Tab") {
                const focusable = dialogRef.current?.querySelectorAll(
                    "button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex='-1'])"
                )
                if (!focusable?.length) return
                const first = focusable[0]
                const last = focusable[focusable.length - 1]
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault()
                    last.focus()
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault()
                    first.focus()
                }
            }
        }

        document.addEventListener("keydown", handleKeyDown)
        requestAnimationFrame(() => cancelRef.current?.focus())

        return () => {
            document.removeEventListener("keydown", handleKeyDown)
            previousFocus?.focus?.()
        }
    }, [busy, onClose, open])

    if (!open) return null

    return createPortal(
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <button
                type="button"
                className="absolute inset-0 cursor-default bg-black/45"
                aria-label="Close confirmation"
                onClick={busy ? undefined : onClose}
            />
            <section
                ref={dialogRef}
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="confirm-dialog-title"
                aria-describedby="confirm-dialog-description"
                className="relative w-full max-w-md rounded-2xl bg-white p-6 text-neutral-900 shadow-xl"
            >
                <h2 id="confirm-dialog-title" className="text-xl font-semibold">
                    {title}
                </h2>
                <p id="confirm-dialog-description" className="mt-2 text-sm leading-relaxed text-neutral-600">
                    {description}
                </p>
                <div className="mt-6 flex flex-wrap justify-end gap-3">
                    <Button
                        ref={cancelRef}
                        type="button"
                        variant="ghost"
                        onClick={onClose}
                        disabled={busy}
                    >
                        {cancelLabel}
                    </Button>
                    <Button
                        type="button"
                        variant={variant}
                        onClick={onConfirm}
                        disabled={busy || confirmDisabled}
                    >
                        {busy ? "Working…" : confirmLabel}
                    </Button>
                </div>
            </section>
        </div>,
        document.body
    )
}
