import { useRef, useState } from "react";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";
import StatusMessage from "@/components/ui/StatusMessage";
import Textarea from "@/components/ui/Textarea";
import { formatDate } from "@/utils/formatDate";
import { originLabel } from "@/utils/originLabel";

const MAX_IMPORT_BYTES = 1048576;

function ImportIcon({ kind = "upload" }) {
    const paths = { upload: "M12 16V4 M7 9l5-5 5 5 M4 20h16", check: "m5 12 5 5 9-10", close: "M6 6l12 12 M18 6 6 18" };
    return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true"><path d={paths[kind]} /></svg>;
}

function FailureList({ failure }) {
    const entries = Object.entries(failure.errors || {});
    return (
        <StatusMessage tone="error">
            <span className="block">{entries.length ? "This package cannot be imported. Hand these back to the chat client to correct:" : failure.message}</span>
            {!!entries.length && <ul className="mt-2 space-y-1 break-words font-mono text-xs">{entries.map(([path, messages]) => <li key={path}><strong>{path}</strong>: {[messages].flat().join(" ")}</li>)}</ul>}
        </StatusMessage>
    );
}

function ImportPreview({ preview, onOpen }) {
    const info = preview.preview;
    const origin = originLabel(preview.external_origin);
    return (
        <div className="space-y-4 text-sm">
            <h3 className="break-words font-serif text-lg">{preview.title}</h3>
            {preview.saved && <StatusMessage tone="info" action={<Button size="sm" variant="subtle" onClick={() => onOpen(preview.conversation_id)}>Open imported analysis</Button>}>Already imported on {formatDate(preview.saved_at)}.</StatusMessage>}
            <dl className="space-y-3">
                <div><dt className="text-xs text-neutral-600">Poem</dt><dd className="break-words font-medium">{info.poem_title}</dd></div>
                <div className={`border-l-2 pl-3 ${info.newer_version ? "border-amber-500" : "border-teal-700"}`}>
                    <dt className="text-xs text-neutral-600">Analysis attaches to</dt>
                    <dd className="font-semibold">{info.newer_version ? "An earlier saved version (the poem has changed since)" : "The current saved poem"}</dd>
                </div>
                <div><dt className="text-xs text-neutral-600">Saved to</dt><dd className="break-words">{info.creates_conversation ? `New conversation: ${info.conversation_title}` : info.conversation_title}</dd></div>
                {!!info.comparisons.length && <div><dt className="text-xs text-neutral-600">Comparison poems</dt><dd><ul>{info.comparisons.map((comparison) => <li key={comparison.poem} className="break-words">{comparison.title}{comparison.snapshot === "retained_previous" ? " (earlier version)" : ""}</li>)}</ul></dd></div>}
                {!!info.author_notes.length && <div><dt className="text-xs text-neutral-600">Author notes referenced</dt><dd><ul className="space-y-1">{info.author_notes.map((note) => <li key={note.id} className="whitespace-pre-wrap break-words border-l-2 border-neutral-300 pl-3">{note.content}</li>)}</ul></dd></div>}
                {info.suggested_notes > 0 && <div><dt className="text-xs text-neutral-600">Suggested notes</dt><dd>{info.suggested_notes}, kept as proposals until you save them as author notes.</dd></div>}
                {origin && <div><dt className="text-xs text-neutral-600">Origin, as reported by the package</dt><dd className="break-words">{origin}</dd></div>}
            </dl>
        </div>
    );
}

export default function ImportAnalysisDialog({ open, onClose, preview, failure, busy, onPreview, onSave, onOpen }) {
    const [markdown, setMarkdown] = useState("");
    const [fileName, setFileName] = useState("");
    const [fileError, setFileError] = useState("");
    const fileInput = useRef(null);

    async function chooseFile(event) {
        const file = event.target.files?.[0];
        setFileError("");
        if (!file) return;
        if (file.size > MAX_IMPORT_BYTES) {
            setFileError(`${file.name} is larger than 1 MiB.`);
            setFileName("");
            event.target.value = "";
            return;
        }
        setFileName(file.name);
        setMarkdown(await file.text());
    }

    function close() {
        setMarkdown("");
        setFileName("");
        setFileError("");
        onClose();
    }

    const tooLarge = new Blob([markdown]).size > MAX_IMPORT_BYTES;

    return (
        <Modal open={open} onClose={busy ? undefined : close} className="md:max-w-2xl">
            <div role="dialog" aria-modal="true" aria-labelledby="import-analysis-title" className="space-y-4">
                <h2 id="import-analysis-title" className="text-xl font-semibold">Import analysis</h2>
                {failure && <FailureList failure={failure} />}
                {preview ? <ImportPreview preview={preview} onOpen={(conversation) => { close(); onOpen(conversation); }} /> : (
                    <div className="space-y-3">
                        <div className="flex flex-wrap items-center gap-3">
                            <input ref={fileInput} className="sr-only" type="file" accept=".md,text/markdown,text/plain" aria-label="Choose a Markdown file" onChange={chooseFile} />
                            <Button className="gap-2" variant="subtle" type="button" onClick={() => fileInput.current?.click()}><ImportIcon />Choose .md file</Button>
                            {fileName && <span className="break-all text-sm text-neutral-600">{fileName}</span>}
                        </div>
                        {fileError && <p className="text-sm text-red-700" role="alert">{fileError}</p>}
                        <label className="block text-sm font-medium">Or paste the Markdown
                            <Textarea className="mt-2 min-h-48 font-mono text-xs" value={markdown} onChange={(event) => { setMarkdown(event.target.value); setFileName(""); }} />
                        </label>
                        {tooLarge && <p className="text-sm text-red-700" role="alert">The package is larger than 1 MiB.</p>}
                    </div>
                )}
                <div className="flex flex-wrap justify-end gap-2 pt-2">
                    <Button className="gap-2" variant="ghost" disabled={busy} onClick={close}><ImportIcon kind="close" />{preview && !preview.saved ? "Cancel" : "Close"}</Button>
                    {preview && !preview.saved && <Button className="gap-2" disabled={busy} onClick={onSave}><ImportIcon kind="check" />Save import</Button>}
                    {!preview && <Button className="gap-2" disabled={busy || !markdown.trim() || tooLarge} onClick={() => onPreview(markdown)}><ImportIcon />Preview import</Button>}
                </div>
            </div>
        </Modal>
    );
}
