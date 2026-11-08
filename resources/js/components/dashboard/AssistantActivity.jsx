import { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { getAssistantActivity, acknowledgeActivity } from "@/services/poemAnalysis";
import { useAuth } from "@/hooks/useAuth";
import { can } from "@/services/authService";

export default function AssistantActivity() {
    const { user } = useAuth();
    if (!can(user, "poems.update") && !can(user, "worlds.update")) return null;
    return <ActivityList />;
}

function ActivityList() {
    const [turns, setTurns] = useState([]);
    const [error, setError] = useState("");
    const [open, setOpen] = useState(false);
    const container = useRef(null);
    useEffect(() => {
        let stale = false;
        let timer;
        async function poll() {
            try {
                const loaded = await getAssistantActivity();
                if (stale) return;
                setTurns(loaded);
                setError("");
            } catch (failure) { if (!stale) setError(failure.message); }
            if (!stale) timer = window.setTimeout(poll, 10000);
        }
        poll();
        const close = (event) => { if (!container.current?.contains(event.target)) setOpen(false); };
        document.addEventListener("pointerdown", close);
        return () => { stale = true; window.clearTimeout(timer); document.removeEventListener("pointerdown", close); };
    }, []);
    async function acknowledge(id) {
        try {
            await acknowledgeActivity(id);
            setTurns((turns) => turns.filter((turn) => turn.id !== id));
        } catch (failure) { setError(failure.message); }
    }
    const active = turns.filter((turn) => ["queued", "running"].includes(turn.status)).length;
    return <div ref={container} className="relative mb-2 flex shrink-0 justify-end" onKeyDown={(event) => { if (event.key === "Escape") setOpen(false); }}>
        <button className="inline-flex min-h-9 items-center gap-2 text-xs text-neutral-700" aria-expanded={open} aria-controls="publishing-assistant-activity" onClick={() => setOpen(!open)}><span className={`h-2 w-2 rounded-full ${active ? "animate-pulse bg-teal-700" : "bg-neutral-400"}`} aria-hidden="true" />Activity · {active} working · {turns.length - active} updates</button>
        {open && <section id="publishing-assistant-activity" className="absolute right-0 top-full z-30 max-h-[60vh] w-80 max-w-[80vw] space-y-3 overflow-y-auto rounded-lg border border-neutral-200 bg-white p-4 shadow-lg" aria-label="Assistant activity">
            {error && <p className="text-xs text-red-700" role="alert">{error}</p>}
            {!turns.length && <p className="text-sm">No outstanding assistant activity.</p>}
            {turns.map((turn) => <article key={turn.id} className="border-b border-neutral-200 pb-3 text-sm">{turn.url.startsWith("/studio/") ? <a className="font-medium underline" href={turn.url}>{turn.title}</a> : <Link className="font-medium underline" to={turn.url} onClick={() => setOpen(false)}>{turn.title}</Link>}<p className="mt-1 text-xs">{turn.status} · {turn.error || turn.progress}</p>{!["queued", "running"].includes(turn.status) && <button className="mt-1 min-h-9 text-xs underline" onClick={() => acknowledge(turn.id)}>Mark reviewed</button>}</article>)}
        </section>}
    </div>;
}
