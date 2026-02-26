import { useEffect } from "react";

export default function Modal({ open, onClose, children }) {
    useEffect(() => {
        document.body.style.overflow = open ? "hidden" : "auto";
        return () => {
            document.body.style.overflow = "auto";
        };
    }, [open]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center md:items-center md:justify-center">

            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Modal Content */}
            <div className="relative bg-white dark:bg-neutral-900 rounded-none md:rounded-3xl shadow-xl w-full h-full md:h-auto md:max-w-lg p-6 md:p-8 overflow-y-auto">
                {children}
            </div>

        </div>
    );
}