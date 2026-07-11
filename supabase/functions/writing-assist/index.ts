import { createClient } from "@supabase/supabase-js"
import { corsHeaders } from "../_shared/cors.ts"

const SENTENCE_MAX_LENGTH = 1000
const DEFAULT_ENDPOINT = "https://api.languagetoolplus.com/v2/check"
const DEFAULT_LANGUAGE = "en-ZA"

type LanguageToolReplacement = {
  value?: string
}

type LanguageToolMatch = {
  message?: string
  shortMessage?: string
  offset?: number
  length?: number
  replacements?: LanguageToolReplacement[]
  rule?: {
    id?: string
    issueType?: string
    category?: {
      name?: string
    }
  }
}

function jsonResponse(body: Record<string, unknown>, status: number): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: {
      ...corsHeaders,
      "Content-Type": "application/json"
    }
  })
}

function validateSentence(raw: unknown): string {
  const sentence = typeof raw === "string" ? raw.trim() : ""
  if (!sentence) throw new Error("Sentence is required.")
  if (sentence.length > SENTENCE_MAX_LENGTH) {
    throw new Error(`Sentence must be ${SENTENCE_MAX_LENGTH} characters or fewer.`)
  }
  return sentence
}

function normalizeMatches(matches: LanguageToolMatch[], sentence: string) {
  return matches.flatMap((match, matchIndex) => {
    const offset = Number(match.offset)
    const length = Number(match.length)
    if (!Number.isInteger(offset) || !Number.isInteger(length) || offset < 0 || length < 0) return []
    if (offset + length > sentence.length) return []

    const ruleId = match.rule?.id || "grammar"
    const replacements = (match.replacements || [])
      .map((replacement) => replacement.value?.trim())
      .filter((value): value is string => Boolean(value))
      .slice(0, 8)
      .map((value, replacementIndex) => ({
        id: `${ruleId}:${offset}:${replacementIndex}`,
        type: "grammar",
        label: value,
        replacement: value
      }))

    return [{
      id: `${ruleId}:${offset}:${matchIndex}`,
      type: "grammar",
      message: match.message || match.shortMessage || "Review this wording.",
      category: match.rule?.category?.name || match.rule?.issueType || "Grammar",
      ruleId,
      offset,
      length,
      original: sentence.slice(offset, offset + length),
      replacements
    }]
  })
}

Deno.serve(async (request: Request) => {
  if (request.method === "OPTIONS") {
    return new Response("ok", { headers: corsHeaders })
  }

  if (request.method !== "POST") {
    return jsonResponse({ error: "Method not allowed." }, 405)
  }

  try {
    const authHeader = request.headers.get("Authorization")
    if (!authHeader) return jsonResponse({ error: "Missing authorization header." }, 401)

    const supabaseUrl = Deno.env.get("SUPABASE_URL")
    const supabaseAnonKey = Deno.env.get("SUPABASE_ANON_KEY")
    if (!supabaseUrl || !supabaseAnonKey) {
      console.error("writing-assist: missing Supabase configuration")
      return jsonResponse({ error: "Writing assistance is not configured." }, 500)
    }

    const supabase = createClient(supabaseUrl, supabaseAnonKey, {
      global: { headers: { Authorization: authHeader } }
    })
    const { data: { user }, error: userError } = await supabase.auth.getUser()
    if (userError || !user) return jsonResponse({ error: "User not authenticated." }, 401)

    const body = await request.json()
    const sentence = validateSentence(body.sentence)
    const endpoint = Deno.env.get("LANGUAGETOOL_API_URL") || DEFAULT_ENDPOINT
    const language = Deno.env.get("LANGUAGETOOL_LANGUAGE") || DEFAULT_LANGUAGE
    const username = Deno.env.get("LANGUAGETOOL_USERNAME")
    const apiKey = Deno.env.get("LANGUAGETOOL_API_KEY")

    if (Boolean(username) !== Boolean(apiKey)) {
      console.error("writing-assist: LanguageTool username and API key must be configured together")
      return jsonResponse({ error: "Writing assistance is not configured." }, 500)
    }

    const form = new URLSearchParams({ text: sentence, language })
    if (username && apiKey) {
      form.set("username", username)
      form.set("apiKey", apiKey)
    }

    const providerResponse = await fetch(endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: form
    })

    if (!providerResponse.ok) {
      const providerMessage = await providerResponse.text()
      console.error("writing-assist: LanguageTool error", providerResponse.status, providerMessage)
      return jsonResponse({ error: "Grammar suggestions are unavailable right now." }, 502)
    }

    const providerData = await providerResponse.json()
    const matches = Array.isArray(providerData?.matches) ? providerData.matches : []

    return jsonResponse({
      provider: "languagetool",
      language: providerData?.language?.code || language,
      suggestions: normalizeMatches(matches, sentence)
    }, 200)
  } catch (error) {
    const message = error instanceof Error ? error.message : "Internal server error."
    const isValidationError = message === "Sentence is required." || message.includes("characters or fewer")
    console.error("writing-assist error:", error)
    return jsonResponse({ error: isValidationError ? message : "Grammar suggestions are unavailable right now." }, isValidationError ? 400 : 500)
  }
})
