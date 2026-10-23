import { apiRequest } from "./_shared/api";

const query = (params) => new URLSearchParams(params).toString();

export const getMyCollections = () => apiRequest("/api/studio/collections");
export const getMyCollectionById = (id) => apiRequest(`/api/studio/collections/${id}`);
export const createCollection = (body) => apiRequest("/api/studio/collections", { method: "POST", body });
export const updateCollection = (id, body) => apiRequest(`/api/studio/collections/${id}`, { method: "PATCH", body });
export const archiveCollection = (id) => apiRequest(`/api/studio/collections/${id}`, { method: "DELETE" });
export const restoreCollection = (id) => apiRequest(`/api/studio/collections/${id}/restore`, { method: "POST" });
export const getArchivedCollections = () => apiRequest("/api/studio/collections?archived=1");

export const getMyPoems = () => apiRequest("/api/studio/poems");
export const getMyPoemById = (id) => apiRequest(`/api/studio/poems/${id}`);
export const createPoem = (body) => apiRequest("/api/studio/poems", { method: "POST", body });
export const updatePoem = (id, body) => apiRequest(`/api/studio/poems/${id}`, { method: "PATCH", body });
export const archivePoem = (id) => apiRequest(`/api/studio/poems/${id}`, { method: "DELETE" });
export const restorePoem = (id) => apiRequest(`/api/studio/poems/${id}/restore`, { method: "POST" });
export const getArchivedPoems = () => apiRequest("/api/studio/poems?archived=1");

export const togglePublish = (id, is_published) => apiRequest(`/api/studio/collections/${id}/publish`, { method: "PATCH", body: { is_published } });
export const togglePoemPublish = (id, is_published) => apiRequest(`/api/studio/poems/${id}/publish`, { method: "PATCH", body: { is_published } });
export const getPoemsByCollection = (collection_id) => apiRequest(`/api/studio/poems?${query({ collection_id })}`);
export async function getPoemCountsByCollection() {
    const collections = await getMyCollections();
    return Object.fromEntries(collections.map((collection) => [collection.id, { total: collection.poems_count, published: collection.published_poems_count }]));
}
export const getPublishedCollections = () => apiRequest("/api/reader/collections");
export const getPublishedCollectionsPaginated = (page = 1, pageSize = 12) => apiRequest(`/api/reader/collections?${query({ page, pageSize })}`);
export const getCollectionBySlug = (slug) => apiRequest(`/api/reader/collections/${encodeURIComponent(slug)}`);
export const getCollectionBySlugPreview = (slug) => apiRequest(`/api/studio/collections/preview/${encodeURIComponent(slug)}`);
export const getCollectionById = (id) => apiRequest(`/api/reader/collections/${id}?by_id=1`);
export const getPublishedPoemBySlug = (slug) => apiRequest(`/api/reader/poems/${encodeURIComponent(slug)}`);
export const getRecentlyAddedPoems = (limit = 4) => apiRequest(`/api/reader/poems?${query({ limit })}`);
export const getPublishedPoemsByCollection = (collection) => apiRequest(`/api/reader/poems?${query({ collection })}`);
export const getPublishedPoemsByCollectionPaginated = (collection, page = 1, pageSize = 12) => apiRequest(`/api/reader/poems?${query({ collection, page, pageSize })}`);

export const getPreviewPoemsByCollection = (slug, page = 1, pageSize = 12) => apiRequest(`/api/studio/collections/preview/${encodeURIComponent(slug)}/poems?${query({ page, pageSize })}`);
