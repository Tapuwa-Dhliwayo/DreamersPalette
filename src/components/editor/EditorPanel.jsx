import { useMemo } from "react"
import ReactMarkdown from "react-markdown"

export default function EditorPanel({
                                        value,
                                        onChange,
                                        placeholder = "Begin writing..."
                                    }) {
    const previewContent = useMemo(() => value || "", [value])

    return (
        <div className="w-full rounded-2xl shadow-sm border border-neutral-200 bg-white overflow-hidden">

            <div className="flex flex-col md:flex-row min-h-[500px]">

                {/* Editor */}
                <div className="flex-1 p-6">

                    <textarea
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        placeholder={placeholder}
                        className="
                            w-full h-full resize-none
                            bg-transparent
                            outline-none
                            text-base leading-relaxed
                            text-neutral-800
                            placeholder:text-neutral-400
                        "
                    />

                </div>

                {/* Divider */}
                <div className="hidden md:block w-px bg-neutral-200" />
                <div className="block md:hidden h-px bg-neutral-200" />

                {/* Preview */}
                <div className="flex-1 p-6 bg-neutral-50">

                    <div className="max-w-3xl mx-auto">

                        <ReactMarkdown
                            skipHtml
                            components={{
                                p: ({ node, ...props }) => (
                                    <p
                                        className="whitespace-pre-wrap leading-relaxed text-neutral-800 mb-4"
                                        {...props}
                                    />
                                ),
                                h1: ({ node, ...props }) => (
                                    <h1
                                        className="text-3xl font-semibold tracking-tight mb-6"
                                        {...props}
                                    />
                                ),
                                h2: ({ node, ...props }) => (
                                    <h2
                                        className="text-2xl font-medium mb-4"
                                        {...props}
                                    />
                                ),
                                blockquote: ({ node, ...props }) => (
                                    <blockquote
                                        className="border-l-2 border-neutral-300 pl-4 italic text-neutral-600 my-4 whitespace-pre-wrap"
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