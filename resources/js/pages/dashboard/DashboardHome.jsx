import { useEffect, useState } from "react"
import Button from "@/components/ui/Button"
import Select from "@/components/ui/Select"
import StatusMessage from "@/components/ui/StatusMessage"
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton"
import WritingActivityChart from "@/components/dashboard/WritingActivityChart"
import { getDashboard, saveWritingGoal } from "@/services/dashboard"
import { useAuth } from "@/hooks/useAuth"
import { can } from "@/services/authService"
import { formatDate } from "@/utils/formatDate"
import { formatNumber } from "@/utils/formatNumber"

function ResumeList({ resume }) {
    return (
        <section>
            <h3>Resume your work</h3>
            <div className="dashboard-resume">
                {resume.map((work) => (
                    <a key={`${work.type}-${work.url}`} href={work.url}>
                        <span>
                            <strong>{work.title}</strong>
                            <small>{work.type} · {work.detail}</small>
                        </span>
                        <span className="dashboard-resume-date">
                            {formatDate(work.updated_at)}{" "}
                            <svg aria-hidden="true" width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5"><path d="M4 12 12 4M4 4h8v8" /></svg>
                        </span>
                    </a>
                ))}
                {resume.length === 0 && <p>No recent work yet. Create a manuscript or poem to begin.</p>}
            </div>
        </section>
    )
}

function AttentionList({ attention }) {
    return (
        <section className="dashboard-attention">
            <h3>Needs attention</h3>
            {attention.length ? attention.map((entry) => (
                <a key={entry.url} href={entry.url}>
                    <strong>{entry.title}</strong>
                    <small>{entry.detail} →</small>
                </a>
            )) : <p>No releases or editorial suggestions waiting for review.</p>}
        </section>
    )
}

function WritingTotals({ writing }) {
    const periods = [["Today", writing.today], ["This week", writing.week], ["This month", writing.month]]

    return (
        <dl className="dashboard-writing-totals">
            {periods.map(([label, totals]) => (
                <div key={label}>
                    <dt>{label}</dt>
                    <dd>
                        <strong>{formatNumber(totals.added)}</strong> words added
                        <small>{formatNumber(totals.removed)} removed · {formatNumber(totals.net)} net · {totals.active_days} {totals.active_days === 1 ? "writing day" : "writing days"}</small>
                    </dd>
                </div>
            ))}
        </dl>
    )
}

function ChapterCounts({ chapters }) {
    const longest = Math.max(1, ...chapters.map((entry) => entry.word_count))

    return (
        <section>
            <h4>Chapter word counts</h4>
            <div className="dashboard-chapter-counts">
                {chapters.map((chapter, index) => (
                    <div key={chapter.id}>
                        <span>{index + 1}. {chapter.title}</span>
                        <strong>{formatNumber(chapter.word_count)}</strong>
                        <meter min="0" max={longest} value={chapter.word_count} aria-label={`${chapter.title}: ${formatNumber(chapter.word_count)} words`} />
                    </div>
                ))}
                {!chapters.length && <p>No chapters yet.</p>}
            </div>
        </section>
    )
}

function WritingGoal({ project, weekAdded, saving, loading, error, notice, onSubmit }) {
    return (
        <section>
            <h4>Weekly writing goal</h4>
            {project.weekly_word_goal && (
                <>
                    <p>{formatNumber(weekAdded)} of {formatNumber(project.weekly_word_goal)} words added</p>
                    <progress max={project.weekly_word_goal} value={Math.min(weekAdded, project.weekly_word_goal)} aria-label="Weekly words added goal" />
                </>
            )}
            {project.can_update && (
                <form key={`${project.id}-${project.weekly_word_goal}`} onSubmit={onSubmit} className="dashboard-goal">
                    <label htmlFor="weekly-writing-goal">Words to add per week</label>
                    <div>
                        <input
                            id="weekly-writing-goal"
                            name="weekly_word_goal"
                            type="number"
                            min="1"
                            max="1000000"
                            defaultValue={project.weekly_word_goal || ""}
                            placeholder="No goal"
                            disabled={saving}
                        />
                        <Button variant="subtle" type="submit" disabled={saving || loading}>{saving ? "Saving…" : "Save goal"}</Button>
                    </div>
                    <small>Leave blank to remove the goal. Weeks start on Monday.</small>
                </form>
            )}
            <StatusMessage tone="error">{error}</StatusMessage>
            <p role="status">{notice}</p>
        </section>
    )
}

