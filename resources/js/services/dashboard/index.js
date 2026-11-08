import { apiRequest } from "../_shared/api"

export function getDashboard({ days, manuscriptId, timezone }) {
    const query = new URLSearchParams({ days, timezone })
    if (manuscriptId) query.set("manuscript_id", manuscriptId)
    return apiRequest(`/api/studio/dashboard?${query}`)
}

export function saveWritingGoal(manuscriptId, weeklyWordGoal) {
    return apiRequest(`/api/studio/dashboard/manuscripts/${manuscriptId}/goal`, {
        method: "PATCH", body: { weekly_word_goal: weeklyWordGoal },
    })
}
