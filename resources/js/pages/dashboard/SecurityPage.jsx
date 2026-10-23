import { useEffect, useState } from "react";
import { getSecuritySettings } from "@/services/securityService";
import ConfirmIdentity from "@/components/auth/ConfirmIdentity";
import AuthenticatorSettings from "@/components/auth/AuthenticatorSettings";
import PasskeySettings from "@/components/auth/PasskeySettings";

export default function SecurityPage() {
    const [settings, setSettings] = useState(null);
    const [busy, setBusy] = useState("");
    const [error, setError] = useState("");
    const [status, setStatus] = useState("");

    useEffect(() => {
        let active = true;
        getSecuritySettings()
            .then((response) => {
                if (active) setSettings(response);
            })
            .catch((failure) => {
                if (active) setError(failure.message);
            });
        return () => {
            active = false;
        };
    }, []);

    async function perform(label, action, message) {
        setBusy(label);
        setError("");
        setStatus("");
        try {
            await action();
            setSettings(await getSecuritySettings());
            setStatus(message);
        } catch (failure) {
            setError(
                failure.message || "The security setting could not be changed.",
            );
            try {
                const current = await getSecuritySettings();
                setSettings(current);
            } catch {
                /* Session expiry is handled by the shared authentication store. */
            }
        } finally {
            setBusy("");
        }
    }

    return (
        <section className="mx-auto w-full max-w-3xl space-y-8 p-4 md:p-8">
            <header className="space-y-2">
                <h1 className="font-serif text-2xl">Account security</h1>
                <p className="text-sm text-neutral-600">
                    Password sign-in is always available. Two-factor
                    authentication and passkeys are optional.
                </p>
            </header>
            {error && (
                <p
                    role="alert"
                    className="rounded-lg bg-red-50 p-3 text-sm text-red-800"
                >
                    {error}
                </p>
            )}
            {status && (
                <p
                    role="status"
                    className="rounded-lg bg-green-50 p-3 text-sm text-green-800"
                >
                    {status}
                </p>
            )}
            {!settings ? (
                <p role="status" className="text-sm">
                    {error
                        ? "Security settings could not be loaded. Reload the page to retry."
                        : "Loading security settings…"}
                </p>
            ) : (
                <>
                    <ConfirmIdentity
                        settings={settings}
                        busy={busy}
                        perform={perform}
                    />
                    <AuthenticatorSettings
                        key={
                            settings.password_confirmed ? "confirmed" : "locked"
                        }
                        settings={settings}
                        busy={busy}
                        perform={perform}
                    />
                    <PasskeySettings
                        settings={settings}
                        busy={busy}
                        perform={perform}
                    />
                </>
            )}
        </section>
    );
}
