import { useEffect, useState } from "react";
import { supabase } from "@/services/supabaseClient";

export function useAuth() {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const { data: listener } = supabase.auth.onAuthStateChange(
            async (_event, session) => {
                setUser(session?.user ?? null);
                setLoading(false);
            }
        );

        supabase.auth.getSession().then(({ data }) => {
            setUser(data.session?.user ?? null);
            setLoading(false);
        });

        return () => listener.subscription.unsubscribe();
    }, []);

    return { user, loading };
}