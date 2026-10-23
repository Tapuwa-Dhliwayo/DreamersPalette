let csrfRequest = null;

export class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.name = "ApiError";
        this.status = status;
        this.errors = errors;
    }
}

export function ensureCsrf() {
    if (!csrfRequest) {
        csrfRequest = fetch("/sanctum/csrf-cookie", {
            credentials: "same-origin",
            headers: { Accept: "application/json" },
        })
            .then((response) => {
                if (!response.ok)
                    throw new ApiError(
                        "Could not prepare a secure session. Please retry.",
                        response.status,
                    );
            })
            .catch((error) => {
                csrfRequest = null;
                throw error;
            });
    }
    return csrfRequest;
}

function csrfToken() {
    const cookie = document.cookie
        .split("; ")
        .find((entry) => entry.startsWith("XSRF-TOKEN="));
    return cookie ? decodeURIComponent(cookie.slice("XSRF-TOKEN=".length)) : "";
}

export async function apiRequest(
    path,
    { method = "GET", body, retryCsrf = true } = {},
) {
    if (
        new URL(path, window.location.origin).origin !== window.location.origin
    ) {
        throw new Error("API requests must use a same-origin path.");
    }
    const changesState = !["GET", "HEAD"].includes(method);
    if (changesState) await ensureCsrf();
    const headers = {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    };
    if (changesState) headers["X-XSRF-TOKEN"] = csrfToken();
    const multipart = body instanceof FormData;
    if (body !== undefined && !multipart)
        headers["Content-Type"] = "application/json";
    const response = await fetch(path, {
        method,
        credentials: "same-origin",
        headers,
        ...(body !== undefined
            ? { body: multipart ? body : JSON.stringify(body) }
            : {}),
    });
    if (response.status === 419 && changesState && retryCsrf) {
        csrfRequest = null;
        return apiRequest(path, { method, body, retryCsrf: false });
    }
    if (response.status === 204) return null;
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        if (response.status === 401)
            window.dispatchEvent(new Event("auth:expired"));
        const validationMessage = Object.values(payload.errors ?? {}).flat()[0];
        throw new ApiError(
            response.status >= 500
                ? "The server could not complete this request. Please retry."
                : validationMessage ||
                      payload.message ||
                      "The request could not be completed.",
            response.status,
            payload.errors,
        );
    }
    return payload;
}
