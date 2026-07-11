import { useEffect, useRef, useState } from "react"
import Button from "@/components/ui/Button"
import {
    getDefinitions,
    getGrammarSuggestions,
    getSpellingSuggestions,
    getThesaurusSuggestions
} from "@/services/writingAssistantService"

const sections = [
    { value: "spelling", label: "Spelling" },
    { value: "thesaurus", label: "Thesaurus" },
    { value: "definition", label: "Definition" },
    { value: "grammar", label: "Grammar" }
]

export default function WritingAssistant({ open, selection, onClose, onReplace, onReplaceRange }) {
    const [activeSection, setActiveSection] = useState("spelling")
    const [lookups, setLookups] = useState({
        spelling: { status: "idle", data: [] },
        thesaurus: { status: "idle", data: null },
        definition: { status: "idle", data: [] },
        grammar: { status: "idle", data: [] }
    })
    const panelRef = useRef(null)

    useEffect(() => {
        if (!open || !selection.text.trim()) return undefined

        const controller = new AbortController()
        const timeout = window.setTimeout(async () => {
            setLookups({
                spelling: { status: "loading", data: [] },
                thesaurus: { status: "loading", data: null },
                definition: { status: "loading", data: [] },
                grammar: { status: "loading", data: [] }
            })

            const requests = [
                getSpellingSuggestions(selection.text, { signal: controller.signal }),
                getThesaurusSuggestions(selection.text, { signal: controller.signal }),
                getDefinitions(selection.text, { signal: controller.signal }),
                getGrammarSuggestions(selection.sentence)
            ]
            const [spelling, thesaurus, definition, grammar] = await Promise.allSettled(requests)
            if (controller.signal.aborted) return

            setLookups({
                spelling: spelling.status === "fulfilled"
                    ? { status: "success", data: spelling.value }
                    : { status: "error", data: [] },
                thesaurus: thesaurus.status === "fulfilled"
                    ? { status: "success", data: thesaurus.value }
                    : { status: "error", data: null },
                definition: definition.status === "fulfilled"
                    ? { status: "success", data: definition.value }
                    : { status: "error", data: [] },
                grammar: grammar.status === "fulfilled"
                    ? { status: "success", data: grammar.value }
                    : { status: "error", data: [] }
            })
        }, 300)

        return () => {
            window.clearTimeout(timeout)
            controller.abort()
        }
    }, [open, selection.sentence, selection.text])

    useEffect(() => {
        if (!open) return undefined

        const panel = panelRef.current
        const firstControl = panel?.querySelector("button")
        firstControl?.focus()

        function handleKeyDown(event) {
            if (event.key === "Escape") {
                event.preventDefault()
                onClose()
                return
            }

            if (event.key !== "Tab" || !panel) return
            const controls = Array.from(panel.querySelectorAll("button:not(:disabled)"))
            if (controls.length === 0) return
            const first = controls[0]
            const last = controls[controls.length - 1]

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault()
                last.focus()
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault()
                first.focus()
            }
        }

        document.addEventListener("keydown", handleKeyDown)
        return () => document.removeEventListener("keydown", handleKeyDown)
    }, [onClose, open])

    if (!open) return null

    function renderStatus(state, emptyMessage) {
        if (state.status === "idle" || state.status === "loading") {
            return <p className="text-sm text-neutral-600" role="status">Looking for suggestions…</p>
        }
        if (state.status === "error") {
            return <p className="text-sm text-neutral-600" role="status">Suggestions are unavailable right now. Your writing is unaffected.</p>
        }
        if (!state.data || state.data.length === 0) {
            return <p className="text-sm text-neutral-600">{emptyMessage}</p>
        }
        return null
    }

    function renderSuggestionGroup(title, suggestions) {
        if (!suggestions?.length) return null
        return (
            <div className="space-y-2">
                <h4 className="text-xs font-medium text-neutral-600">{title}</h4>
                <div className="flex flex-wrap gap-2">
                    {suggestions.map((suggestion) => (
                        <Button
                            key={suggestion.id}
                            type="button"
                            size="sm"
                            variant="subtle"
                            onClick={() => onReplace(suggestion.replacement)}
                        >
                            {suggestion.label}
                        </Button>
                    ))}
                </div>
            </div>
        )
    }

    return (
        <section
            ref={panelRef}
            role="dialog"
            aria-modal="true"
            aria-labelledby="writing-assistant-title"
            className="border-b border-neutral-200 bg-white px-4 py-4 md:px-6"
        >
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <h3 id="writing-assistant-title" className="text-sm font-semibold text-neutral-900">
                        Writing assistant
                    </h3>
                    <p className="mt-1 truncate text-sm text-neutral-600">
                        Reviewing “{selection.text}”
                    </p>
                </div>
                <Button type="button" size="sm" variant="ghost" onClick={onClose}>
                    Close
                </Button>
            </div>

            <div className="mt-4 flex gap-1 overflow-x-auto border-b border-neutral-200" role="tablist" aria-label="Writing assistance categories">
                {sections.map((section) => (
                    <button
                        key={section.value}
                        type="button"
                        role="tab"
                        aria-selected={activeSection === section.value}
                        aria-controls="writing-assistant-panel"
                        onClick={() => setActiveSection(section.value)}
                        className={`min-h-11 shrink-0 border-b-2 px-3 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-neutral-500 ${
                            activeSection === section.value
                                ? "border-neutral-900 text-neutral-900"
                                : "border-transparent text-neutral-600 hover:text-neutral-900"
                        }`}
                    >
                        {section.label}
                    </button>
                ))}
            </div>

            <div id="writing-assistant-panel" role="tabpanel" className="py-4">
                {activeSection === "spelling" && (
                    <div className="space-y-3">
                        {renderStatus(lookups.spelling, "No alternative spellings found.")}
                        {lookups.spelling.status === "success" && renderSuggestionGroup("Possible spellings", lookups.spelling.data)}
                    </div>
                )}

                {activeSection === "thesaurus" && (
                    <div className="space-y-5">
                        {lookups.thesaurus.status !== "success" && renderStatus(lookups.thesaurus, "No related language found.")}
                        {lookups.thesaurus.status === "success" && (
                            <>
                                {renderSuggestionGroup("Synonyms", lookups.thesaurus.data.synonyms)}
                                {renderSuggestionGroup("Antonyms", lookups.thesaurus.data.antonyms)}
                                {renderSuggestionGroup("Related words & phrases", lookups.thesaurus.data.related)}
                                {lookups.thesaurus.data.synonyms.length === 0
                                    && lookups.thesaurus.data.antonyms.length === 0
                                    && lookups.thesaurus.data.related.length === 0
                                    && <p className="text-sm text-neutral-600">No related language found.</p>}
                            </>
                        )}
                    </div>
                )}

                {activeSection === "definition" && (
                    <div className="space-y-4">
                        {renderStatus(lookups.definition, "No definition found. Try selecting one word.")}
                        {lookups.definition.status === "success" && lookups.definition.data.map((entry) => (
                            <article key={entry.id} className="max-w-2xl border-b border-neutral-200 pb-4 last:border-0 last:pb-0">
                                <p className="text-xs font-medium text-neutral-600">{entry.partOfSpeech}</p>
                                <p className="mt-1 text-sm leading-relaxed text-neutral-800">{entry.definition}</p>
                                {entry.example && <p className="mt-1 text-xs italic leading-relaxed text-neutral-600">“{entry.example}”</p>}
                            </article>
                        ))}
                    </div>
                )}

                {activeSection === "grammar" && (
                    <div className="space-y-4">
                        {renderStatus(lookups.grammar, "No grammar or punctuation suggestions found.")}
                        {lookups.grammar.status === "success" && lookups.grammar.data.map((suggestion) => (
                            <article key={suggestion.id} className="max-w-2xl border-b border-neutral-200 pb-4 last:border-0 last:pb-0">
                                <p className="text-xs font-medium text-neutral-600">
                                    {suggestion.category || "Grammar"}
                                </p>
                                <p className="mt-1 text-sm leading-relaxed text-neutral-800">
                                    {suggestion.message}
                                </p>
                                {suggestion.original && (
                                    <p className="mt-2 text-xs text-neutral-600">
                                        Review “{suggestion.original}”
                                    </p>
                                )}
                                {suggestion.replacements.length > 0 && (
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {suggestion.replacements.map((replacement) => (
                                            <Button
                                                key={replacement.id}
                                                type="button"
                                                size="sm"
                                                variant="subtle"
                                                onClick={() => onReplaceRange(
                                                    selection.sentenceStart + suggestion.offset,
                                                    selection.sentenceStart + suggestion.offset + suggestion.length,
                                                    replacement.replacement
                                                )}
                                            >
                                                Replace with “{replacement.label}”
                                            </Button>
                                        ))}
                                    </div>
                                )}
                            </article>
                        ))}
                    </div>
                )}
            </div>
        </section>
    )
}
