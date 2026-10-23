import { useSyncExternalStore } from "react";
import { getAuthSnapshot, subscribeAuth } from "@/services/authService";

export function useAuth() {
    return useSyncExternalStore(subscribeAuth, getAuthSnapshot);
}
