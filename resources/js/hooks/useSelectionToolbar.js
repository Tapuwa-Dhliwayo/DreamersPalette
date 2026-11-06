import { useCallback, useEffect, useLayoutEffect, useRef, useState } from "react"

function selectionAnchor(textarea) {
    const bounds = textarea.getBoundingClientRect()
    const mirror = document.createElement("div")
    const computed = getComputedStyle(textarea)

    for (const property of ["boxSizing", "fontFamily", "fontSize", "fontWeight", "fontStyle", "lineHeight", "letterSpacing", "textTransform", "textIndent", "textAlign", "direction", "tabSize", "paddingTop", "paddingRight", "paddingBottom", "paddingLeft", "borderTopWidth", "borderRightWidth", "borderBottomWidth", "borderLeftWidth", "borderStyle"]) {
        mirror.style[property] = computed[property]
    }
    Object.assign(mirror.style, {
        position: "fixed", left: `${bounds.left}px`, top: `${bounds.top}px`,
        width: `${bounds.width - (textarea.offsetWidth - textarea.clientWidth - parseFloat(computed.borderLeftWidth) - parseFloat(computed.borderRightWidth))}px`,
        whiteSpace: "pre-wrap", overflowWrap: "break-word", visibility: "hidden", pointerEvents: "none"
    })
    mirror.textContent = textarea.value.slice(0, textarea.selectionStart)
    const selected = document.createElement("span")
    selected.textContent = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd)
    mirror.append(selected)
    document.body.append(mirror)
    const firstLine = selected.getClientRects()[0] || bounds
    const anchor = {
        left: firstLine.left + firstLine.width / 2 - textarea.scrollLeft,
        top: firstLine.top - textarea.scrollTop,
        bottom: firstLine.bottom - textarea.scrollTop
    }
    mirror.remove()
    return anchor
}

export function useSelectionToolbar(enabled) {
    const toolbarRef = useRef(null)
    const [anchor, setAnchor] = useState(null)
    const [position, setPosition] = useState({ left: 12, top: 12 })
    const close = useCallback(() => setAnchor(null), [])
    const capture = useCallback((textarea) => {
        if (!enabled || !textarea.value.slice(textarea.selectionStart, textarea.selectionEnd).trim()) {
            close()
            return
        }
        setAnchor(selectionAnchor(textarea))
    }, [enabled, close])

    useLayoutEffect(() => {
        if (!anchor || !toolbarRef.current) return
        const { width, height } = toolbarRef.current.getBoundingClientRect()
        const top = anchor.top >= height + 20 ? anchor.top - height - 8 : anchor.bottom + 8
        setPosition({
            left: Math.max(12, Math.min(anchor.left - width / 2, window.innerWidth - width - 12)),
            top: Math.max(12, Math.min(top, window.innerHeight - height - 12))
        })
    }, [anchor])

    useEffect(() => {
        if (!anchor) return
        window.addEventListener("scroll", close, true)
        window.addEventListener("resize", close)
        return () => {
            window.removeEventListener("scroll", close, true)
            window.removeEventListener("resize", close)
        }
    }, [anchor, close])

    return { toolbarRef, capture, close, position, visible: enabled && Boolean(anchor) }
}
