import { useState } from "react";
import Button from "@/components/ui/Button";
import Input from "@/components/ui/Input";
import FormField from "@/components/ui/FormField";
import {
    confirmTwoFactor,
    disableTwoFactor,
    enableTwoFactor,
    getRecoveryCodes,
    getTwoFactorQrCode,
    regenerateRecoveryCodes,
} from "@/services/securityService";

export default function AuthenticatorSettings({ settings, busy, perform }) {
    const [code, setCode] = useState("");
    const [qr, setQr] = useState(null);
    const [recoveryCodes, setRecoveryCodes] = useState([]);
    function addAuthenticator() {
        return perform(
            "enable",
            async () => {
                await enableTwoFactor();
                setQr(await getTwoFactorQrCode());
                setRecoveryCodes([]);
            },
            "Scan the QR code, then enter a code to finish enabling 2FA.",
        );
    }
    function verifyAuthenticator(event) {
        event.preventDefault();
        return perform(
            "verify",
            async () => {
                await confirmTwoFactor(code);
                setCode("");
                setQr(null);
                setRecoveryCodes(await getRecoveryCodes());
            },
            "Two-factor authentication is enabled. Save your recovery codes privately.",
        );
    }
    function turnOffAuthenticator() {
        return perform(
            "disable",
            async () => {
                await disableTwoFactor();
                setQr(null);
                setRecoveryCodes([]);
                setCode("");
            },
            "Two-factor authentication is off. Password and any registered passkeys remain available.",
        );
    }

    return (
        <section
            className="space-y-4 border-b border-neutral-200 pb-6"
            aria-labelledby="two-factor-title"
        >
            <h2 id="two-factor-title" className="text-lg font-medium">
                Two-factor authentication
            </h2>
            {!settings.two_factor.available ? (
                <p className="text-sm text-neutral-600">
                    This sign-in method is turned off in server configuration.
                </p>
            ) : (
                <>
                    <p className="text-sm text-neutral-600">
                        {settings.two_factor.confirmed
                            ? "Enabled. Password sign-in also requires your authenticator or a recovery code."
                            : settings.two_factor.enrolled
                              ? "Setup is pending. Confirm an authenticator code to enable it."
                              : "Off. Add an authenticator if you want a second check after entering your password."}
                    </p>
                    {!settings.two_factor.enrolled && (
                        <Button
                            disabled={!!busy || !settings.password_confirmed}
                            onClick={addAuthenticator}
                        >
                            Enable 2FA
                        </Button>
                    )}
                    {settings.two_factor.enrolled &&
                        !settings.two_factor.confirmed && (
                            <>
                                {!qr && (
                                    <Button
                                        variant="subtle"
                                        disabled={
                                            !!busy ||
                                            !settings.password_confirmed
                                        }
                                        onClick={() =>
                                            perform(
                                                "qr",
                                                async () =>
                                                    setQr(
                                                        await getTwoFactorQrCode(),
                                                    ),
                                                "Scan the QR code with your authenticator.",
                                            )
                                        }
                                    >
                                        Show setup QR code
                                    </Button>
                                )}
                                {qr?.svg && (
                                    <img
                                        src={`data:image/svg+xml;charset=utf-8,${encodeURIComponent(qr.svg)}`}
                                        width="192"
                                        height="192"
                                        alt="Scan this private setup QR code in your authenticator"
                                        className="rounded-lg bg-white p-2"
                                    />
                                )}
                                <form
                                    onSubmit={verifyAuthenticator}
                                    className="flex flex-col items-start gap-3 sm:flex-row sm:items-end"
                                >
                                    <FormField
                                        htmlFor="security-code"
                                        label="Authenticator code"
                                    >
                                        <Input
                                            id="security-code"
                                            autoComplete="one-time-code"
                                            inputMode="numeric"
                                            value={code}
                                            onChange={(event) =>
                                                setCode(event.target.value)
                                            }
                                            required
                                            disabled={
                                                !!busy ||
                                                !settings.password_confirmed
                                            }
                                        />
                                    </FormField>
                                    <Button
                                        type="submit"
                                        disabled={
                                            !!busy ||
                                            !settings.password_confirmed
                                        }
                                    >
                                        Confirm 2FA
                                    </Button>
                                </form>
                            </>
                        )}
                    {settings.two_factor.enrolled && (
                        <div className="flex flex-wrap gap-3">
                            <Button
                                variant="subtle"
                                disabled={
                                    !!busy || !settings.password_confirmed
                                }
                                onClick={turnOffAuthenticator}
                            >
                                {settings.two_factor.confirmed
                                    ? "Disable 2FA"
                                    : "Cancel setup"}
                            </Button>
                            {settings.two_factor.confirmed && (
                                <>
                                    <Button
                                        variant="subtle"
                                        disabled={
                                            !!busy ||
                                            !settings.password_confirmed
                                        }
                                        onClick={() =>
                                            perform(
                                                "recovery",
                                                async () =>
                                                    setRecoveryCodes(
                                                        await getRecoveryCodes(),
                                                    ),
                                                "Keep recovery codes private and separate from your password.",
                                            )
                                        }
                                    >
                                        Show recovery codes
                                    </Button>
                                    <Button
                                        variant="subtle"
                                        disabled={
                                            !!busy ||
                                            !settings.password_confirmed
                                        }
                                        onClick={() =>
                                            perform(
                                                "regenerate",
                                                async () =>
                                                    setRecoveryCodes(
                                                        await regenerateRecoveryCodes(),
                                                    ),
                                                "Recovery codes replaced. Previous codes no longer work.",
                                            )
                                        }
                                    >
                                        Replace recovery codes
                                    </Button>
                                </>
                            )}
                        </div>
                    )}
                    {recoveryCodes.length > 0 && (
                        <div className="space-y-3">
                            <p className="text-sm text-neutral-700">
                                Save these privately. Each code can be used
                                once.
                            </p>
                            <ul className="grid gap-2 sm:grid-cols-2">
                                {recoveryCodes.map((recoveryCode) => (
                                    <li key={recoveryCode}>
                                        <code className="select-all text-sm">
                                            {recoveryCode}
                                        </code>
                                    </li>
                                ))}
                            </ul>
                            <Button
                                variant="ghost"
                                onClick={() => setRecoveryCodes([])}
                            >
                                Hide recovery codes
                            </Button>
                        </div>
                    )}
                </>
            )}
        </section>
    );
}
