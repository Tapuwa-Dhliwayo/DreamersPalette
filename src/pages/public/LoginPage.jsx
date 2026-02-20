import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { supabase } from "@/services/supabaseClient";

export default function LoginPage() {
    const navigate = useNavigate();

    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        supabase.auth.getSession().then(({ data }) => {
            if (data.session) {
                navigate("/dashboard");
            }
        });
    }, [navigate]);

    async function handleLogin(e) {
        e.preventDefault();
        setLoading(true);

        const { error } = await supabase.auth.signInWithPassword({
            email,
            password,
        });

        setLoading(false);

        if (error) {
            alert("Invalid credentials");
            return;
        }

        navigate("/dashboard");
    }

    return (
        <div className="max-w-md mx-auto mt-24 space-y-8">
            <div className="space-y-2 text-center">
                <h1 className="text-3xl font-semibold tracking-tight">
                    Author Access
                </h1>
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                    Sign in to manage your literary world.
                </p>
            </div>

            <form onSubmit={handleLogin} className="space-y-4">
                <input
                    type="email"
                    placeholder="Email"
                    className="w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-transparent px-4 py-2 outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                />

                <input
                    type="password"
                    placeholder="Password"
                    className="w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-transparent px-4 py-2 outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                />

                <button
                    type="submit"
                    disabled={loading}
                    className="w-full rounded-xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 py-2 font-medium transition-opacity disabled:opacity-50"
                >
                    {loading ? "Signing in…" : "Sign In"}
                </button>
            </form>
        </div>
    );
}