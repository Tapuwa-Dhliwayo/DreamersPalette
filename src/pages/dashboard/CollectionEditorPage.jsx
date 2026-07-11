import { useEffect, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"
import Button from "@/components/ui/Button"
import Input from "@/components/ui/Input"
import Textarea from "@/components/ui/Textarea"
import FormField from "@/components/ui/FormField"
import StatusMessage from "@/components/ui/StatusMessage"
import ConfirmDialog from "@/components/ui/ConfirmDialog"
import CollectionThemePreview from "@/components/dashboard/CollectionThemePreview"
import {
    createCollection,
    getMyCollectionById,
    updateCollection
} from "@/services/contentService"
import {
    compressImageIfNeeded,
    uploadCollectionBackgroundImage,
    validateImageFile
} from "@/services/storageService"
import { generateCollectionBackground, getAiProviderLabel } from "@/services/aiAssetService"
import { supabase } from "@/services/supabaseClient"
import { useUnsavedChanges } from "@/hooks/useUnsavedChanges"
import { useLocalDraft } from "@/hooks/useLocalDraft"
import EditorSaveState from "@/components/dashboard/EditorSaveState"
import { DASHBOARD_ROUTES } from "@/app/routes"
import { compositeColors, contrastRatio, parseColorToHex } from "@/utils/color"

const TEXT_COLOR_FIELDS = [
    {
        field: "theme_text_color",
        id: "collection-poem-text",
        label: "Poem text",
        hint: "The main reading colour for poems and chapters."
    },
    {
        field: "theme_heading_color",
        id: "collection-heading-text",
        label: "Titles & headings",
        hint: "Collection titles, poem titles, and section headings."
    },
    {
        field: "theme_muted_color",
        id: "collection-muted-text",
        label: "Captions & supporting text",
        hint: "Descriptions, metadata, and quieter reader text."
    }
]

function getThemeDefaults(textMode) {
    return textMode === "light"
        ? { text: "#f5f5f5", muted: "#a3a3a3" }
        : { text: "#171717", muted: "#737373" }
}

function getEstimatedBackground(form) {
    if (!form.theme_background_url) return "#262626"
    const overlay = form.theme_text_mode === "light" ? "#000000" : "#ffffff"
    return compositeColors(overlay, "#808080", form.theme_overlay_opacity)
}

function TextColorControl({ config, value, defaultColor, backgroundColor, error, onChange, onError }) {
    const [inputValue, setInputValue] = useState(value || defaultColor)
    const resolvedColor = value || defaultColor
    const ratio = contrastRatio(resolvedColor, backgroundColor)
    const hasLowContrast = ratio !== null && ratio < 4.5

    function handleTextChange(nextValue) {
        setInputValue(nextValue)
        const normalized = parseColorToHex(nextValue)
        if (!normalized) {
            onError("Enter a HEX, RGB, or HSL colour.")
            return
        }
        onError("")
        onChange(normalized)
    }

    return (
        <FormField label={config.label} htmlFor={config.id} hint={config.hint} error={error}>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                    id={`${config.id}-picker`}
                    type="color"
                    value={resolvedColor}
                    aria-label={`${config.label} colour picker`}
                    onChange={(event) => {
                        setInputValue(event.target.value)
                        onError("")
                        onChange(event.target.value)
                    }}
                    className="h-11 w-14 shrink-0 cursor-pointer rounded-lg border border-neutral-300 bg-transparent"
                />
                <Input
                    id={config.id}
                    value={inputValue}
                    aria-invalid={Boolean(error)}
                    aria-describedby={`${config.id}-description`}
                    placeholder="#f5f5f5, rgb(245, 245, 245), or hsl(0, 0%, 96%)"
                    onBlur={() => {
                        if (!error) setInputValue(resolvedColor)
                    }}
                    onChange={(event) => handleTextChange(event.target.value)}
                />
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    disabled={value == null}
                    onClick={() => {
                        setInputValue(defaultColor)
                        onError("")
                        onChange(null)
                    }}
                >
                    Reset
                </Button>
            </div>
            {hasLowContrast && !error && (
                <p className="text-xs leading-relaxed text-amber-800" role="status">
                    {ratio.toFixed(1)}:1 contrast. This colour may be hard to read against the background.
                </p>
            )}
        </FormField>
    )
}

const EMPTY_FORM = {
    title: "",
    description: "",
    theme_background_url: "",
    theme_overlay_opacity: 0.6,
    accent_color: "#d4d4d8",
    theme_text_mode: "light",
    theme_text_color: null,
    theme_heading_color: null,
    theme_muted_color: null
}

export default function CollectionEditorPage() {
    const navigate = useNavigate()
    const { id } = useParams()
    const isEditMode = Boolean(id)
    const providerLabel = getAiProviderLabel()
    const [form, setForm] = useState(EMPTY_FORM)
    const [initialForm, setInitialForm] = useState(null)
    const [backgroundPrompt, setBackgroundPrompt] = useState("")
    const [loading, setLoading] = useState(isEditMode)
    const [saving, setSaving] = useState(false)
    const [uploading, setUploading] = useState(false)
    const [generating, setGenerating] = useState(false)
    const [error, setError] = useState("")
    const [uploadInfo, setUploadInfo] = useState("")
    const [fieldErrors, setFieldErrors] = useState({})

    const localDraft = useLocalDraft({
        key: `dreamers-palette:collection:${id || "new"}`,
        form,
        initialForm,
        setForm
    })
    const navigationGuard = useUnsavedChanges(localDraft.isDirty && !saving)

    useEffect(() => {
        if (!isEditMode) {
            setInitialForm(EMPTY_FORM)
            setLoading(false)
            return
        }

        async function loadCollection() {
            try {
                const collection = await getMyCollectionById(id)
                const nextForm = {
                    title: collection.title || "",
                    description: collection.description || "",
                    theme_background_url: collection.theme_background_url || "",
                    theme_overlay_opacity: collection.theme_overlay_opacity ?? 0.6,
                    accent_color: collection.accent_color || "#d4d4d8",
                    theme_text_mode: collection.theme_text_mode || "light",
                    theme_text_color: collection.theme_text_color || null,
                    theme_heading_color: collection.theme_heading_color || null,
                    theme_muted_color: collection.theme_muted_color || null
                }
                setForm(nextForm)
                setInitialForm(nextForm)
            } catch (err) {
                setError(err.message || "The collection could not be loaded.")
            } finally {
                setLoading(false)
            }
        }

        loadCollection()
    }, [id, isEditMode])

    function updateField(field, value) {
        setFieldErrors((current) => ({ ...current, [field]: "" }))
        setForm((current) => ({ ...current, [field]: value }))
    }

    function validate() {
        const nextErrors = {}
        if (!form.title.trim()) nextErrors.title = "Enter a collection title."
        if (form.title.trim().length > 120) nextErrors.title = "Keep the title under 120 characters."
        if (form.description.length > 1000) nextErrors.description = "Keep the description under 1,000 characters."
        TEXT_COLOR_FIELDS.forEach(({ field }) => {
            if (fieldErrors[field]) nextErrors[field] = fieldErrors[field]
        })
        setFieldErrors(nextErrors)
        return Object.keys(nextErrors).length === 0
    }

    async function handleSave() {
        if (!validate()) return

        try {
            setSaving(true)
            setError("")
            const payload = {
                ...form,
                title: form.title.trim(),
                description: form.description.trim(),
                theme_background_url: form.theme_background_url || null
            }
            if (isEditMode) {
                await updateCollection(id, payload)
            } else {
                await createCollection({ ...payload, is_published: false })
            }
            localDraft.clearDraft()
            navigationGuard.markSafe()
            navigate(DASHBOARD_ROUTES.COLLECTIONS, {
                replace: true,
                state: { message: `Collection ${isEditMode ? "saved" : "created"} successfully.` }
            })
        } catch (err) {
            setError(err.message || "The collection could not be saved. Your changes are still here.")
        } finally {
            setSaving(false)
        }
    }

    async function handleBackgroundUpload(event) {
        const file = event.target.files?.[0]
        if (!file) return

        try {
            setUploading(true)
            setError("")
            setUploadInfo("")
            await validateImageFile(file)
            const { file: processedFile, wasCompressed } = await compressImageIfNeeded(file)
            const { data: { user } } = await supabase.auth.getUser()
            if (!user) throw new Error("Your session has expired. Sign in again.")
            const { fullUrl } = await uploadCollectionBackgroundImage(processedFile, user.id)
            updateField("theme_background_url", fullUrl)
            setUploadInfo(wasCompressed ? "Image optimized and uploaded." : "Image uploaded.")
        } catch (err) {
            setError(err.message || "The background image could not be uploaded.")
        } finally {
            setUploading(false)
            event.target.value = ""
        }
    }

    async function handleGenerateBackground() {
        if (!backgroundPrompt.trim()) return
        try {
            setGenerating(true)
            setError("")
            const { imageUrl } = await generateCollectionBackground(backgroundPrompt)
            updateField("theme_background_url", imageUrl)
        } catch (err) {
            setError(err.message || "The background could not be generated. Try a more specific prompt.")
        } finally {
            setGenerating(false)
        }
    }

    if (loading) {
        return <div className="h-72 animate-pulse rounded-2xl bg-neutral-200/70" aria-label="Loading collection editor" />
    }

    return (
        <div className="space-y-6">
            <header className="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="m-0 text-sm text-neutral-600">Collections</p>
                    <h2 className="mt-1 text-2xl font-semibold tracking-tight">
                        {isEditMode ? "Edit collection" : "New collection"}
                    </h2>
                    <p className="mt-1 text-sm text-neutral-600">
                        Build the atmosphere while keeping the reader experience visible.
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <EditorSaveState
                        saving={saving}
                        isDirty={localDraft.isDirty}
                        persistedAt={localDraft.persistedAt}
                        recoveredAt={localDraft.recoveredAt}
                        error={error}
                    />
                    <Button variant="ghost" onClick={() => navigate(DASHBOARD_ROUTES.COLLECTIONS)}>
                        Cancel
                    </Button>
                    <Button onClick={handleSave} disabled={saving || uploading || generating}>
                        {saving ? "Saving…" : "Save collection"}
                    </Button>
                </div>
            </header>

            <StatusMessage tone="error">{error}</StatusMessage>
            <StatusMessage tone="success">{uploadInfo}</StatusMessage>

            <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(340px,420px)] lg:items-start">
                <div className="min-w-0 space-y-8">
                    <section className="space-y-5">
                        <div>
                            <h3 className="text-lg font-medium">Collection details</h3>
                            <p className="mt-1 text-sm text-neutral-600">
                                These details introduce the collection before a reader opens a poem.
                            </p>
                        </div>
                        <FormField label="Title" htmlFor="collection-title" error={fieldErrors.title} required>
                            <Input
                                id="collection-title"
                                value={form.title}
                                maxLength={120}
                                aria-invalid={Boolean(fieldErrors.title)}
                                aria-describedby="collection-title-description"
                                onChange={(event) => updateField("title", event.target.value)}
                            />
                        </FormField>
                        <FormField
                            label="Description"
                            htmlFor="collection-description"
                            hint={`${form.description.length}/1000 characters`}
                            error={fieldErrors.description}
                        >
                            <Textarea
                                id="collection-description"
                                value={form.description}
                                maxLength={1000}
                                rows={5}
                                aria-invalid={Boolean(fieldErrors.description)}
                                aria-describedby="collection-description-description"
                                onChange={(event) => updateField("description", event.target.value)}
                            />
                        </FormField>
                    </section>

                    <section className="space-y-5 border-t border-neutral-200 pt-8">
                        <div>
                            <h3 className="text-lg font-medium">Background atmosphere</h3>
                            <p className="mt-1 text-sm text-neutral-600">
                                Upload an image or generate one. The overlay keeps the writing legible.
                            </p>
                        </div>
                        <FormField
                            label="Upload background"
                            htmlFor="collection-background-file"
                            hint="JPEG, PNG, WebP, or GIF. Maximum 5 MB."
                        >
                            <input
                                id="collection-background-file"
                                type="file"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                disabled={uploading}
                                onChange={handleBackgroundUpload}
                                className="block min-h-11 w-full text-sm text-neutral-700 file:mr-4 file:min-h-11 file:rounded-xl file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:font-medium file:text-neutral-900 hover:file:bg-neutral-200"
                            />
                        </FormField>
                        <FormField
                            label="Generate with AI"
                            htmlFor="collection-background-prompt"
                            hint={`Describe mood, setting, light, and texture. Provider: ${providerLabel}.`}
                        >
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Input
                                    id="collection-background-prompt"
                                    value={backgroundPrompt}
                                    maxLength={500}
                                    placeholder="Moonlit garden after rain, soft focus, no text"
                                    onChange={(event) => setBackgroundPrompt(event.target.value)}
                                />
                                <Button
                                    type="button"
                                    variant="subtle"
                                    disabled={generating || !backgroundPrompt.trim()}
                                    onClick={handleGenerateBackground}
                                >
                                    {generating ? "Generating…" : "Generate"}
                                </Button>
                            </div>
                        </FormField>
                        {form.theme_background_url && (
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-sm text-neutral-700">Background ready</span>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => updateField("theme_background_url", "")}
                                >
                                    Remove
                                </Button>
                            </div>
                        )}
                    </section>

                    <section className="space-y-5 border-t border-neutral-200 pt-8">
                        <div>
                            <h3 className="text-lg font-medium">Colours & contrast</h3>
                            <p className="mt-1 text-sm text-neutral-600">
                                Tune the treatment against the preview. Readability takes priority over atmosphere.
                            </p>
                        </div>
                        <FormField label="Overlay strength" htmlFor="collection-overlay" hint={`${Math.round(form.theme_overlay_opacity * 100)}%`}>
                            <input
                                id="collection-overlay"
                                type="range"
                                min="0.4"
                                max="0.75"
                                step="0.05"
                                value={form.theme_overlay_opacity}
                                onChange={(event) => updateField("theme_overlay_opacity", Number(event.target.value))}
                                className="min-h-11 w-full accent-neutral-900"
                            />
                        </FormField>
                        <fieldset className="space-y-2">
                            <legend className="text-sm font-medium text-neutral-700">Overlay text mode</legend>
                            <div className="flex rounded-xl bg-neutral-100 p-1">
                                {[
                                    { value: "light", label: "Light text" },
                                    { value: "dark", label: "Dark text" }
                                ].map((option) => (
                                    <button
                                        key={option.value}
                                        type="button"
                                        aria-pressed={form.theme_text_mode === option.value}
                                        onClick={() => updateField("theme_text_mode", option.value)}
                                        className={`min-h-11 flex-1 rounded-lg px-3 text-sm font-medium transition-colors ${
                                            form.theme_text_mode === option.value
                                                ? "bg-white text-neutral-950 shadow-sm"
                                                : "text-neutral-600 hover:text-neutral-900"
                                        }`}
                                    >
                                        {option.label}
                                    </button>
                                ))}
                            </div>
                        </fieldset>
                        <div className="space-y-4 border-t border-neutral-200 pt-5">
                            <div>
                                <h4 className="text-sm font-medium text-neutral-900">Text colours</h4>
                                <p className="mt-1 text-xs leading-relaxed text-neutral-600">
                                    Use HEX, RGB, or HSL. Reset follows the selected overlay text mode.
                                </p>
                            </div>
                            {TEXT_COLOR_FIELDS.map((config) => {
                                const defaults = getThemeDefaults(form.theme_text_mode)
                                const defaultColor = config.field === "theme_muted_color" ? defaults.muted : defaults.text
                                return (
                                    <TextColorControl
                                        key={`${config.field}-${form[config.field] || defaultColor}`}
                                        config={config}
                                        value={form[config.field]}
                                        defaultColor={defaultColor}
                                        backgroundColor={getEstimatedBackground(form)}
                                        error={fieldErrors[config.field]}
                                        onChange={(value) => updateField(config.field, value)}
                                        onError={(message) => setFieldErrors((current) => ({ ...current, [config.field]: message }))}
                                    />
                                )
                            })}
                        </div>
                        <FormField label="Accent color" htmlFor="collection-accent" hint="Used for small links and underlines in the reader.">
                            <div className="flex items-center gap-3">
                                <input
                                    id="collection-accent"
                                    type="color"
                                    value={form.accent_color}
                                    onChange={(event) => updateField("accent_color", event.target.value)}
                                    className="h-11 w-14 cursor-pointer rounded-lg border border-neutral-300 bg-transparent"
                                />
                                <code className="text-sm text-neutral-700">{form.accent_color}</code>
                            </div>
                        </FormField>
                    </section>
                </div>

                <aside className="lg:sticky lg:top-6">
                    <CollectionThemePreview collection={form} />
                </aside>
            </div>

            <div className="sticky bottom-0 -mx-4 flex items-center justify-end gap-3 border-t border-neutral-200 bg-neutral-50/95 px-4 py-4 backdrop-blur-sm md:hidden">
                <Button variant="ghost" onClick={() => navigate(DASHBOARD_ROUTES.COLLECTIONS)}>Cancel</Button>
                <Button onClick={handleSave} disabled={saving || uploading || generating}>
                    {saving ? "Saving…" : "Save"}
                </Button>
            </div>

            <ConfirmDialog
                open={navigationGuard.blocked}
                title="Discard unsaved changes?"
                description="Your collection changes have not been saved. Leaving now will discard them."
                confirmLabel="Discard changes"
                onClose={navigationGuard.reset}
                onConfirm={navigationGuard.proceed}
            />
        </div>
    )
}
