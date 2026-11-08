import { useEffect, useMemo, useRef, useState } from "react";
import { useAuth } from "@/hooks/useAuth";

export function useLocalDraft({ key, form, initialForm, setForm, version = null, enabled = true }) {
    const { user } = useAuth();
    const [recoveredAt, setRecoveredAt] = useState(null);
    const [persistedAt, setPersistedAt] = useState(null);
    const [recovery, setRecovery] = useState(null);
    const [storageWarning, setStorageWarning] = useState("");
    const restoredKeyRef = useRef(null);
    const ownerId = user?.id;
    const storageKey = ownerId && key ? `dreamers-palette:user:${ownerId}:${key}` : null;
    const isDirty = useMemo(() => Boolean(initialForm) && JSON.stringify(form) !== JSON.stringify(initialForm), [form, initialForm]);

    useEffect(() => {
        if (!enabled || !storageKey || !initialForm || restoredKeyRef.current === storageKey) return;
        restoredKeyRef.current = storageKey;
        try {
            const stored = window.localStorage.getItem(storageKey);
            const draft = stored ? JSON.parse(stored) : null;
            if (draft?.ownerId === ownerId && draft.form && draft.savedAt && JSON.stringify(draft.form) !== JSON.stringify(initialForm)) {
                window.queueMicrotask(() => setRecovery({ ...draft, outdated: draft.baseVersion !== version }));
            }
        } catch {
            window.queueMicrotask(() => setStorageWarning("Browser recovery is unavailable. You can still edit and save to the server."));
        }
    }, [enabled, storageKey, initialForm, ownerId, version]);

    useEffect(() => {
        if (!enabled || !storageKey || !initialForm || !isDirty || (recovery && recovery.ownerId === ownerId)) return;
        const timeout = window.setTimeout(() => {
            const savedAt = new Date().toISOString();
            try {
                window.localStorage.setItem(storageKey, JSON.stringify({ ownerId, form, savedAt, baseVersion: version }));
                setPersistedAt(savedAt);
                setStorageWarning("");
            } catch {
                setStorageWarning("A browser recovery copy could not be stored. Your changes remain here; save them to the server.");
            }
        }, 500);
        return () => window.clearTimeout(timeout);
    }, [enabled, storageKey, initialForm, isDirty, recovery, ownerId, form, version]);

    function clearDraft() {
        try {
            if (storageKey) window.localStorage.removeItem(storageKey);
        } catch {
            setStorageWarning("The browser recovery copy could not be cleared. Your server save is unaffected.");
        }
        setRecovery(null);
        setRecoveredAt(null);
        setPersistedAt(null);
    }

    function restoreDraft() {
        if (!recovery || recovery.ownerId !== ownerId) return;
        setForm(recovery.form);
        setRecoveredAt(recovery.savedAt);
        setRecovery(null);
    }

    return { isDirty, recoveredAt, persistedAt, recovery: recovery?.ownerId === ownerId ? recovery : null, storageWarning, clearDraft, restoreDraft };
}
