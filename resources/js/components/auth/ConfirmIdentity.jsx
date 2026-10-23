import { useState } from "react";
import Button from "@/components/ui/Button";
import Input from "@/components/ui/Input";
import FormField from "@/components/ui/FormField";
import { Passkeys } from "@laravel/passkeys";
import {
    confirmPassword,
    confirmWithPasskey,
} from "@/services/securityService";

export default function ConfirmIdentity({ settings, busy, perform }) {
    const [password, setPassword] = useState("");
    function unlock(event) {
        event.preventDefault();
        return perform(
            "confirm",
            async () => {
                try {
                    await confirmPassword(password);
                } finally {
                    setPassword("");
                }
            },
            "Password confirmed for this session.",
        );
    }

    return (
        <section
            className="space-y-4 border-b border-neutral-200 pb-6"
            aria-labelledby="confirm-security"
        >
            <h2 id="confirm-security" className="text-lg font-medium">
                Confirm your identity
            </h2>
            <p className="text-sm text-neutral-600">
                Confirm your current password or an existing passkey before
                changing sign-in methods or viewing recovery codes. Changing the
                administrator password is managed through server configuration.
            </p>
            {settings.password_confirmed ? (
                <p className="text-sm text-green-800">
                    Identity confirmed for this session.
                </p>
            ) : (
                <>
                    <form
                        onSubmit={unlock}
                        className="flex flex-col items-start gap-3 sm:flex-row sm:items-end"
                    >
                        <FormField
                            htmlFor="security-password"
                            label="Current password"
                        >
                            <Input
                                id="security-password"
                                type="password"
                                autoComplete="current-password"
                                value={password}
                                onChange={(event) =>
                                    setPassword(event.target.value)
                                }
                                required
                                disabled={!!busy}
                            />
                        </FormField>
                        <Button type="submit" disabled={!!busy}>
                            Confirm password
                        </Button>
                    </form>
                    {settings.passkeys_available &&
                        settings.passkeys.length > 0 &&
                        Passkeys.isSupported() && (
                            <Button
                                variant="subtle"
                                disabled={!!busy}
                                onClick={() =>
                                    perform(
                                        "confirm-passkey",
                                        confirmWithPasskey,
                                        "Passkey confirmed for this session.",
                                    )
                                }
                            >
                                Confirm with a passkey
                            </Button>
                        )}
                </>
            )}
        </section>
    );
}
