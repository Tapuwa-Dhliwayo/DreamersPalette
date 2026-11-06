import { useCallback, useMemo, useRef, useState } from "react"
import { createPortal } from "react-dom"
import ReactMarkdown from "react-markdown"
import Button from "@/components/ui/Button"
import WritingAssistant from "@/components/editor/WritingAssistant"
import { useSelectionToolbar } from "@/hooks/useSelectionToolbar"
import { useTextSelection } from "@/hooks/useTextSelection"

export default function EditorPanel({
                                         value,
                                         onChange,
                                         placeholder = "Begin writing...",
                                         selectionTools = false
                                     }) {
    const previewContent = useMemo(() => value || "", [value])
    const textareaRef = useRef(null)
    const [assistantOpen, setAssistantOpen] = useState(false)
    const [mode, setMode] = useState("write")
    const { selection, captureSelection, clearSelection, hasSelection } = useTextSelection(value)

    const { toolbarRef, capture: captureToolbar, close: closeToolbar, position: toolbarPosition, visible: toolbarVisible } = useSelectionToolbar(selectionTools && mode !== "preview" && !assistantOpen)

    function updateValue(nextValue, selectionStart, selectionEnd = selectionStart) {
        onChange(nextValue)

        requestAnimationFrame(() => {
            const textarea = textareaRef.current
            if (!textarea) return

            textarea.focus()
            textarea.setSelectionRange(selectionStart, selectionEnd)
        })
    }

    function wrapSelection(prefix, suffix, fallbackText) {
        const textarea = textareaRef.current
        const currentValue = value || ""

        if (!textarea) {
            const insertedText = `${prefix}${fallbackText}${suffix}`
            onChange(`${currentValue}${insertedText}`)
            return
        }

        const selectionStart = textarea.selectionStart ?? currentValue.length
        const selectionEnd = textarea.selectionEnd ?? currentValue.length
        const selectedText = currentValue.slice(selectionStart, selectionEnd) || fallbackText

        const nextValue =
            currentValue.slice(0, selectionStart)
            + prefix
            + selectedText
            + suffix
            + currentValue.slice(selectionEnd)

        updateValue(
            nextValue,
            selectionStart + prefix.length,
            selectionStart + prefix.length + selectedText.length
        )
    }

    function transformSelectedLines(transformer, fallbackText) {
        const textarea = textareaRef.current
        const currentValue = value || ""

        if (!textarea) {
            onChange(transformer(fallbackText))
            return
        }

        const rawStart = textarea.selectionStart ?? currentValue.length
        const rawEnd = textarea.selectionEnd ?? currentValue.length
        const selectionStart = currentValue.lastIndexOf("\n", Math.max(rawStart - 1, 0)) + 1
        const nextLineBreak = currentValue.indexOf("\n", rawEnd)
        const selectionEnd = nextLineBreak === -1 ? currentValue.length : nextLineBreak
        const selectedBlock = currentValue.slice(selectionStart, selectionEnd) || fallbackText
        const transformedBlock = transformer(selectedBlock)

        const nextValue =
            currentValue.slice(0, selectionStart)
            + transformedBlock
            + currentValue.slice(selectionEnd)

        updateValue(
            nextValue,
            selectionStart,
            selectionStart + transformedBlock.length
        )
    }

    function prefixLines(prefix, fallbackText = "List item") {
        transformSelectedLines(
            (selectedBlock) => selectedBlock.split("\n").map((line) => `${prefix}${line}`).join("\n"),
            fallbackText
        )
    }

    function insertAtCursor(insertedText, cursorOffset = insertedText.length) {
        const textarea = textareaRef.current
        const currentValue = value || ""

        if (!textarea) {
            onChange(`${currentValue}${insertedText}`)
            return
        }

        const selectionStart = textarea.selectionStart ?? currentValue.length
        const selectionEnd = textarea.selectionEnd ?? currentValue.length
        const nextValue =
            currentValue.slice(0, selectionStart)
            + insertedText
            + currentValue.slice(selectionEnd)

        updateValue(nextValue, selectionStart + cursorOffset)
    }

    function replaceRange(selectionStart, selectionEnd, replacement) {
        const textarea = textareaRef.current
        const currentValue = value || ""
        if (!textarea || selectionEnd < selectionStart) return

        textarea.focus()
        textarea.setSelectionRange(selectionStart, selectionEnd)

        const replacedWithNativeUndo = document.execCommand?.("insertText", false, replacement)
        const nextValue =
            replacedWithNativeUndo
                ? textarea.value
                : currentValue.slice(0, selectionStart)
                + replacement
                + currentValue.slice(selectionEnd)

        if (!replacedWithNativeUndo) {
            updateValue(nextValue, selectionStart, selectionStart + replacement.length)
        } else {
            if (nextValue !== currentValue) onChange(nextValue)
            requestAnimationFrame(() => {
                textarea.focus()
                textarea.setSelectionRange(selectionStart, selectionStart + replacement.length)
            })
        }

        setAssistantOpen(false)
        clearSelection()
    }

    function replaceSelection(replacement) {
        replaceRange(selection.start, selection.end, replacement)
    }

    const closeAssistant = useCallback(() => {
        setAssistantOpen(false)
        requestAnimationFrame(() => textareaRef.current?.focus())
    }, [])

    function openAssistant() {
        const nextSelection = captureSelection(textareaRef.current)
        if (nextSelection.end <= nextSelection.start) return
        setAssistantOpen(true)
    }

    const toolbarActions = [
        { id: "heading", label: "H1", description: "Heading" },
        { id: "bold", label: "Bold", description: "Bold text" },
        { id: "italic", label: "Italic", description: "Italic text" },
        { id: "quote", label: "Quote", description: "Block quote" },
        { id: "bullets", label: "List", description: "Bulleted list" },
        { id: "numbers", label: "1.", description: "Numbered list" },
        { id: "link", label: "Link", description: "Insert link" },
        { id: "rule", label: "Rule", description: "Horizontal rule" },
        { id: "break", label: "Break", description: "Line break" }
    ]

    function formatSelection(actionId) {
        switch (actionId) {
            case "heading": return prefixLines("# ", "Heading")
            case "bold": return wrapSelection("**", "**", "bold text")
            case "italic": return wrapSelection("*", "*", "italic text")
            case "quote": return prefixLines("> ", "Quoted text")
            case "bullets": return prefixLines("- ")
            case "numbers": return transformSelectedLines(
                (block) => block.split("\n").map((line, index) => !line.trim() || /^\s*\d+\.\s+/.test(line) ? line : `${index + 1}. ${line}`).join("\n"),
                "List item"
            )
            case "link": return wrapSelection("[", "](https://example.com)", "link text")
            case "rule": return insertAtCursor("\n\n---\n\n")
            case "break": return insertAtCursor("  \n", 3)
            default: throw new Error("Unknown formatting action")
        }
    }

    return (
        <section className="publishing-editor" aria-label="Writing workspace">
            <div className="publishing-editor-modes" role="group" aria-label="Writing mode">
                {["write", "preview", "split"].map((writingMode) => (
                    <button key={writingMode} type="button" aria-pressed={mode === writingMode} onClick={() => setMode(writingMode)}>
                        {writingMode.charAt(0).toUpperCase() + writingMode.slice(1)}
                    </button>
                ))}
            </div>
            <div hidden={mode === "preview"} className="publishing-formatting border-b border-neutral-200 px-3 py-2 md:px-4">
                <div className="flex flex-wrap gap-1">
                    {toolbarActions.map((action) => (
                        <Button
                            key={action.id}
                            type="button"
                            variant="subtle"
                            size="sm"
                            className="shrink-0"
                            aria-label={action.description}
                            title={action.description}
                            onClick={() => formatSelection(action.id)}
                        >
                            {action.label}
                        </Button>
                    ))}
                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        className="ml-auto shrink-0"
                        disabled={!hasSelection}
                        aria-label="Assist with selected text"
                        title={hasSelection ? "Assist with selected text" : "Select text to use writing assistance"}
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={openAssistant}
                    >
                        Assist
                    </Button>
                </div>
            </div>

            {toolbarVisible && hasSelection && createPortal(
                <div
                    ref={toolbarRef}
                    className="publishing-selection-toolbar"
                    role="group"
                    aria-label="Selected text formatting"
                    style={toolbarPosition}
                    onPointerDown={(event) => event.preventDefault()}
                    onKeyDown={(event) => {
                        if (event.key === "Escape") {
                            closeToolbar()
                            textareaRef.current?.focus()
                        }
                    }}
                >
                    {toolbarActions.filter((action) => ["bold", "italic", "break"].includes(action.id)).map((action) => (
                        <Button key={action.id} type="button" variant="subtle" size="sm" aria-label={action.description} title={action.description} onClick={() => formatSelection(action.id)}>
                            {action.label}
                        </Button>
                    ))}
                    <Button type="button" size="sm" aria-label="Assist with selected text" onClick={() => {
                        closeToolbar()
                        openAssistant()
                    }}>Assist</Button>
                </div>, document.body
            )}

            <WritingAssistant
                open={assistantOpen}
                selection={selection}
                onClose={closeAssistant}
                onReplace={replaceSelection}
                onReplaceRange={replaceRange}
            />

            <div className={`publishing-editor-panes publishing-editor-${mode}`}>

                {/* Editor */}
                <div hidden={mode === "preview"} className="publishing-writing-pane">

                    <textarea
                        ref={textareaRef}
                        value={value ?? ""}
                        onChange={(event) => {
                            onChange(event.target.value)
                            closeToolbar()
                            if (assistantOpen) setAssistantOpen(false)
                        }}
                        onSelect={(event) => {
                            captureSelection(event.currentTarget)
                            captureToolbar(event.currentTarget)
                        }}
                        onBlur={(event) => {
                            if (!toolbarRef.current?.contains(event.relatedTarget)) closeToolbar()
                        }}
                        onKeyDown={(event) => {
                            if (event.key === "Escape") closeToolbar()
                        }}
                        placeholder={placeholder}
                        aria-label="Markdown editor"
                        className="
                            w-full min-h-[260px] md:min-h-[420px] resize-none
                            bg-transparent
                            focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-neutral-500
                            text-sm md:text-base leading-relaxed
                            text-neutral-800
                            placeholder:text-neutral-400
                        "
                    />

                </div>


                {/* Preview */}
                <div hidden={mode === "write"} className="publishing-preview-pane" aria-label="Rendered preview">
                    {!previewContent && <p className="text-sm text-neutral-500">Your formatted writing will appear here.</p>}

                    <div className="max-w-3xl mx-auto prose-reading">

                        <ReactMarkdown
                            skipHtml
                            components={{
                                p: ({ ...props }) => (
                                    <p
                                        className="whitespace-pre-wrap leading-relaxed text-neutral-800 mb-6"
                                        {...props}
                                    />
                                ),
                                h1: ({ ...props }) => (
                                    <h1
                                        className="text-3xl font-serif tracking-tight mb-6"
                                        {...props}
                                    />
                                ),
                                h2: ({ ...props }) => (
                                    <h2
                                        className="text-2xl font-serif font-medium mt-8 mb-4"
                                        {...props}
                                    />
                                ),
                                blockquote: ({ ...props }) => (
                                    <blockquote
                                        className="border-l-2 border-neutral-300 pl-4 italic text-neutral-600 my-6 whitespace-pre-wrap"
                                        {...props}
                                    />
                                )
                            }}
                        >
                            {previewContent}
                        </ReactMarkdown>

                    </div>

                </div>

            </div>

        </section>
    )
}
