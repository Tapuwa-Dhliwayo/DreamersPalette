import Button from "@/components/ui/Button";
import StatusMessage from "@/components/ui/StatusMessage";

function DraftText({ title, form }) {
    const writing = form?.content_md ?? form?.description ?? form?.synopsis ?? "";
    return <section className="min-w-0"><h4 className="mb-2 text-sm font-semibold">{title}</h4><p className="mb-2 break-words font-medium">{form?.title}</p><pre className="max-h-64 overflow-y-auto whitespace-pre-wrap break-words font-serif text-sm">{writing || "No writing."}</pre></section>;
}

export default function DraftRecovery({ draft, initialForm }) {
    return <>
        <StatusMessage>{draft.storageWarning}</StatusMessage>
        {draft.recovery && <section className="space-y-3 border-l-2 border-amber-600 bg-amber-50 p-4" aria-label="Browser draft recovery">
            <p className="text-sm">{draft.recovery.outdated ? "A recovery copy was based on an older saved version. Compare it before using it." : "An unsaved recovery copy is available."} Using it replaces the form; saved writing changes only when you save.</p>
            <details><summary className="cursor-pointer text-sm font-medium">Compare writing</summary><div className="mt-3 grid gap-4 md:grid-cols-2"><DraftText title="Latest saved writing" form={initialForm} /><DraftText title="Recovery copy" form={draft.recovery.form} /></div></details>
            <div className="flex flex-wrap gap-2"><Button type="button" size="sm" variant="subtle" onClick={draft.restoreDraft}>Use recovery copy</Button><Button type="button" size="sm" variant="ghost" onClick={draft.clearDraft}>Keep saved version</Button></div>
        </section>}
    </>;
}
