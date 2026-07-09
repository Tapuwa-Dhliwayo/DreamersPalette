export default function FormField({ label, htmlFor, hint, error, required = false, children }) {
    const descriptionId = `${htmlFor}-description`

    return (
        <div className="space-y-2">
            <label htmlFor={htmlFor} className="block text-sm font-medium text-neutral-700">
                {label}
                {required && <span className="text-red-600" aria-hidden="true"> *</span>}
            </label>
            {children}
            {(hint || error) && (
                <p
                    id={descriptionId}
                    className={`text-xs leading-relaxed ${error ? "text-red-700" : "text-neutral-600"}`}
                >
                    {error || hint}
                </p>
            )}
        </div>
    )
}
