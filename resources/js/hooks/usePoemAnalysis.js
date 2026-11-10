import { useCallback, useEffect, useRef, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { requestUuid } from "@/utils/requestUuid";
import * as analysis from "@/services/poemAnalysis";

export function usePoemAnalysis(poemId) {
    const [params, setParams] = useSearchParams();
    const conversationId = (params.get("conversation") === "new" ? "" : params.get("conversation")) || "";
    const explicitNew = params.get("conversation") === "new";
    const [workspace, setWorkspace] = useState(null);
    const [conversation, setConversation] = useState(null);
    const [error, setError] = useState("");
    const [pollError, setPollError] = useState("");
    const [busy, setBusy] = useState(false);
    const [question, setQuestionState] = useState(() => {
        try { return sessionStorage.getItem(`poem-analysis:${poemId}:${conversationId}`) || ""; }
        catch { return ""; }
    });
    const [connectionId, setConnectionId] = useState("");
    const [model, setModel] = useState("");
    const [effort, setEffort] = useState("default");
    const [catalogue, setCatalogue] = useState(null);
    const [source, setSource] = useState("latest");
    const [comparisonIds, setComparisonIds] = useState([]);
    const [importPreview, setImportPreview] = useState(null);
    const [importFailure, setImportFailure] = useState(null);
    const [importBusy, setImportBusy] = useState(false);
    const scope = `${poemId}:${conversationId}`;
    const mounted = useRef(false);
    const requestRef = useRef(null);
    const operationRef = useRef(false);
    const pendingConversationRef = useRef(null);
    const pollNowRef = useRef(null);
    const retryRef = useRef(null);
    useEffect(() => { mounted.current = true; return () => { mounted.current = false; }; }, []);

    function setQuestion(content) {
        setQuestionState(content);
        try {
            if (content) sessionStorage.setItem(`poem-analysis:${scope}`, content);
            else sessionStorage.removeItem(`poem-analysis:${scope}`);
        } catch { /* Browser recovery is optional; the current draft stays in memory. */ }
    }

    const selectConversation = useCallback((id) => setParams({ conversation: id || "new" }, { replace: true }), [setParams]);
    const loadWorkspace = useCallback(async () => {
        const loaded = await analysis.getAnalysis(poemId);
        if (mounted.current) setWorkspace(loaded);
        return loaded;
    }, [poemId]);

    useEffect(() => {
        let stale = false;
        analysis.getAnalysis(poemId).then((loaded) => {
            if (stale) return;
            setWorkspace(loaded);
            const savedConnection = loaded.conversations.find((conversation) => String(conversation.id) === conversationId)?.connection_id;
            setConnectionId(String(savedConnection || loaded.connections[0]?.id || ""));
            if (!conversationId && !explicitNew && loaded.conversations.length) selectConversation(loaded.conversations[0].id);
        }).catch((failure) => { if (!stale) setError(failure.message); });
        return () => { stale = true; };
    }, [poemId, conversationId, explicitNew, selectConversation]);

    useEffect(() => {
        let stale = false;
        let timer;
        let initialized = false;
        let inFlight = false;
        let refreshRequested = false;

        const poll = async () => {
            if (inFlight) { refreshRequested = true; return; }
            inFlight = true;
            window.clearTimeout(timer);
            let delay = 3000;
            try {
                const loaded = await analysis.getConversation(poemId, conversationId);
                if (stale) return;
                setConversation(loaded);
                setPollError("");
                const latest = loaded.turns.at(-1);
                if (latest && !initialized) setComparisonIds(latest.request_context.comparisons.map((poem) => poem.poem_id));
                initialized = true;
                delay = loaded.turns.some((turn) => ["queued", "running"].includes(turn.status)) ? 1500 : 10000;
            } catch (failure) {
                if (!stale) setPollError(failure.message);
            } finally {
                inFlight = false;
                if (!stale) timer = window.setTimeout(poll, refreshRequested ? 0 : delay);
                refreshRequested = false;
            }
        };
        pollNowRef.current = poll;
        if (conversationId) poll();
        return () => { stale = true; window.clearTimeout(timer); pollNowRef.current = null; };
    }, [poemId, conversationId, scope]);

    const latestTurn = conversation?.turns.at(-1);
    const savedEffort = latestTurn?.connection_id === Number(connectionId) ? latestTurn.request_context.effort : "default";
    const savedModel = (latestTurn?.connection_id === Number(connectionId) ? latestTurn.model : "") || workspace?.connections.find((connection) => String(connection.id) === connectionId)?.model || "";
    const loadModels = useCallback(async (refresh = false) => {
        if (!connectionId) return;
        const loaded = await analysis.getModels(connectionId, refresh);
        if (mounted.current) {
            setCatalogue({ ...loaded, connectionId });
            setModel(savedModel);
            setEffort(savedEffort);
        }
    }, [connectionId, savedModel, savedEffort]);

    useEffect(() => {
        if (!connectionId) return;
        let stale = false;
        analysis.getModels(connectionId).then((loaded) => {
            if (stale) return;
            setCatalogue({ ...loaded, connectionId });
            setModel(savedModel);
            setEffort(savedEffort);
        }).catch((failure) => { if (!stale) setError(failure.message); });
        return () => { stale = true; };
    }, [connectionId, savedModel, savedEffort]);

    async function perform(operation) {
        if (operationRef.current) return;
        operationRef.current = true;
        setBusy(true);
        setError("");
        try { await operation(); return true; }
        catch (failure) { if (mounted.current) setError(failure.message); return false; }
        finally { operationRef.current = false; if (mounted.current) setBusy(false); }
    }

    async function newConversation() {
        await perform(async () => {
            const created = await analysis.createConversation(poemId, `Analysis ${workspace.conversations.length + 1}`);
            await loadWorkspace();
            selectConversation(created.id);
        });
    }

    async function send(content = question) {
        if (!content.trim()) return;
        await perform(async () => {
            let selected = conversationId;
            if (!selected && pendingConversationRef.current) selected = pendingConversationRef.current;
            if (!selected) {
                const created = await analysis.createConversation(poemId, "Analysis 1");
                selected = String(created.id);
                pendingConversationRef.current = selected;
                // Stay on the current screen until the first question is durably saved.
            }
            const payload = {
                content, connection_id: Number(connectionId), model, effort,
                source, fingerprint: workspace.fingerprint, comparison_ids: comparisonIds,
            };
            const signature = JSON.stringify({ selected, ...payload });
            if (requestRef.current?.signature !== signature) requestRef.current = { signature, uuid: requestUuid() };
            await analysis.sendMessage(poemId, selected, { ...payload, request_uuid: requestRef.current.uuid });
            requestRef.current = null;
            if (!mounted.current) return;
            setQuestion("");
            try { sessionStorage.removeItem(`poem-analysis:${scope}`); } catch { /* Optional recovery storage. */ }
            const loaded = await analysis.getConversation(poemId, selected);
            setConversation(loaded);
            if (conversationId) pollNowRef.current?.();
            await loadWorkspace();
            if (selected !== conversationId) selectConversation(selected);
        });
    }

    async function refreshConversation() {
        if (conversationId) {
            setConversation(await analysis.getConversation(poemId, conversationId));
            pollNowRef.current?.();
        }
    }

    function importFailureFrom(failure) {
        return { message: failure.message, errors: failure.errors || {} };
    }

    async function previewImport(markdown) {
        setImportBusy(true);
        setImportFailure(null);
        try { setImportPreview(await analysis.previewImport(poemId, markdown)); }
        catch (failure) { if (mounted.current) { setImportPreview(null); setImportFailure(importFailureFrom(failure)); } }
        finally { if (mounted.current) setImportBusy(false); }
    }

    // Closing an unsaved preview drops it server-side; a repeat of an already saved package has nothing to drop.
    async function closeImport() {
        const abandoned = importPreview && !importPreview.saved ? importPreview : null;
        setImportPreview(null);
        setImportFailure(null);
        if (abandoned) await analysis.discardImport(poemId, abandoned.id).catch(() => {});
    }

    async function openImported(conversation) {
        setImportPreview(null);
        setImportFailure(null);
        await loadWorkspace();
        if (String(conversation) === conversationId) await refreshConversation();
        else selectConversation(conversation);
    }

    async function saveImport() {
        setImportBusy(true);
        setImportFailure(null);
        try {
            const saved = await analysis.saveImport(poemId, importPreview.id);
            await openImported(saved.conversation_id);
        } catch (failure) { if (mounted.current) setImportFailure(importFailureFrom(failure)); }
        finally { if (mounted.current) setImportBusy(false); }
    }

    return {
        importPreview, importFailure, importBusy, previewImport, saveImport, closeImport, openImported,
        workspace, conversation, conversationId, question, setQuestion, connectionId, setConnectionId,
        model, setModel, effort, setEffort, catalogue: catalogue?.connectionId === connectionId ? catalogue : null, source, setSource, comparisonIds, setComparisonIds,
        error: error || pollError, busy, selectConversation, newConversation, send, loadModels: (refresh) => perform(() => loadModels(refresh)),
        reload: () => perform(loadWorkspace),
        stop: (turn) => perform(async () => { await analysis.stopTurn(poemId, conversationId, turn); await refreshConversation(); }),
        retry: (turn) => perform(async () => {
            if (retryRef.current?.turn !== turn) retryRef.current = { turn, uuid: requestUuid() };
            await analysis.retryTurn(poemId, conversationId, turn, retryRef.current.uuid);
            retryRef.current = null;
            await refreshConversation();
        }),
        saveNote: (body) => perform(async () => { await analysis.saveAuthorNote(poemId, body); await loadWorkspace(); }),
        retireNote: (note) => perform(async () => { await analysis.retireAuthorNote(poemId, note); await loadWorkspace(); }),
        restoreNote: (note) => perform(async () => { await analysis.restoreAuthorNote(poemId, note); await loadWorkspace(); }),
        restore: (conversation) => perform(async () => { await analysis.restoreConversation(poemId, conversation); await loadWorkspace(); selectConversation(conversation); }),
        archive: () => perform(async () => { await analysis.archiveConversation(poemId, conversationId); selectConversation(""); await loadWorkspace(); }),
    };
}
