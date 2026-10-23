import { Passkeys } from "@laravel/passkeys";
import { apiRequest, ensureCsrf } from "./_shared/api";

export const getSecuritySettings = () => apiRequest("/api/auth/security");
export const getAuthFeatures = () => apiRequest("/api/auth/features");
export const confirmPassword = (password) =>
    apiRequest("/user/confirm-password", {
        method: "POST",
        body: { password },
    });
export const enableTwoFactor = () =>
    apiRequest("/user/two-factor-authentication", { method: "POST" });
export const getTwoFactorQrCode = () => apiRequest("/user/two-factor-qr-code");
export const confirmTwoFactor = (code) =>
    apiRequest("/user/confirmed-two-factor-authentication", {
        method: "POST",
        body: { code },
    });
export const disableTwoFactor = () =>
    apiRequest("/user/two-factor-authentication", { method: "DELETE" });
export const getRecoveryCodes = () =>
    apiRequest("/user/two-factor-recovery-codes");
export async function regenerateRecoveryCodes() {
    await apiRequest("/user/two-factor-recovery-codes", { method: "POST" });
    return getRecoveryCodes();
}
export async function registerPasskey(name) {
    await ensureCsrf();
    return Passkeys.register({ name });
}
export async function confirmWithPasskey() {
    await ensureCsrf();
    return Passkeys.verify({
        routes: {
            options: "/passkeys/confirm/options",
            submit: "/passkeys/confirm",
        },
    });
}
export const removePasskey = (id) =>
    apiRequest(`/user/passkeys/${encodeURIComponent(id)}`, {
        method: "DELETE",
    });
