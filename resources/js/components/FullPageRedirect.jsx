import { useEffect } from "react";

// Hands a path served by the studio shell back to the server instead of the React router.
export default function FullPageRedirect({ to }) {
    useEffect(() => {
        if (to) window.location.replace(to);
        else window.location.reload();
    }, [to]);

    return <p role="status" className="p-6 text-sm">Opening your account…</p>;
}
