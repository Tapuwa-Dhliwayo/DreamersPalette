import { supabase } from "./supabaseClient";

/* ---------- SESSION ---------- */
export async function getSession() {
    const { data } = await supabase.auth.getSession();
    return data.session;
}

/* ---------- LOGIN ---------- */
export async function signInWithEmail(email, password) {
    const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password,
    });

    if (error) throw error;
    return data;
}

/* ---------- SIGN UP ---------- */
export async function signUp(email, password) {
    const { data, error } = await supabase.auth.signUp({
        email,
        password,
    });

    if (error) throw error;
    return data;
}

/* ---------- LOGOUT ---------- */
export async function signOut() {
    const { error } = await supabase.auth.signOut();
    if (error) throw error;
}

/* ---------- USER ---------- */
export async function getCurrentUser() {
    const { data } = await supabase.auth.getUser();
    return data.user;
}