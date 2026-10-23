import { useState } from "react";
import Button from "@/components/ui/Button";
import Input from "@/components/ui/Input";
import FormField from "@/components/ui/FormField";
import { Passkeys } from "@laravel/passkeys";
import { registerPasskey, removePasskey } from "@/services/securityService";

export default function PasskeySettings({ settings, busy, perform }) {
    const [passkeyName, setPasskeyName] = useState("");
    function addPasskey(event) {
        event.preventDefault();
        return perform(
            "passkey",
            async () => {
                await registerPasskey(passkeyName);
                setPasskeyName("");
            },
            "Passkey added.",
        );
    }

    return (
        <section className="space-y-4" aria-labelledby="passkeys-title">
            <h2 id="passkeys-title" className="text-lg font-medium">
                Passkeys
            </h2>
            {!settings.passkeys_available ? (
                <p className="text-sm text-neutral-600">
                    Passkey sign-in is turned off in server configuration.
                </p>
            ) : (
                <>
                    <p className="text-sm text-neutral-600">
                        Use your device or security key as an alternative to
                        password sign-in. Each passkey is tied to this website’s
                        hostname.
                    </p>
                    {settings.passkeys.length === 0 ? (
                        <p className="text-sm">No passkeys registered.</p>
                    ) : (
                        <ul className="divide-y divide-neutral-200">
                            {settings.passkeys.map((passkey) => (
                                <li
                                    key={passkey.id}
                                    className="flex items-center justify-between gap-3 py-3"
                                >
                                    <span className="min-w-0 break-words text-sm">
                                        {passkey.name}
                                    </span>
                                    <Button
                                        variant="ghost"
                                        disabled={
                                            !!busy ||
                                            !settings.password_confirmed
                                        }
                                        onClick={() =>
                                            perform(
                                                "remove",
                                                () => removePasskey(passkey.id),
                                                "Passkey removed.",
                                            )
                                        }
                                    >
                                        Remove
                                        <span className="sr-only">
                                            {" "}
                                            {passkey.name}
                                        </span>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                    {Passkeys.isSupported() ? (
                        <form
                            onSubmit={addPasskey}
                            className="flex flex-col items-start gap-3 sm:flex-row sm:items-end"
                        >
                            <FormField
                                htmlFor="passkey-name"
                                label="Passkey name"
                            >
                                <Input
                                    id="passkey-name"
                                    value={passkeyName}
                                    onChange={(event) =>
                                        setPasskeyName(event.target.value)
                                    }
                                    maxLength={255}
                                    required
                                    disabled={
                                        !!busy || !settings.password_confirmed
                                    }
                                    placeholder="My laptop"
                                />
                            </FormField>
                            <Button
                                type="submit"
                                disabled={
                                    !!busy || !settings.password_confirmed
                                }
                            >
                                Add passkey
                            </Button>
                        </form>
                    ) : (
                        <p className="text-sm text-neutral-600">
                            Passkeys require a supported browser on HTTPS or
                            localhost.
                        </p>
                    )}
                </>
            )}
        </section>
    );
}
