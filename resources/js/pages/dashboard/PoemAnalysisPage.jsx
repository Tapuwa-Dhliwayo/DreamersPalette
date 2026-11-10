import { useEffect, useRef, useState } from "react";
import { Link, useParams, useSearchParams } from "react-router-dom";
import ReactMarkdown from "react-markdown";
import Button from "@/components/ui/Button";
import Select from "@/components/ui/Select";
import StatusMessage from "@/components/ui/StatusMessage";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import ImportAnalysisDialog from "@/components/poemAnalysis/ImportAnalysisDialog";
import { ImportedAnalysisHeader, SuggestedNotes } from "@/components/poemAnalysis/ImportedAnalysis";
import { usePoemAnalysis } from "@/hooks/usePoemAnalysis";
import { DASHBOARD_ROUTES } from "@/app/routes";
import { formatDate } from "@/utils/formatDate";

function ActionIcon({ kind = "chat" }) {
    const paths = { chat: "M4 4h16v12H9l-5 4z", send: "m3 3 18 9-18 9 4-9z M7 12h14", plus: "M12 5v14 M5 12h14", write: "m4 16 12-12 4 4-12 12H4z", archive: "M3 4h18v4H3z M5 8v12h14V8 M10 12h4", import: "M12 4v12 M7 11l5 5 5-5 M4 20h16" };
    return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true"><path d={paths[kind]} /></svg>;
}

export default function PoemAnalysisPage() {
    const { id } = useParams();
    const [params] = useSearchParams();
    return <AnalysisWorkspace key={`${id}:${params.get("conversation") || ""}`} id={id} />;
}

