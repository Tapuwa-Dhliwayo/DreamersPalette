import { formatDateTime } from "@/utils/formatDate";

function saveStatus({ error, saving, recoveredAt, isDirty, persistedAt }) {
    switch (true) {
        case Boolean(error): return ["Save failed", "text-red-700"];
        case saving: return ["Saving…", "text-neutral-600"];
        case Boolean(recoveredAt): return [`Recovered local draft from ${formatDateTime(recoveredAt)}`, "text-amber-800"];
        case isDirty && Boolean(persistedAt): return [`Unsaved · recovery copy stored ${formatDateTime(persistedAt)}`, "text-amber-800"];
        case isDirty: return ["Unsaved changes", "text-amber-800"];
        default: return ["All changes saved", "text-neutral-600"];
    }
}

export default function EditorSaveState(props) {
    const [label, tone] = saveStatus(props);
    return <span role="status" aria-live="polite" className={`text-xs ${tone}`}>{label}</span>;
}
