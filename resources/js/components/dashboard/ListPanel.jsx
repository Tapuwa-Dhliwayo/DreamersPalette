import { useEffect, useRef } from "react"
import clsx from "clsx"

export function ListPanel({ className, children }) {
    return <div className={clsx("flex min-h-0 flex-1 flex-col", className)}>{children}</div>
}

export function ListPanelHeader({ className, children }) {
    return <div className={clsx("shrink-0 space-y-6", className)}>{children}</div>
}

export function ListPanelBody({ scrollKey, className, children }) {
    const bodyRef = useRef(null)

    useEffect(() => {
        bodyRef.current?.scrollTo({ top: 0 })
    }, [scrollKey])

    return (
        <div
            ref={bodyRef}
            className={clsx("min-h-0 flex-1 overflow-y-auto overscroll-contain", className)}
        >
            {children}
        </div>
    )
}

export function ListPanelFooter({ className, children }) {
    return <div className={clsx("shrink-0 border-t border-neutral-200 pt-3", className)}>{children}</div>
}
