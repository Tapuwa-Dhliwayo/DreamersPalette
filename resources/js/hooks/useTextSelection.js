import { useCallback, useState } from "react"

const EMPTY_SELECTION = {
    start: 0,
    end: 0,
    text: "",
    sentence: "",
    sentenceStart: 0,
    sentenceEnd: 0
}

function getSurroundingSentence(value, start, end) {
    const content = value || ""
    const boundaryPattern = /[.!?\n]/
    let sentenceStart = start
    let sentenceEnd = end

    while (sentenceStart > 0 && !boundaryPattern.test(content[sentenceStart - 1])) {
        sentenceStart -= 1
    }

    while (sentenceEnd < content.length && !boundaryPattern.test(content[sentenceEnd])) {
        sentenceEnd += 1
    }

    if (sentenceEnd < content.length && /[.!?]/.test(content[sentenceEnd])) {
        sentenceEnd += 1
    }

    while (sentenceStart < sentenceEnd && /\s/.test(content[sentenceStart])) sentenceStart += 1
    while (sentenceEnd > sentenceStart && /\s/.test(content[sentenceEnd - 1])) sentenceEnd -= 1

    return {
        text: content.slice(sentenceStart, sentenceEnd),
        start: sentenceStart,
        end: sentenceEnd
    }
}

export function useTextSelection(value) {
    const [selection, setSelection] = useState(EMPTY_SELECTION)

    const captureSelection = useCallback((textarea) => {
        if (!textarea) return EMPTY_SELECTION

        const content = value || ""
        const start = textarea.selectionStart ?? 0
        const end = textarea.selectionEnd ?? start
        const sentence = getSurroundingSentence(content, start, end)
        const nextSelection = {
            start,
            end,
            text: content.slice(start, end),
            sentence: sentence.text,
            sentenceStart: sentence.start,
            sentenceEnd: sentence.end
        }

        setSelection(nextSelection)
        return nextSelection
    }, [value])

    const clearSelection = useCallback(() => {
        setSelection(EMPTY_SELECTION)
    }, [])

    return {
        selection,
        captureSelection,
        clearSelection,
        hasSelection: selection.end > selection.start
    }
}
