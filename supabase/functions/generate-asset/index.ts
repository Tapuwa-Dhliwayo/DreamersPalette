import { createClient } from "https://esm.sh/@supabase/supabase-js@2"
import { corsHeaders } from "../_shared/cors.ts"

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------
const PROMPT_MAX_LENGTH = 500
const DEFAULT_BUCKET    = "backgrounds"

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

function buildVisualPrompt(prompt: string, type: string): string {
  const base = prompt
      .replace(/[.,]/g, " ")
      .replace(/\s+/g, " ")
      .trim()

  if (type === "background") {
    return `${base}, atmospheric environment, cinematic lighting, soft gradients, painterly, no text`
  }

  return `${base}, epic fantasy book cover, cinematic lighting, painterly, elegant, no text`
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
    const HF_API_KEY         = Deno.env.get("HUGGINGFACE_API_KEY")

    if (!HF_API_KEY) throw new Error("Missing HuggingFace API key.")

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

    console.log("HF Prompt:", visualPrompt)

    // -----------------------------------------------------------------------
    // Hugging Face Image Generation
    // -----------------------------------------------------------------------
    const imageRes = await fetch(
        "https://router.huggingface.co/hf-inference/models/black-forest-labs/FLUX.1-schnell",
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${HF_API_KEY}`,
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            inputs: visualPrompt,
          }),
        }
    )

    console.log("HF status:", imageRes.status)

    if (!imageRes.ok) {
      const text = await imageRes.text()
      console.error("HF error:", text)
      throw new Error(`HuggingFace error: ${text}`)
    }

    const contentType = imageRes.headers.get("content-type") || "image/png"

    if (!contentType.startsWith("image/")) {
      const text = await imageRes.text()
      console.error("Invalid HF response:", text)
      throw new Error("Provider did not return an image.")
    }

    const extension = EXTENSION_BY_MIME[contentType] || "png"
    const fileName  = `${Date.now()}-${type}.${extension}`
    const filePath  = `${user.id}/${fileName}`

    // -----------------------------------------------------------------------
    // STREAM upload (NO WORKER LIMIT ISSUE)
    // -----------------------------------------------------------------------
    const { error: uploadError } = await supabaseAdmin.storage
        .from(bucket)
        .upload(filePath, imageRes.body, {
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