export default function PublishingEmptyState({ children, className = "" }) {
    return (
        <section className={`publishing-empty ${className}`}>
            <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.25"><path d="M4 4h6a3 3 0 0 1 2 1 3 3 0 0 1 2-1h6v15h-6a3 3 0 0 0-2 1 3 3 0 0 0-2-1H4zM12 5v15" /></svg>
            {children}
        </section>
    )
}
