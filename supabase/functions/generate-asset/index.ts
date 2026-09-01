import { createClient } from "https://esm.sh/@supabase/supabase-js@2"
import { InferenceClient } from "https://esm.sh/@huggingface/inference@4"
import { corsHeaders } from "../_shared/cors.ts"

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------
const PROMPT_MAX_LENGTH = 500
const DEFAULT_BUCKET    = "backgrounds"
const IMAGE_MODEL       = "black-forest-labs/FLUX.1-schnell"

const EXTENSION_BY_MIME: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png":  "png",
  "image/webp": "webp",
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function validatePrompt(raw: unknown): string {
  const prompt = typeof raw === "string" ? raw.trim() : ""
  if (!prompt) throw new Error("Prompt is required.")
  if (prompt.length > PROMPT_MAX_LENGTH)
    throw new Error(`Prompt must be ${PROMPT_MAX_LENGTH} characters or fewer.`)
  return prompt
}

function validateType(raw: unknown): string {
  if (raw !== "background" && raw !== "cover")
    throw new Error('Type must be "background" or "cover".')
  return raw as string
}

// Words that cause the model to render text or literary content in the image
const LITERARY_WORDS = new Set([
  "poem", "poems", "poetry", "verse", "verses", "stanza", "stanzas",
  "story", "stories", "novel", "chapter", "chapters", "book", "books",
  "writing", "written", "writes", "write", "wrote",
  "describing", "described", "describes", "describe", "description",
  "about", "titled", "called", "named",
  "words", "word", "text", "texts", "letter", "letters",
  "saying", "says", "said", "tells", "telling", "told",
  "reading", "read", "reads",
  "four", "three", "two", "five", "six", "seven", "eight", "nine", "ten",
  "collection", "collections", "anthology",
])

function sanitizeForVisual(raw: string): string {
  return raw
      .replace(/[.,;:!?'"()\[\]{}]/g, " ")
      .split(/\s+/)
      .filter((w) => !LITERARY_WORDS.has(w.toLowerCase()))
      .join(" ")
      .replace(/\s+/g, " ")
      .trim()
}

// FLUX.1-schnell is guidance-distilled, so a negative prompt has no effect.
// Everything that keeps text and clutter out of the image has to be stated here.
function buildVisualPrompt(prompt: string, type: string): string {
  const visual = sanitizeForVisual(prompt)

  // Fallback if sanitization strips everything
  const base = visual || "abstract atmospheric landscape"

  const textless = "completely textless, no text, no words, no letters, no typography, no watermark, no signature, no logo"

  if (type === "background") {
    return `beautiful painting of ${base}, abstract art, atmospheric scene, cinematic lighting, soft color gradients, painterly brushstrokes, dreamy, ethereal, uncluttered, no people, purely visual, ${textless}`
  }

  return `painting of ${base}, epic fantasy book cover art, cinematic lighting, painterly, elegant composition, sharp and detailed, purely visual, ${textless}`
}

// ---------------------------------------------------------------------------
// Handler
// ---------------------------------------------------------------------------
Deno.serve(async (req: Request) => {
  // CORS preflight
  if (req.method === "OPTIONS") {
    return new Response("ok", { headers: corsHeaders })
  }

  try {
    // ---- Auth ----
    const authHeader = req.headers.get("Authorization")
    if (!authHeader) throw new Error("Missing authorization header.")

    const supabaseUrl        = Deno.env.get("SUPABASE_URL")!
    const supabaseServiceKey = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!
    const supabaseAnon       = Deno.env.get("SUPABASE_ANON_KEY")!
    const bucket             = Deno.env.get("GENERATED_ASSETS_BUCKET") || DEFAULT_BUCKET
    const hfApiKey           = Deno.env.get("HUGGINGFACE_API_KEY")

    if (!hfApiKey) throw new Error("Missing HuggingFace API key.")

    // Verify user
    const supabaseUser = createClient(supabaseUrl, supabaseAnon, {
      global: { headers: { Authorization: authHeader } },
    })

    const { data: { user }, error: userError } = await supabaseUser.auth.getUser()
    if (userError || !user) throw new Error("User not authenticated.")

    // Admin client
    const supabaseAdmin = createClient(supabaseUrl, supabaseServiceKey)

    // ---- Parse request ----
    const body   = await req.json()
    const prompt = validatePrompt(body.prompt)
    const type   = validateType(body.type)

    const visualPrompt = buildVisualPrompt(prompt, type)

    console.log("HF prompt:", visualPrompt)

    // -----------------------------------------------------------------------
    // Hugging Face image generation
    //
    // FLUX is not served by the `hf-inference` backend. It is routed to partner
    // providers (nscale, fal-ai, wavespeed), each with its own request and
    // response shape, so the client resolves the provider and normalises the
    // result to a Blob for us.
    // -----------------------------------------------------------------------
    const hf = new InferenceClient(hfApiKey)

    let image: Blob
    try {
      image = await hf.textToImage({
        provider: "auto",
        model: IMAGE_MODEL,
        inputs: visualPrompt,
      }) as Blob
    } catch (hfError: any) {
      console.error("HuggingFace generation failed:", hfError)
      throw new Error(`HuggingFace error: ${hfError?.message || "image generation failed."}`)
    }

    if (!image || image.size === 0) throw new Error("Provider did not return an image.")

    const contentType = image.type && image.type.startsWith("image/") ? image.type : "image/png"
    const extension   = EXTENSION_BY_MIME[contentType] || "png"
    const fileName    = `${Date.now()}-${type}.${extension}`
    const filePath    = `${user.id}/${fileName}`

    console.log("Generated image:", contentType, image.size, "bytes")

    // ---- Upload ----
    const { error: uploadError } = await supabaseAdmin.storage
        .from(bucket)
        .upload(filePath, image, {
          contentType,
          upsert: false,
        })

    if (uploadError) throw uploadError

    const { data: urlData } = supabaseAdmin.storage
        .from(bucket)
        .getPublicUrl(filePath)

    if (!urlData?.publicUrl) {
      throw new Error("Could not resolve public URL.")
    }

    const publicUrl = urlData.publicUrl

    // -----------------------------------------------------------------------
    // Save DB record
    // -----------------------------------------------------------------------
    const { data: asset, error: dbError } = await supabaseAdmin
        .from("generated_assets")
        .insert([{
          author_id: user.id,
          type,
          prompt,
          image_url: publicUrl,
        }])
        .select("id, author_id, type, prompt, image_url, created_at")
        .single()

    if (dbError) throw dbError

    // -----------------------------------------------------------------------
    // Response
    // -----------------------------------------------------------------------
    return new Response(
        JSON.stringify({
          provider: "huggingface",
          imageUrl: publicUrl,
          asset
        }),
        {
          status: 200,
          headers: {
            ...corsHeaders,
            "Content-Type": "application/json"
          }
        }
    )

  } catch (err: any) {
    console.error("generate-asset error:", err)

    return new Response(
        JSON.stringify({
          error: err.message || "Internal server error"
        }),
        {
          status: 400,
          headers: {
            ...corsHeaders,
            "Content-Type": "application/json"
          }
        }
    )
  }
})
