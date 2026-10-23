import { getCurrentUser } from "./authService";

export async function getMyProfile() {
    const user = await getCurrentUser();
    if (!user) throw new Error("Your session has ended. Please sign in again.");
    return user.profile ? { ...user.profile, role: user.roles.join(", ") } : null;
}
