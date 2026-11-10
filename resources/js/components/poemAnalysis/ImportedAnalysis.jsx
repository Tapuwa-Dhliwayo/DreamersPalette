import Button from "@/components/ui/Button";
import { formatDate } from "@/utils/formatDate";
import { originLabel } from "@/utils/originLabel";

export function ImportedAnalysisHeader({ record, snapshot, isNewerVersion, onViewSource }) {
    const origin = originLabel(record?.external_origin);
    const label = ["Imported analysis", record?.title, origin, formatDate(record?.saved_at)].filter(Boolean).join(" · ");
    return (
        <div className="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-neutral-600">
            <strong className="break-words">{label}</strong>
            {snapshot && <button className="min-h-8 underline" onClick={() => onViewSource(snapshot.id)}>View source version {snapshot.id}</button>}
            {isNewerVersion && <span className="text-amber-800">A newer version of the poem exists.</span>}
        </div>
    );
}

export function SuggestedNotes({ notes, savedContents, disabled, onSave }) {
    if (!notes?.length) return null;
    return (
        <div className="mt-3 space-y-2 border-l-2 border-teal-700 pl-3 text-sm">
            <h4 className="text-xs font-semibold text-neutral-600">Suggested author notes</h4>
            {notes.map((content, index) => (
                <div key={index} className="flex flex-wrap items-start justify-between gap-2">
                    <p className="min-w-0 flex-1 whitespace-pre-wrap break-words">{content}</p>
                    {savedContents.includes(content)
                        ? <span className="text-xs text-neutral-600">Saved as author note</span>
                        : <Button size="sm" variant="subtle" disabled={disabled} onClick={() => onSave(content)}>Save as author note</Button>}
                </div>
            ))}
        </div>
    );
}