function ManuscriptList({ manuscripts }) {
    return (
        <section>
            <h3>Active manuscripts</h3>
            <div className="dashboard-manuscripts">
                {manuscripts.map((manuscript) => (
                    <a key={manuscript.id} href={manuscript.url}>
                        <div>
                            <strong>{manuscript.title}</strong>
                            <small>{manuscript.world || "No linked world"} · Draft {manuscript.draft_number}</small>
                        </div>
                        <div>
                            <strong>{formatNumber(manuscript.words)} words</strong>
                            <small>{manuscript.chapters.length} chapters · {manuscript.finished_drafts} finished drafts</small>
                        </div>
                    </a>
                ))}
                {!manuscripts.length && <p>No active manuscripts.</p>}
            </div>
        </section>
    )
}

function Milestones({ activity }) {
    return (
        <section>
            <h3>Recent milestones</h3>
            {activity.length ? activity.map((entry) => (
                <a className="dashboard-milestone" key={`${entry.detail}-${entry.at}-${entry.url}`} href={entry.url}>
                    <span>
                        <strong>{entry.title}</strong>
                        <small>{entry.detail}</small>
                    </span>
                    <time>{formatDate(entry.at)}</time>
                </a>
            )) : <p>Finished drafts and published releases will appear here.</p>}
        </section>
    )
}

function PublishingOverview({ publishing }) {
    return (
        <section>
            <h3>Publishing overview</h3>
            <dl className="dashboard-publishing">
                {publishing.map((entry) => (
                    <div key={entry.kind}>
                        <dt><a href={`/dashboard/${entry.kind}`}>{entry.label}</a></dt>
                        <dd>{entry.published} published · {entry.total - entry.published} unpublished</dd>
                    </div>
                ))}
            </dl>
        </section>
    )
}

