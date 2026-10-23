import { Navigate } from "react-router-dom";
import { useAuth } from "@/hooks/useAuth";
import { can } from "@/services/authService";

export default function ProtectedRoute({
    children,
    permission = "dashboard.access",
}) {
    const { user, loading, error } = useAuth();

    if (loading)
        return (
            <p role="status" className="p-6 text-sm">
                Checking your session…
            </p>
        );
    if (error)
        return (
            <p role="alert" className="p-6 text-sm">
                {error}
            </p>
        );
    if (!user) return <Navigate to="/login" replace />;
    if (!user.email_verified) return <Navigate to="/verify-email" replace />;
    if (!can(user, permission))
        return (
            <p role="alert" className="p-6 text-sm">
                You do not have permission to open this page.
            </p>
        );

    return children;
}
