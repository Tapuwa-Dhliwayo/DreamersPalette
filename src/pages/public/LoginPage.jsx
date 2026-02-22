import { useState, useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import { supabase } from "@/services/supabaseClient";
import Logo from "@/components/ui/Logo.jsx";
import { PUBLIC_ROUTES, DASHBOARD_ROUTES } from "@/app/routes"

export default function LoginPage() {
    const navigate = useNavigate();

    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        supabase.auth.getSession().then(({ data }) => {
            if (data.session) {
                navigate(DASHBOARD_ROUTES.ROOT);
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

        navigate(DASHBOARD_ROUTES.ROOT);
    }

    return (
        <div className="min-h-[70vh] flex items-center justify-center py-24">

            <div className="w-full max-w-md rounded-3xl bg-neutral-400/85 dark:bg-neutral-900/80 backdrop-blur-md shadow-xl p-10 space-y-10">

                {/* Logo */}
                <div className="text-center">
                    <Link
                        to={PUBLIC_ROUTES.HOME}
                        className="inline-block hover:opacity-80 transition-opacity"
                    >
                        <Logo size="md" variant="subtle" stacked />
                    </Link>
                </div>

                {/* Heading */}
                <div className="space-y-2 text-center">
                    <h1 className="text-2xl font-serif tracking-tight">
                        Author Access
                    </h1>
                    <p className="text-sm opacity-60">
                        Sign in to manage your literary world.
                    </p>
                </div>

                {/* Form */}
                <form onSubmit={handleLogin} className="space-y-5">

                    <div className="space-y-2">
                        <input
                            type="email"
                            placeholder="Email"
                            className="w-full rounded-xl bg-transparent border border-neutral-300/60 dark:border-neutral-700/60 px-4 py-2 outline-none transition-all focus:border-[var(--accent-color)] focus:ring-2 focus:ring-[var(--accent-color)]/30"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                        />
                    </div>

                    <div className="space-y-2">
                        <input
                            type="password"
                            placeholder="Password"
                            className="w-full rounded-xl bg-transparent border border-neutral-300/60 dark:border-neutral-700/60 px-4 py-2 outline-none transition-all focus:border-[var(--accent-color)] focus:ring-2 focus:ring-[var(--accent-color)]/30"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full rounded-xl border border-[var(--accent-color)] text-sm font-medium py-2 transition-all duration-200 hover:bg-[var(--accent-color)]/15 disabled:opacity-50"
                    >
                        {loading ? "Signing in…" : "Sign In"}
                    </button>

                </form>

            </div>

        </div>
    );
}