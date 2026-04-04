import { useMemo, useRef } from "react"
import ReactMarkdown from "react-markdown"
import Button from "@/components/ui/Button"

export default function EditorPanel({
                                         value,
                                         onChange,
                                         placeholder = "Begin writing..."
                                     }) {
    const previewContent = useMemo(() => value || "", [value])
    const textareaRef = useRef(null)

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

    const toolbarActions = [
        {
            label: "H1",
            onClick: () => prefixLines("# ", "Heading")
        },
        {
            label: "Bold",
            onClick: () => wrapSelection("**", "**", "bold text")
        },
        {
            label: "Italic",
            onClick: () => wrapSelection("*", "*", "italic text")
        },
        {
            label: "Quote",
            onClick: () => prefixLines("> ", "Quoted text")
        },
        {
            label: "List",
            onClick: () => prefixLines("- ")
        },
        {
            label: "1.",
            onClick: () => transformSelectedLines(
                (selectedBlock) => selectedBlock
                    .split("\n")
                    .map((line, index) => line.match(/^\d+\.\s+/) ? line : `${index + 1}. ${line}`)
                    .join("\n"),
                "List item"
            )
        },
        {
            label: "Link",
            onClick: () => wrapSelection("[", "](https://example.com)", "link text")
        },
        {
            label: "Rule",
            onClick: () => insertAtCursor("\n\n---\n\n")
        },
        {
            label: "Break",
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
                            onClick={action.onClick}
                        >
                            {action.label}
                        </Button>
                    ))}
                </div>
            </div>

            <div className="flex flex-col md:flex-row min-h-[280px] md:min-h-[500px]">

                {/* Editor */}
                <div className="flex-1 p-4 md:p-6 min-h-[220px]">

                    <textarea
                        ref={textareaRef}
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        placeholder={placeholder}
                        className="
                            w-full h-full resize-none
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
                <div className="flex-1 p-4 md:p-6 bg-neutral-50 min-h-[220px]">

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
