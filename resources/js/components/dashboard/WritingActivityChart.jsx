import { useState } from "react"
import { formatNumber } from "@/utils/formatNumber"

// The readout spells the change out; the daily list keeps it compact.
function describeDay(day, growth, compact = false) {
    if (growth) return day.total === null ? "Not recorded" : `${formatNumber(day.total)} words`
    if (day.added === null) return "Not recorded"
    return compact
        ? `+${formatNumber(day.added)} / −${formatNumber(day.removed)}`
        : `${formatNumber(day.added)} added · ${formatNumber(day.removed)} removed`
}

export default function WritingActivityChart({ series, growth = false }) {
    const [inspectedDate, setInspectedDate] = useState(null)
    const inspected = series.find((day) => day.date === inspectedDate) || series.at(-1)
    const known = series.filter((day) => day.total !== null)
    const maximum = Math.max(1, ...series.map((day) => growth ? day.total || 0 : Math.max(day.added, day.removed)))
    const points = known.map((day) => `${series.length > 1 ? series.indexOf(day) / (series.length - 1) * 1000 : 500},${150 - day.total / maximum * 135}`).join(" ")

    return (
        <section className="writing-chart" aria-label={growth ? "Manuscript size over time" : "Daily words added and removed"}>
            <div className="writing-chart-readout" aria-live="polite">
                {inspected && (
                    <>
                        <span>{inspected.date}</span>
                        <strong>{describeDay(inspected, growth)}</strong>
                    </>
                )}
            </div>
            {growth ? (
                <div className="writing-growth">
                    <span className="writing-chart-scale">{formatNumber(maximum)} words</span>
                    {known.length > 0 ? (
                        <svg
                            viewBox="0 0 1000 170"
                            preserveAspectRatio="none"
                            role="img"
                            aria-label={`Manuscript size: ${formatNumber(known[0].total)} to ${formatNumber(known.at(-1).total)} words across recorded days`}
                        >
                            <line x1="0" y1="150" x2="1000" y2="150" stroke="var(--author-line)" />
                            <polyline points={points} fill="none" stroke="var(--author-accent)" strokeWidth="3" vectorEffect="non-scaling-stroke" />
                            {known.length === 1 && (
                                <circle
                                    cx={series.length > 1 ? series.indexOf(known[0]) / (series.length - 1) * 1000 : 500}
                                    cy={150 - known[0].total / maximum * 135}
                                    r="4"
                                    fill="var(--author-accent)"
                                />
                            )}
                        </svg>
                    ) : <p>No manuscript size history yet.</p>}
                </div>
            ) : (
                <>
                    <div className="writing-chart-legend">
                        <span><i />Added</span>
                        <span><i />Removed</span>
                        <small>{series.some((day) => day.added || day.removed) ? `Up to ${formatNumber(maximum)} words/day` : "No saved word changes in this period"}</small>
                    </div>
                    <div className="writing-bars" style={{ gridTemplateColumns: `repeat(${series.length}, minmax(0, 1fr))` }}>
                        {series.map((day) => (
                            <button
                                key={day.date}
                                type="button"
                                aria-label={day.added === null ? `${day.date}: not recorded` : `${day.date}: ${day.added} words added, ${day.removed} removed`}
                                onFocus={() => setInspectedDate(day.date)}
                                onMouseEnter={() => setInspectedDate(day.date)}
                                onClick={() => setInspectedDate(day.date)}
                            >
                                <span className="writing-bar-added" style={{ height: `${day.added / maximum * 100}%` }} />
                                <span className="writing-bar-removed" style={{ height: `${day.removed / maximum * 100}%` }} />
                            </button>
                        ))}
                    </div>
                </>
            )}
            <div className="writing-chart-dates"><span>{series[0]?.date}</span><span>{series.at(-1)?.date}</span></div>
            <details className="writing-chart-data">
                <summary>View daily figures</summary>
                <div className="writing-daily-list">
                    {series.map((day) => (
                        <div key={day.date}>
                            <time>{day.date}</time>
                            <span>{describeDay(day, growth, true)}</span>
                        </div>
                    ))}
                </div>
            </details>
        </section>
    )
}
