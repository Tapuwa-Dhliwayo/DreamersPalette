import { useState } from "react";
import { Navigate } from "react-router-dom";
import { useAuth } from "@/hooks/useAuth";
import { apiRequest } from "@/services/_shared/api";
import { can, signOut } from "@/services/authService";
import Button from "@/components/ui/Button";

export default function VerifyEmailPage() {
    const { user, loading } = useAuth();
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");
    if (loading) return <p role="status">Checking your session…</p>;
    if (!user) return <Navigate to="/login" replace />;
    if (user.email_verified)
        return (
            <Navigate
                to={
                    can(user, "dashboard.access")
                        ? "/dashboard"
                        : "/dashboard/security"
                }
                replace
            />
        );

    async function sendLink() {
        setBusy(true);
        setError("");
        setMessage("");
        try {
            await apiRequest("/email/verification-notification", {
                method: "POST",
            });
            setMessage(
                "Verification link sent. Open it in this browser, then return here.",
            );
        } catch (failure) {
            setError(failure.message);
        } finally {
            setBusy(false);
        }
    }
    async function leave() {
        setBusy(true);
        setError("");
        try {
            await signOut();
        } catch (failure) {
            setError(failure.message);
        } finally {
            setBusy(false);
        }
    }
    return (
        <section className="mx-auto max-w-md space-y-5 py-12">
            <h1 className="font-serif text-2xl">Verify your email</h1>
            <p className="text-sm">
                Confirm your email address before opening your account or
                studio.
            </p>
            {message && (
                <p role="status" className="text-sm">
                    {message}
                </p>
            )}
            {error && (
                <p role="alert" className="text-sm">
                    {error}
                </p>
            )}
            <div className="flex flex-wrap gap-3">
                <Button onClick={sendLink} disabled={busy}>
                    Send verification link
                </Button>
                <Button variant="subtle" onClick={leave} disabled={busy}>
                    Sign out
                </Button>
            </div>
        </section>
    );
}
