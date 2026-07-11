import { supabase } from "./supabaseClient"

const DATAMUSE_ENDPOINT = "https://api.datamuse.com/words"
const DICTIONARY_ENDPOINT = "https://api.dictionaryapi.dev/api/v2/entries/en"
const requestCache = new Map()

function normalizeQuery(text) {
    return text.trim().replace(/\s+/g, " ").slice(0, 160)
}

function isSingleWord(query) {
    return /^[\p{L}\p{M}]+(?:['’-][\p{L}\p{M}]+)*$/u.test(query)
}

async function fetchJson(url, signal, allowNotFound = false) {
    const response = await fetch(url, { signal })
    if (allowNotFound && response.status === 404) return null
    if (!response.ok) throw new Error(`Writing assistance provider returned ${response.status}.`)
    return response.json()
}

function cachedRequest(key, request) {
    if (requestCache.has(key)) return requestCache.get(key)

    const promise = request().catch((error) => {
        requestCache.delete(key)
        throw error
    })
    requestCache.set(key, promise)
    return promise
}

async function datamuseLookup(parameter, query, max, signal) {
    const params = new URLSearchParams({ [parameter]: query, max: String(max) })
    return fetchJson(`${DATAMUSE_ENDPOINT}?${params}`, signal)
}

function toSuggestion(item, type) {
    return {
        id: `${type}:${item.word}`,
        type,
        label: item.word,
        replacement: item.word,
        score: item.score ?? null
    }
}

export function getSpellingSuggestions(text, { signal } = {}) {
    const query = normalizeQuery(text)
    if (!isSingleWord(query)) return Promise.resolve([])

    return cachedRequest(`spelling:${query.toLowerCase()}`, async () => {
        const results = await datamuseLookup("sp", query, 9, signal)
        return results
            .filter((item) => item.word.toLowerCase() !== query.toLowerCase())
            .slice(0, 8)
            .map((item) => toSuggestion(item, "spelling"))
    })
}

export function getThesaurusSuggestions(text, { signal } = {}) {
    const query = normalizeQuery(text)
    if (!query) return Promise.resolve({ synonyms: [], antonyms: [], related: [] })

    return cachedRequest(`thesaurus:${query.toLowerCase()}`, async () => {
        const word = isSingleWord(query)
        const lookups = word
            ? [
                datamuseLookup("rel_syn", query, 12, signal),
                datamuseLookup("rel_ant", query, 8, signal),
                datamuseLookup("ml", query, 12, signal)
            ]
            : [Promise.resolve([]), Promise.resolve([]), datamuseLookup("ml", query, 12, signal)]
        const [synonyms, antonyms, related] = await Promise.all(lookups)

        return {
            synonyms: synonyms.map((item) => toSuggestion(item, "synonym")),
            antonyms: antonyms.map((item) => toSuggestion(item, "antonym")),
            related: related
                .filter((item) => item.word.toLowerCase() !== query.toLowerCase())
                .map((item) => toSuggestion(item, "related"))
        }
    })
}

export function getDefinitions(text, { signal } = {}) {
    const query = normalizeQuery(text)
    if (!isSingleWord(query)) return Promise.resolve([])

    return cachedRequest(`definition:${query.toLowerCase()}`, async () => {
        const data = await fetchJson(
            `${DICTIONARY_ENDPOINT}/${encodeURIComponent(query)}`,
            signal,
            true
        )
        if (!Array.isArray(data)) return []

        return data.flatMap((entry) => (entry.meanings || []).flatMap((meaning) =>
            (meaning.definitions || []).slice(0, 3).map((definition, index) => ({
                id: `${meaning.partOfSpeech || "word"}:${index}:${definition.definition}`,
                partOfSpeech: meaning.partOfSpeech || "Word",
                definition: definition.definition,
                example: definition.example || null
            }))
        )).slice(0, 12)
    })
}

export function getGrammarSuggestions(sentence) {
    const query = typeof sentence === "string" ? sentence.trim().slice(0, 1000) : ""
    if (!query) return Promise.resolve([])

    return cachedRequest(`grammar:${query.toLowerCase()}`, async () => {
        const { data, error } = await supabase.functions.invoke("writing-assist", {
            body: { sentence: query }
        })

        if (error) {
            const message = typeof data?.error === "string"
                ? data.error
                : error.message || "Grammar review failed."
            throw new Error(message)
        }

        return Array.isArray(data?.suggestions) ? data.suggestions : []
    })
}
