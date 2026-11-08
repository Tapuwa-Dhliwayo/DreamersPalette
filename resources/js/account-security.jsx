import { createRoot } from "react-dom/client";
import SecuritySettings from "@/components/auth/SecuritySettings";

// Account lives in the studio shell; the existing React 2FA and passkey settings mount here unchanged.
const root = document.getElementById("account-security");
if (root) createRoot(root).render(<SecuritySettings />);
