import { useEffect } from "react";

export default function Modal({ open, onClose, children }) {
    useEffect(() => {
        document.body.style.overflow = open ? "hidden" : "auto";
    }, [open]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div className="bg-white dark:bg-neutral-900 rounded-2xl shadow-md max-w-lg w-full p-6 relative">
                {children}
            </div>
            <div
                className="absolute inset-0"
                onClick={onClose}
            />
        </div>
    );
}