function AnalysisWorkspace({ id }) {
    const state = usePoemAnalysis(id);
    const [pane, setPane] = useState("conversation");
    const [viewSnapshotId, setViewSnapshotId] = useState("");
    const [note, setNote] = useState("");
    const [editingNoteId, setEditingNoteId] = useState(null);
    const [archiveOpen, setArchiveOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const messagesEnd = useRef(null);
    const followLatest = useRef(true);
    const { workspace, conversation, busy, error } = state;
    const turns = conversation?.turns || [];
    const active = turns.find((turn) => ["queued", "running"].includes(turn.status));
    const latest = turns.at(-1);
    const snapshot = conversation?.snapshots.find((snapshot) => snapshot.id === latest?.request_context.snapshot_id);
    const changed = snapshot && snapshot.fingerprint !== workspace?.fingerprint;
    const viewed = conversation?.snapshots.find((snapshot) => String(snapshot.id) === viewSnapshotId);
    const shownPoem = viewed || workspace?.poem;
    const selectedModel = state.catalogue?.models.find((model) => model.id === state.model);
    const canSend = !busy && !active && state.connectionId && state.model && state.catalogue;

    useEffect(() => { if (followLatest.current) messagesEnd.current?.scrollIntoView({ block: "nearest" }); }, [conversation?.messages, pane]);

    if (!workspace) return <div role="status">{error || "Loading poem analysis…"}</div>;

    // Below lg the whole workspace is the one scroller (the dashboard surface clips overflow); on lg the thread scrolls in place.
    const trackFollowLatest = (event) => { const panel = event.currentTarget; followLatest.current = panel.scrollHeight - panel.scrollTop - panel.clientHeight < 160; };

    return (
        <div className="poem-analysis flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto lg:overflow-visible" onScroll={trackFollowLatest}>
            <header className="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-3">
                <div className="min-w-0"><h2 className="break-words text-xl font-semibold">{workspace.poem.title}</h2><span className="text-sm text-neutral-600">{workspace.collection?.title}</span></div>
                <nav className="flex items-center gap-3" aria-label="Poem workspace">
                    <Link className="inline-flex min-h-11 items-center gap-2 text-sm underline" to={DASHBOARD_ROUTES.POEM_EDIT(id)}><ActionIcon kind="write" />Write</Link>
                    <span className="inline-flex items-center gap-2 border-b-2 border-teal-700 py-3 text-sm font-semibold" aria-current="page"><ActionIcon />Analysis</span>
                </nav>
            </header>
            <StatusMessage tone="error" action={<Button size="sm" variant="ghost" onClick={state.reload}>Reload saved poem</Button>}>{error}</StatusMessage>
            <div className="flex gap-2 lg:hidden" role="group" aria-label="Workspace pane">
                <Button variant={pane === "poem" ? "primary" : "subtle"} onClick={() => setPane("poem")}>Poem</Button>
                <Button variant={pane === "conversation" ? "primary" : "subtle"} onClick={() => setPane("conversation")}>Conversation</Button>
            </div>
            <div className="grid gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(16rem,0.8fr)_minmax(0,1.4fr)]">
                <aside className={`${pane === "poem" ? "flex" : "hidden"} flex-col border-neutral-200 pr-1 lg:flex lg:min-h-0 lg:overflow-y-auto lg:border-r lg:pr-5`} aria-label="Poem and context">
                    <label className="text-sm font-medium">Reading version
                        <Select className="mt-2" value={viewSnapshotId} onChange={(event) => setViewSnapshotId(event.target.value)}>
                            <option value="">Latest saved poem</option>
                            {conversation?.snapshots.map((snapshot) => <option key={snapshot.id} value={snapshot.id}>Version {snapshot.id} · {formatDate(snapshot.created_at)}</option>)}
                        </Select>
                    </label>
                    <h3 className="mb-4 mt-6 font-serif text-lg">{shownPoem?.title}</h3>
                    <div className="poetry-content whitespace-pre-wrap break-words font-serif leading-relaxed">{shownPoem?.content_md}</div>
                    <details className="mt-6 border-t border-neutral-200 py-4">
                        <summary className="cursor-pointer text-sm font-semibold">Context and author notes</summary>
                        <div className="mt-3 space-y-4 text-sm">
                            <p className="whitespace-pre-wrap break-words">{viewed ? viewed.collection_description : workspace.collection?.description}</p>
                            <fieldset disabled={busy || Boolean(active)} className="space-y-2">
                                <legend className="mb-2 font-medium">Comparison poems ({state.comparisonIds.length}/4)</legend>
                                <div className="max-h-48 space-y-2 overflow-y-auto">
                                    {workspace.comparison_poems.map((poem) => <label key={poem.id} className="flex min-h-9 items-center gap-2"><input type="checkbox" checked={state.comparisonIds.includes(poem.id)} disabled={!state.comparisonIds.includes(poem.id) && state.comparisonIds.length >= 4} onChange={(event) => state.setComparisonIds(event.target.checked ? [...state.comparisonIds, poem.id] : state.comparisonIds.filter((id) => id !== poem.id))} />{poem.title}</label>)}
                                </div>
                            </fieldset>
                            <div className="space-y-3">
                                <h4 className="font-semibold">Author notes</h4>
                                {workspace.notes.map((note) => <div key={note.id} className="border-l-2 border-teal-700 pl-3"><p className="whitespace-pre-wrap break-words">{note.content}</p><div className="mt-2 flex gap-3"><button className="min-h-9 underline" disabled={busy} onClick={() => { setEditingNoteId(note.id); setNote(note.content); }}>Edit</button><button className="min-h-9 underline" disabled={busy} onClick={() => state.retireNote(note.id)}>Retire</button></div></div>)}
                                {!!workspace.retired_notes?.length && <details><summary className="cursor-pointer">Retired author notes</summary>{workspace.retired_notes.map((note) => <div key={note.id} className="mt-2 border-l-2 border-neutral-300 pl-3"><p className="whitespace-pre-wrap">{note.content}</p><Button size="sm" variant="ghost" disabled={busy} onClick={() => state.restoreNote(note.id)}>Restore note</Button></div>)}</details>}
                                <form onSubmit={(event) => { event.preventDefault(); state.saveNote({ content: note, note_id: editingNoteId }).then((saved) => { if (saved) { setNote(""); setEditingNoteId(null); } }); }}>
                                    <label className="block">{editingNoteId ? "Edit author note" : "Add author note"}<textarea className="mt-2 min-h-24 w-full rounded-lg border border-neutral-300 bg-white p-3" value={note} maxLength={4000} onChange={(event) => setNote(event.target.value)} /></label>
                                    <Button className="mt-2 gap-2" size="sm" disabled={busy || !note.trim()} type="submit"><ActionIcon kind="plus" />Save note</Button>
                                    {editingNoteId && <Button size="sm" variant="ghost" onClick={() => { setEditingNoteId(null); setNote(""); }}>Cancel</Button>}
                                </form>
                            </div>
                        </div>
                    </details>
                </aside>
                <section className={`${pane === "conversation" ? "flex" : "hidden"} flex-col gap-3 lg:flex lg:min-h-0`} aria-label="Analysis conversation">
                    <div className="flex shrink-0 flex-wrap items-end gap-2">
                        <label className="min-w-0 flex-1 text-sm font-medium">Conversation<Select className="mt-1" value={state.conversationId} disabled={busy} onChange={(event) => { setViewSnapshotId(""); state.selectConversation(event.target.value); }}><option value="">New analysis</option>{workspace.conversations.map((conversation) => <option key={conversation.id} value={conversation.id}>{conversation.title}</option>)}</Select></label>
                        <Button className="gap-2" variant="subtle" disabled={busy} onClick={state.newConversation}><ActionIcon kind="plus" />New</Button>
                        <Button className="gap-2" variant="subtle" disabled={busy} onClick={() => setImportOpen(true)}><ActionIcon kind="import" />Import analysis</Button>
                        {state.conversationId && <Button className="gap-2" variant="ghost" disabled={busy} onClick={() => setArchiveOpen(true)}><ActionIcon kind="archive" />Archive</Button>}
                    </div>
                    {!!workspace.archived_conversations.length && <details className="text-sm"><summary className="cursor-pointer">Archived conversations</summary>{workspace.archived_conversations.map((conversation) => <div key={conversation.id} className="flex items-center justify-between gap-2"><span>{conversation.title}</span><Button size="sm" variant="ghost" disabled={busy} onClick={() => state.restore(conversation.id)}>Restore</Button></div>)}</details>}
                    <div className="analysis-messages space-y-5 pr-2 lg:min-h-32 lg:flex-1 lg:overflow-y-auto lg:overscroll-contain" onScroll={trackFollowLatest} aria-label="Messages" aria-busy={Boolean(active)}>
                        {!state.conversationId && <p className="py-8 text-sm text-neutral-600">Analyse this poem with its collection context, or ask a specific question.</p>}
                        {state.conversationId && !conversation && <p role="status">Loading conversation…</p>}
                        {conversation?.messages.map((message) => {
                            const turn = turns.find((turn) => turn.response_id === message.id);
                            const questionTurn = turns.findLast((turn) => turn.message_id === message.id);
                            const imported = message.import_id ? conversation.imports.find((record) => record.id === message.import_id) : null;
                            if (message.import_id) {
                                // An imported answer has no turn: no provider status, retry, model or usage.
                                const importedSnapshot = conversation.snapshots.find((snapshot) => snapshot.id === imported?.snapshot_id);
                                return <article key={message.id} className="break-words border-l-2 border-neutral-400 py-1 pl-4">
                                    <ImportedAnalysisHeader record={imported} snapshot={importedSnapshot} isNewerVersion={Boolean(importedSnapshot) && importedSnapshot.fingerprint !== workspace.fingerprint} onViewSource={(snapshotId) => { setViewSnapshotId(String(snapshotId)); setPane("poem"); }} />
                                    <div className="analysis-markdown text-sm leading-7"><ReactMarkdown>{message.content}</ReactMarkdown></div>
                                    <SuggestedNotes notes={imported?.suggested_notes} savedContents={workspace.notes.map((saved) => saved.content)} disabled={busy} onSave={(content) => state.saveNote({ content })} />
                                </article>;
                            }
                            return <article key={message.id} className={`break-words border-l-2 py-1 pl-4 ${message.role === "user" ? "border-teal-700" : "border-neutral-200"}`}>
                                <div className="mb-2 flex flex-wrap items-center gap-2 text-xs text-neutral-600"><strong>{message.role === "user" ? "You" : `${turn?.provider || "Assistant"} · ${turn?.model || ""}`}</strong>{turn && <span>{turn.status}{turn.request_context.retry_of ? " · retried question" : ""}</span>}{questionTurn && <button className="min-h-8 underline" onClick={() => { setViewSnapshotId(String(questionTurn.request_context.snapshot_id)); setPane("poem"); }}>View source version {questionTurn.request_context.snapshot_id}</button>}</div>
                                {questionTurn?.request_context.revision && <p className="mb-2 text-xs text-teal-800">New poem version supplied.</p>}
                                {questionTurn && <details className="mb-3 text-xs text-neutral-600"><summary className="cursor-pointer">Context used</summary><div className="mt-2 space-y-3"><p>Poem version {questionTurn.request_context.snapshot_id} · analysis instructions v{questionTurn.request_context.instruction_version}</p>{questionTurn.request_context.author_notes.map((note) => <p key={note.id} className="whitespace-pre-wrap">Author note: {note.content}</p>)}{questionTurn.request_context.comparisons.map((comparison) => <details key={comparison.id}><summary className="cursor-pointer">Comparison: {comparison.title}</summary><p className="whitespace-pre-wrap">{comparison.collection_description}</p><p className="mt-2 whitespace-pre-wrap font-serif">{comparison.content_md}</p></details>)}</div></details>}
                                <div className="analysis-markdown text-sm leading-7">{message.role === "user" ? <p className="whitespace-pre-wrap">{message.content}</p> : <ReactMarkdown>{message.content || (turn?.status === "running" ? "Writing…" : "No response text received.")}</ReactMarkdown>}</div>
                                {message.role === "user" && questionTurn && ["failed", "cancelled"].includes(questionTurn.status) && <div className="mt-3 border-l-2 border-amber-500 pl-3 text-sm"><p>{questionTurn.error || "Response stopped. The saved question and source version are retained."}</p>{questionTurn.id === latest?.id && <Button className="mt-2" size="sm" variant="subtle" disabled={busy || Boolean(active)} onClick={() => state.retry(questionTurn.id)}>Retry saved question</Button>}</div>}
                                {message.role === "user" && <button className="mt-2 min-h-9 text-xs underline" disabled={busy} onClick={() => state.saveNote({ content: message.content, source_message_id: message.id })}>Save as author note</button>}
                            </article>;
                        })}
                        <div ref={messagesEnd} />
                    </div>
                    <form className="shrink-0 space-y-3 border-t border-neutral-200 pt-3" onSubmit={(event) => { event.preventDefault(); state.send(); }}>
                        {changed && <div className="space-y-2 border-l-2 border-amber-500 pl-3 text-sm"><p>Poem changed since the last analysis.</p><Select aria-label="Version to send" value={state.source} onChange={(event) => state.setSource(event.target.value)}><option value="latest">Use latest saved poem</option><option value="previous">Continue discussing previous version</option></Select></div>}
                        <details className="group text-xs">
                            <summary className="flex min-h-9 cursor-pointer list-none items-center gap-2 font-medium [&::-webkit-details-marker]:hidden"><svg className="shrink-0 transition-transform group-open:rotate-90" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg><span>Model settings</span><span className={state.model ? "truncate font-normal text-neutral-600" : "font-normal text-amber-800"}>{state.model ? `${workspace.connections.find((connection) => String(connection.id) === state.connectionId)?.name || ""} · ${selectedModel?.name || state.model}${state.effort !== "default" ? ` · ${state.effort}` : ""}` : "Choose a model before sending"}</span></summary>
                            <div className="mt-2 space-y-3">
                        <div className="flex flex-wrap gap-2">
                            <label className="min-w-36 flex-1 text-xs font-medium">Connection<Select className="mt-1" value={state.connectionId} disabled={busy || Boolean(active)} onChange={(event) => state.setConnectionId(event.target.value)}><option value="">Select connection</option>{workspace.connections.map((connection) => <option key={connection.id} value={connection.id}>{connection.name}</option>)}</Select></label>
                            <label className="min-w-40 flex-1 text-xs font-medium">Model<Select className="mt-1" value={state.model} disabled={busy || Boolean(active) || !state.catalogue} onChange={(event) => { state.setModel(event.target.value); state.setEffort("default"); }}><option value="">{state.catalogue ? "Select model" : "Loading models…"}</option>{state.catalogue?.models.map((model) => <option key={model.id} value={model.id}>{model.name}</option>)}{state.model && !selectedModel && <option value={state.model}>{state.model} · saved choice</option>}</Select></label>
                            {!!selectedModel?.efforts.length && <label className="text-xs font-medium">Thinking<Select className="mt-1" value={state.effort} disabled={busy || Boolean(active)} onChange={(event) => state.setEffort(event.target.value)}><option value="default">Provider default</option>{selectedModel.efforts.map((effort) => <option key={effort} value={effort}>{effort}</option>)}</Select></label>}
                        </div>
                        <div className="flex flex-wrap gap-3 text-xs"><a className="inline-flex min-h-8 items-center underline" href="/studio/assistant-connections">Manage connections and usage</a><button className="min-h-8 underline" type="button" disabled={busy || Boolean(active) || !state.connectionId} onClick={() => state.loadModels(true)}>Refresh models</button><button className="min-h-8 underline" type="button" disabled={busy} onClick={state.reload}>Reload saved poem</button></div>
                            </div>
                        </details>
                        {state.catalogue && state.catalogue.status !== "ready" && <p className="text-xs text-amber-800">{state.catalogue.message}</p>}
                        <label className="sr-only" htmlFor="analysis-question">Question or author explanation</label>
                        <textarea id="analysis-question" className="min-h-20 w-full resize-y rounded-lg border border-neutral-300 bg-white p-3 text-sm" placeholder="Ask about this poem or explain an intentional choice…" value={state.question} maxLength={12000} onChange={(event) => state.setQuestion(event.target.value)} />
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <span className="text-xs text-neutral-600">{state.source === "previous" && changed ? "Previous version" : "Latest version"} · collection · {state.comparisonIds.length} comparisons</span>
                            {active ? <div className="flex items-center gap-2"><span className="text-xs" role="status">{active.progress}</span><Button type="button" variant="danger" disabled={busy} onClick={() => state.stop(active.id)}>Stop</Button></div> : <div className="flex gap-2"><Button className="gap-2" type="button" variant="subtle" disabled={!canSend} onClick={() => state.send("Analyse this poem in depth, considering its sound and page presentation equally, and its relationship to the supplied collection context.")}><ActionIcon />Analyse poem</Button><Button className="gap-2" type="submit" disabled={!canSend || !state.question.trim()}><ActionIcon kind="send" />Send</Button></div>}
                        </div>
                    </form>
                </section>
            </div>
            <ImportAnalysisDialog open={importOpen || Boolean(state.importPreview || state.importFailure)} onClose={() => { setImportOpen(false); state.closeImport(); }} preview={state.importPreview} failure={state.importFailure} busy={state.importBusy} onPreview={state.previewImport} onSave={state.saveImport} onOpen={(conversationId) => { setImportOpen(false); state.openImported(conversationId); }} />
            <ConfirmDialog open={archiveOpen} title="Archive this conversation?" description="Any running response will stop. Saved discussion and source versions will be retained." confirmLabel="Archive conversation" onClose={() => setArchiveOpen(false)} onConfirm={() => { setArchiveOpen(false); state.archive(); }} />
        </div>
    );
}