export default function DashboardHome() {
    const { user } = useAuth()
    const [overview, setOverview] = useState(null)
    const [days, setDays] = useState("30")
    const [manuscriptId, setManuscriptId] = useState("")
    const [chart, setChart] = useState("activity")
    const [retry, setRetry] = useState(0)
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState("")
    const [goalError, setGoalError] = useState("")
    const [goalNotice, setGoalNotice] = useState("")
    const [savingGoal, setSavingGoal] = useState(false)

    useEffect(() => {
        let active = true
        getDashboard({ days, manuscriptId, timezone: Intl.DateTimeFormat().resolvedOptions().timeZone })
            .then((nextOverview) => {
                if (active) {
                    setOverview(nextOverview)
                    setError("")
                }
            })
            .catch((failure) => { if (active) setError(failure.message) })
            .finally(() => { if (active) setLoading(false) })
        return () => { active = false }
    }, [days, manuscriptId, retry])

    function filter(setter, next) {
        setLoading(true)
        setGoalNotice("")
        setGoalError("")
        setter(next)
    }

    async function updateGoal(event) {
        event.preventDefault()
        const submitted = new FormData(event.currentTarget).get("weekly_word_goal")
        setSavingGoal(true)
        setGoalError("")
        setGoalNotice("")
        try {
            const saved = await saveWritingGoal(manuscriptId, submitted === "" ? null : Number(submitted))
            setOverview((current) => ({
                ...current,
                manuscripts: current.manuscripts.map((project) => (
                    String(project.id) === manuscriptId ? { ...project, weekly_word_goal: saved.weekly_word_goal } : project
                )),
            }))
            setGoalNotice(saved.weekly_word_goal ? "Weekly goal saved." : "Weekly goal removed.")
        } catch (failure) {
            setGoalError(failure.message)
        } finally {
            setSavingGoal(false)
        }
    }

    const project = overview?.manuscripts.find((manuscript) => String(manuscript.id) === manuscriptId)
    const createLinks = [
        { permission: "manuscripts.create", label: "Manuscript", url: "/studio" },
        { permission: "poems.create", label: "Poem", url: "/dashboard/poems/new" },
        { permission: "collections.create", label: "Collection", url: "/dashboard/collections/new" },
        { permission: "books.create", label: "Novel", url: "/dashboard/books/new" },
        { permission: "worlds.create", label: "World", url: "/studio/worlds" },
    ].filter((link) => can(user, link.permission))

    return (
        <div className="author-dashboard min-h-0 flex-1 overflow-y-auto">
            <header className="author-dashboard-heading">
                <h2>Your writing desk</h2>
                {createLinks.length > 0 && (
                    <details className="dashboard-create">
                        <summary>＋ Create</summary>
                        <nav aria-label="Create writing">
                            {createLinks.map((link) => <a key={link.label} href={link.url}>{link.label}</a>)}
                        </nav>
                    </details>
                )}
            </header>
            <StatusMessage
                tone="error"
                action={error ? <Button variant="ghost" onClick={() => { setLoading(true); setRetry((count) => count + 1) }}>Try again</Button> : null}
            >
                {error}
            </StatusMessage>
            {!overview && loading && <DashboardSkeleton />}
            {overview && (
                <>
                    <div className="dashboard-desk-grid">
                        <ResumeList resume={overview.resume} />
                        <AttentionList attention={overview.attention} />
                    </div>
                    {!error && (overview.manuscripts.length > 0 ? (
                        <section className="dashboard-writing" aria-busy={loading}>
                            <div className="dashboard-section-heading">
                                <h3>Writing activity</h3>
                                <div className="dashboard-filters">
                                    <label>
                                        Manuscript
                                        <Select aria-label="Manuscript" value={manuscriptId} disabled={savingGoal} onChange={(event) => filter(setManuscriptId, event.target.value)}>
                                            <option value="">All manuscripts</option>
                                            {overview.manuscripts.map((manuscript) => <option key={manuscript.id} value={manuscript.id}>{manuscript.title}</option>)}
                                        </Select>
                                    </label>
                                    <label>
                                        Period
                                        <Select aria-label="Period" value={days} disabled={savingGoal} onChange={(event) => filter(setDays, event.target.value)}>
                                            <option value="7">7 days</option>
                                            <option value="30">30 days</option>
                                            <option value="90">90 days</option>
                                        </Select>
                                    </label>
                                </div>
                            </div>
                            {loading && <p role="status">Updating writing activity…</p>}
                            <WritingTotals writing={overview.writing} />
                            <div className="dashboard-chart-modes" role="group" aria-label="Writing chart">
                                <button type="button" aria-pressed={chart === "activity"} onClick={() => setChart("activity")}>Words added & removed</button>
                                <button type="button" aria-pressed={chart === "growth"} onClick={() => setChart("growth")}>Manuscript size</button>
                            </div>
                            <WritingActivityChart series={overview.writing.series} growth={chart === "growth"} />
                            <p className="dashboard-tracking-note">
                                {overview.writing.tracking_since ? `Recorded since ${overview.writing.tracking_since}.` : "Activity starts with your next manuscript save."} Counts compare words between saved versions, including pasted or assisted text. Moving existing words is excluded; restoration and archiving affect size only. Days use {overview.writing.timezone}.
                            </p>
                            {project && (
                                <div className="dashboard-project-detail">
                                    <ChapterCounts chapters={project.chapters} />
                                    <WritingGoal
                                        project={project}
                                        weekAdded={overview.writing.week.added}
                                        saving={savingGoal}
                                        loading={loading}
                                        error={goalError}
                                        notice={goalNotice}
                                        onSubmit={updateGoal}
                                    />
                                </div>
                            )}
                        </section>
                    ) : (
                        <section className="dashboard-writing">
                            <h3>Manuscript activity</h3>
                            <p>Create a manuscript in the writing studio to track chapter counts, daily writing and manuscript growth.</p>
                            {can(user, "manuscripts.view") && <a href="/studio">Open writing studio →</a>}
                        </section>
                    ))}
                    <ManuscriptList manuscripts={overview.manuscripts} />
                    <div className="dashboard-desk-grid dashboard-bottom">
                        <Milestones activity={overview.recent_activity} />
                        <PublishingOverview publishing={overview.publishing} />
                    </div>
                </>
            )}
        </div>
    )
}
