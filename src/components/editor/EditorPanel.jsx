import { useCallback, useMemo, useRef, useState } from "react"
import ReactMarkdown from "react-markdown"
import Button from "@/components/ui/Button"
import WritingAssistant from "@/components/editor/WritingAssistant"
import { useTextSelection } from "@/hooks/useTextSelection"

export default function EditorPanel({
                                         value,
                                         onChange,
                                         placeholder = "Begin writing..."
                                     }) {
    const previewContent = useMemo(() => value || "", [value])
    const textareaRef = useRef(null)
    const [assistantOpen, setAssistantOpen] = useState(false)
    const { selection, captureSelection, clearSelection, hasSelection } = useTextSelection(value)

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
        {
            label: "H1",
            description: "Heading",
            onClick: () => prefixLines("# ", "Heading")
        },
        {
            label: "Bold",
            description: "Bold text",
            onClick: () => wrapSelection("**", "**", "bold text")
        },
        {
            label: "Italic",
            description: "Italic text",
            onClick: () => wrapSelection("*", "*", "italic text")
        },
        {
            label: "Quote",
            description: "Block quote",
            onClick: () => prefixLines("> ", "Quoted text")
        },
        {
            label: "List",
            description: "Bulleted list",
            onClick: () => prefixLines("- ")
        },
        {
            label: "1.",
            description: "Numbered list",
            onClick: () => transformSelectedLines(
                (selectedBlock) => selectedBlock
                    .split("\n")
                    .map((line, index) => {
                        if (!line.trim()) return line
                        return line.match(/^\s*\d+\.\s+/) ? line : `${index + 1}. ${line}`
                    })
                    .join("\n"),
                "List item"
            )
        },
        {
            label: "Link",
            description: "Insert link",
            onClick: () => wrapSelection("[", "](https://example.com)", "link text")
        },
        {
            label: "Rule",
            description: "Horizontal rule",
            onClick: () => insertAtCursor("\n\n---\n\n")
        },
        {
            label: "Break",
            description: "Line break",
            onClick: () => insertAtCursor("  \n", 3)
        }
    ]

    return (
        <div className="w-full rounded-2xl shadow-sm border border-neutral-200 bg-white overflow-hidden">
            <div className="border-b border-neutral-200 bg-neutral-50/90 px-3 py-2 md:px-4">
                <div className="flex gap-2 overflow-x-auto pb-1">
                    {toolbarActions.map((action) => (
                        <Button
                            key={action.label}
                            type="button"
                            variant="subtle"
                            size="sm"
                            className="shrink-0"
                            aria-label={action.description}
                            title={action.description}
                            onClick={action.onClick}
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

            <WritingAssistant
                open={assistantOpen}
                selection={selection}
                onClose={closeAssistant}
                onReplace={replaceSelection}
                onReplaceRange={replaceRange}
            />

            <div className="flex flex-col md:flex-row min-h-[280px] md:min-h-[500px]">

                {/* Editor */}
                <div className="flex-1 p-4 md:p-6 min-h-[220px]">

                    <textarea
                        ref={textareaRef}
                        value={value ?? ""}
                        onChange={(event) => {
                            onChange(event.target.value)
                            if (assistantOpen) setAssistantOpen(false)
                        }}
                        onSelect={(event) => captureSelection(event.currentTarget)}
                        placeholder={placeholder}
                        aria-label="Markdown editor"
                        className="
                            w-full min-h-[260px] md:min-h-[420px] resize-none
                            bg-transparent
                            outline-none
                            text-sm md:text-base leading-relaxed
                            text-neutral-800
                            placeholder:text-neutral-400
                        "
                    />

                </div>

                {/* Divider */}
                <div className="hidden md:block w-px bg-neutral-200" />
                <div className="block md:hidden h-px bg-neutral-200" />

                {/* Preview */}
                <div className="flex-1 p-4 md:p-6 bg-neutral-50 min-h-[220px]" aria-label="Rendered preview">

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

        </div>
    )
}
