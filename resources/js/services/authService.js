import { Passkeys } from "@laravel/passkeys";
import { apiRequest, ensureCsrf } from "./_shared/api";

let state = { user: null, loading: true, error: null };
let pendingSession = null;
let sessionRevision = 0;
const listeners = new Set();

function updateSession(user, error = null) {
    state = { user, loading: false, error };
    listeners.forEach((listener) => listener());
}

export function getAuthSnapshot() {
    return state;
}

export function subscribeAuth(listener) {
    listeners.add(listener);
    if (state.loading) void refreshSession();
    return () => listeners.delete(listener);
}

export function refreshSession() {
    if (!pendingSession) {
        const revision = sessionRevision;
        pendingSession = apiRequest("/api/auth/user")
            .then((response) => {
                if (revision !== sessionRevision) return state.user;
                updateSession(response.data);
                return response.data;
            })
            .catch((error) => {
                if (revision === sessionRevision)
                    updateSession(
                        null,
                        error.status === 401 ? null : error.message,
                    );
                return null;
            })
            .finally(() => {
                pendingSession = null;
            });
    }
    return pendingSession;
}

window.addEventListener("auth:expired", () => {
    sessionRevision++;
    updateSession(null);
});
window.addEventListener("focus", () => {
    void refreshSession();
});

export async function getSession() {
    const user = await refreshSession();
    return user ? { user } : null;
}

export const getCurrentUser = refreshSession;

async function refreshAfterSignIn() {
    sessionRevision++;
    if (pendingSession) await pendingSession;
    const user = await refreshSession();
    if (!user)
        throw new Error(
            state.error ||
                "Your account could not be loaded. Please sign in again.",
        );
    return user;
}

export async function signInWithEmail(email, password) {
    const response = await apiRequest("/login", {
        method: "POST",
        body: { email, password },
    });
    if (!response.two_factor) await refreshAfterSignIn();
    return response;
}

export async function completeTwoFactorChallenge(code, useRecoveryCode) {
    await apiRequest("/two-factor-challenge", {
        method: "POST",
        body: useRecoveryCode ? { recovery_code: code } : { code },
    });
    return refreshAfterSignIn();
}

export async function signInWithPasskey() {
    await ensureCsrf();
    await Passkeys.verify();
    return refreshAfterSignIn();
}

export async function signOut() {
    sessionRevision++;
    await apiRequest("/logout", { method: "POST" });
    sessionRevision++;
    updateSession(null);
}

export function can(user, permission) {
    return user?.permissions?.includes(permission) ?? false;
}
