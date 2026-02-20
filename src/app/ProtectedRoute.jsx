import { useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { getSession } from "@/services/authService";

export default function ProtectedRoute({ children }) {
    const [loading, setLoading] = useState(true);
    const [authenticated, setAuthenticated] = useState(false);

    useEffect(() => {
        async function checkAuth() {
            const session = await getSession();
            setAuthenticated(!!session);
            setLoading(false);
        }
        checkAuth();
    }, []);

    if (loading) return null;

    if (!authenticated) {
        return <Navigate to="/login" replace />;
    }

    return children;
}