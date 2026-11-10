import { apiRequest } from "@/services/_shared/api";

const root = (poem) => `/api/studio/poem-analysis/${poem}`;
const conversationPath = (poem, conversation) => `${root(poem)}/conversations/${conversation}`;
export const getAnalysis = (poem) => apiRequest(root(poem));
export const createConversation = (poem, title) => apiRequest(`${root(poem)}/conversations`, { method: "POST", body: { title } });
export const getConversation = (poem, conversation) => apiRequest(conversationPath(poem, conversation));
export const archiveConversation = (poem, conversation) => apiRequest(conversationPath(poem, conversation), { method: "DELETE" });
export const restoreConversation = (poem, conversation) => apiRequest(`${conversationPath(poem, conversation)}/restore`, { method: "POST" });
export const getModels = (connection, refresh = false) => apiRequest(`/api/studio/poem-analysis/connections/${connection}/models?refresh=${refresh ? 1 : 0}`);
export const sendMessage = (poem, conversation, body) => apiRequest(`${conversationPath(poem, conversation)}/messages`, { method: "POST", body });
export const stopTurn = (poem, conversation, turn) => apiRequest(`${conversationPath(poem, conversation)}/turns/${turn}/stop`, { method: "POST" });
export const retryTurn = (poem, conversation, turn, requestUuid) => apiRequest(`${conversationPath(poem, conversation)}/turns/${turn}/retry`, { method: "POST", body: { request_uuid: requestUuid } });
export const saveAuthorNote = (poem, body) => apiRequest(`${root(poem)}/notes`, { method: "POST", body });
export const restoreAuthorNote = (poem, note) => apiRequest(`${root(poem)}/notes/${note}/restore`, { method: "POST" });
export const retireAuthorNote = (poem, note) => apiRequest(`${root(poem)}/notes/${note}`, { method: "DELETE" });
export const previewImport = (poem, markdown) => apiRequest(`${root(poem)}/imports/preview`, { method: "POST", body: { markdown } });
export const saveImport = (poem, importId) => apiRequest(`${root(poem)}/imports/${importId}/save`, { method: "POST" });
export const discardImport = (poem, importId) => apiRequest(`${root(poem)}/imports/${importId}`, { method: "DELETE" });

export const getAssistantActivity = () => apiRequest("/api/studio/assistant-activity");
export const acknowledgeActivity = (turn) => apiRequest(`/api/studio/assistant-activity/${turn}/acknowledge`, { method: "POST" });
