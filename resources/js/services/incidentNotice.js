const incidents = new Map();
let lastFailure = null;

function pageFeature() {
    const path = window.location.pathname;
    const section = new URLSearchParams(window.location.search).get("section");
    if (/^\/studio\/worlds\/\d+\/develop$/.test(path)) {
        return `world.${({ entries: "entries", timeline: "timeline", arcs: "arcs", alignments: "alignments" })[section] || "people"}`;
    }
    if (/^\/studio\/worlds\/\d+\/sandbox$/.test(path)) return "world.sandbox";
    if (/^\/studio\/worlds\/\d+\/thinking$/.test(path)) return "world.connections";
    if (/^\/studio\/worlds\/\d+\/reasoning$/.test(path)) return "world.reasoning";
    for (const feature of ["maps", "calendars"]) {
        if (new RegExp(`^/studio/worlds/\\d+/${feature}$`).test(path)) return `world.${feature}`;
    }
    if (/^\/studio\/worlds(?:\/\d+)?$/.test(path)) return "world.systems";
    if (/^\/studio\/manuscripts\/\d+$/.test(path)) return "manuscripts.writing";
    if (/^\/studio\/manuscripts\/\d+\/compile$/.test(path)) return "manuscripts.compile";
    if (/^\/studio\/manuscripts\/\d+\/publishing$/.test(path)) return "manuscripts.publishing";
    if (/^\/dashboard\/poems\/\d+\/analysis$/.test(path)) return "poems.analysis";
    const publishing = path.match(/^\/dashboard\/(collections|poems|books|chapters)(?:\/|$)/);
    if (publishing) return `publishing.${publishing[1]}`;
    if (path === "/studio/assistant-connections") return "assistant.connections";
    if (path === "/account") return "account.profile";
    if (path === "/account/security") return "account.security";
    if (/^\/studio\/reviews(?:\/\d+)?$/.test(path)) return "manuscripts.reviews";
    if (path === "/studio" || /^\/studio\/manuscripts\//.test(path)) return "manuscripts.management";
    return null;
}

function renderNotice() {
    const banner = document.querySelector("[data-incident-notice]");
    if (!banner) return;
    const notice = incidents.get(pageFeature()) || (lastFailure?.page === window.location.href ? lastFailure.notice : null);
    banner.hidden = !notice;
    if (!notice) return;
    banner.querySelector("[data-incident-message]").textContent = notice.message;
    banner.querySelector("[data-incident-reference]").textContent = notice.id ? `Reference: ${notice.id}` : "";
}

export function showIncident(notice) {
    if (!notice?.message) return;
    if (notice.feature) incidents.set(notice.feature, notice);
    lastFailure = { page: window.location.href, notice };
    renderNotice();
}

async function refreshStatus() {
    if (!window.location.pathname.startsWith("/studio") && !window.location.pathname.startsWith("/dashboard") && !window.location.pathname.startsWith("/account")) return;
    try {
        const response = await fetch("/api/studio/incidents", { credentials: "same-origin", headers: { Accept: "application/json" } });
        if (!response.ok) return;
        const payload = await response.json();
        incidents.clear();
        for (const notice of payload.incidents || []) incidents.set(notice.feature, notice);
        if (lastFailure?.notice.id && ![...incidents.values()].some((notice) => notice.id === lastFailure.notice.id)) {
            // Keep unsaved work in place; the user chooses when to reload after the fix.
            lastFailure.notice = { message: "The feature pause has been cleared. Copy any unsaved writing, then reload this page before continuing." };
        }
        renderNotice();
    } catch {
        // Retain the last known pause during connectivity failures.
    }
}

if (typeof document !== "undefined") {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", refreshStatus, { once: true });
    else refreshStatus();
    window.addEventListener("popstate", renderNotice);
    for (const eventName of ["click", "submit", "beforeinput"]) {
        document.addEventListener(eventName, (event) => {
            if (!incidents.has(pageFeature())) return;
            const changesFeature = eventName === "submit" || eventName === "beforeinput"
                || event.target.closest("button, [role=button], input[type=submit], input[type=checkbox], input[type=radio], select");
            if (!changesFeature) return;
            // Navigation links and selecting/copying existing writing remain available.
            event.preventDefault();
            event.stopImmediatePropagation();
            renderNotice();
        }, true);
    }
    document.addEventListener("livewire:navigated", refreshStatus);
    setInterval(renderNotice, 1000);
    setInterval(refreshStatus, 30000);
}
