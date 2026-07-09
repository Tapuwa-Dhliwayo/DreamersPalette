export default function DashboardSkeleton({ rows = 3 }) {
    return (
        <div className="space-y-4" aria-label="Loading content" aria-busy="true">
            {Array.from({ length: rows }, (_, index) => (
                <div
                    key={index}
                    className="h-24 animate-pulse rounded-2xl bg-neutral-200/70"
                />
            ))}
        </div>
    )
}
