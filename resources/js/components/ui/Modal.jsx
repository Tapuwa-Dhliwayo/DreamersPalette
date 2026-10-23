import { useEffect } from "react";
import { createPortal } from "react-dom";
import clsx from "clsx";

export default function Modal({ open, onClose, children, className }) {
    useEffect(() => {
        document.body.style.overflow = open ? "hidden" : "auto";
        return () => {
            document.body.style.overflow = "auto";
        };
    }, [open]);

    if (!open) return null;

    return createPortal(
        <div className="fixed inset-0 z-50 flex items-center justify-center">

            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Modal Content */}
            <div
                className={clsx(
                    "relative bg-white rounded-none md:rounded-3xl shadow-xl w-full h-full md:h-auto md:max-h-[85vh] p-6 md:p-8 overflow-y-auto",
                    className || "md:max-w-lg"
                )}
            >
                {children}
            </div>

        </div>,
        document.body
    );
}
