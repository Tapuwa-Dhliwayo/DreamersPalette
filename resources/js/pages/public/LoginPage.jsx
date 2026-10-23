import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Passkeys } from "@laravel/passkeys";
import { useAuth } from "@/hooks/useAuth";
import {
    can,
    completeTwoFactorChallenge,
    signInWithEmail,
    signInWithPasskey,
} from "@/services/authService";
import { getAuthFeatures } from "@/services/securityService";
import Logo from "@/components/ui/Logo";
import Button from "@/components/ui/Button";
import Input from "@/components/ui/Input";
import FormField from "@/components/ui/FormField";

export default function LoginPage() {
    const navigate = useNavigate();
    const { user, loading: sessionLoading, error: sessionError } = useAuth();
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [code, setCode] = useState("");
    const [challenge, setChallenge] = useState(false);
    const [recovery, setRecovery] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState("");
    const [features, setFeatures] = useState({ passkeys: false });

    useEffect(() => {
        let active = true;
        getAuthFeatures()
            .then((options) => {
                if (active) setFeatures(options);
            })
            .catch(() => {});
        return () => {
            active = false;
        };
    }, []);

    useEffect(() => {
        if (!sessionLoading && user) {
            const destination = !user.email_verified
                ? "/verify-email"
                : can(user, "dashboard.access")
                  ? "/dashboard"
                  : "/dashboard/security";
            navigate(destination, { replace: true });
        }
    }, [navigate, sessionLoading, user]);

    async function submit(event) {
        event.preventDefault();
        setBusy(true);
        setError("");
        try {
            if (challenge) {
                await completeTwoFactorChallenge(code, recovery);
            } else {
                const response = await signInWithEmail(email, password);
                if (response.two_factor) setChallenge(true);
            }
        } catch (failure) {
            setError(failure.message || "Could not sign in. Please retry.");
        } finally {
            setPassword("");
            setCode("");
            setBusy(false);
        }
    }

    async function usePasskey() {
        setBusy(true);
        setError("");
        try {
            await signInWithPasskey();
        } catch (failure) {
            setError(
                failure.message ||
                    "Passkey sign-in was cancelled or could not finish.",
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="min-h-[70vh] flex items-center justify-center py-12 md:py-24">
            <div className="w-full max-w-md rounded-3xl bg-neutral-400/85 backdrop-blur-md shadow-xl p-6 md:p-10 space-y-6 md:space-y-10 text-neutral-900">
                <div className="text-center">
                    <Link
                        to="/"
                        className="inline-block hover:opacity-80 transition-opacity"
                    >
                        <Logo size="md" variant="subtle" stacked />
                    </Link>
                </div>
                <div className="space-y-2 text-center">
                    <h1 className="text-2xl md:text-3xl font-serif tracking-tight">
                        {challenge ? "Confirm your sign-in" : "Author Access"}
                    </h1>
                    <p className="text-sm">
                        {challenge
                            ? "Enter your authenticator code or a recovery code."
                            : "Sign in to manage your literary world."}
                    </p>
                </div>
                <form onSubmit={submit} className="space-y-5">
                    {challenge ? (
                        <FormField
                            htmlFor="login-code"
                            label={
                                recovery
                                    ? "Recovery code"
                                    : "Authenticator code"
                            }
                        >
                            <Input
                                id="login-code"
                                autoComplete="one-time-code"
                                inputMode={recovery ? "text" : "numeric"}
                                value={code}
                                onChange={(event) =>
                                    setCode(event.target.value)
                                }
                                required
                                autoFocus
                                disabled={busy}
                            />
                        </FormField>
                    ) : (
                        <>
                            <FormField htmlFor="login-email" label="Email">
                                <Input
                                    id="login-email"
                                    name="email"
                                    type="email"
                                    autoComplete="username webauthn"
                                    value={email}
                                    onChange={(event) =>
                                        setEmail(event.target.value)
                                    }
                                    required
                                    disabled={busy}
                                />
                            </FormField>
                            <FormField
                                htmlFor="login-password"
                                label="Password"
                            >
                                <Input
                                    id="login-password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    value={password}
                                    onChange={(event) =>
                                        setPassword(event.target.value)
                                    }
                                    required
                                    disabled={busy}
                                />
                            </FormField>
                        </>
                    )}
                    {(error || sessionError) && (
                        <p role="alert" className="text-sm text-red-950">
                            {error || sessionError}
                        </p>
                    )}
                    <Button
                        type="submit"
                        className="w-full"
                        disabled={busy || sessionLoading}
                    >
                        {busy
                            ? "Signing in…"
                            : challenge
                              ? "Confirm sign-in"
                              : "Sign in"}
                    </Button>
                </form>
                {challenge ? (
                    <div className="flex flex-wrap gap-3">
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setRecovery(!recovery);
                                setCode("");
                                setError("");
                            }}
                            disabled={busy}
                        >
                            {recovery
                                ? "Use authenticator"
                                : "Use recovery code"}
                        </Button>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setChallenge(false);
                                setCode("");
                                setError("");
                            }}
                            disabled={busy}
                        >
                            Back to sign-in
                        </Button>
                    </div>
                ) : (
                    features.passkeys &&
                    Passkeys.isSupported() && (
                        <Button
                            variant="subtle"
                            className="w-full"
                            onClick={usePasskey}
                            disabled={busy || sessionLoading}
                        >
                            Sign in with a passkey
                        </Button>
                    )
                )}
            </div>
        </div>
    );
}
