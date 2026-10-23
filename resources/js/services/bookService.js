import { apiRequest } from "./_shared/api";

const query = (params) => new URLSearchParams(params).toString();

export const getMyBookById = (id) => apiRequest(`/api/studio/books/${id}`);
export const createBook = (body) => apiRequest("/api/studio/books", { method: "POST", body });
export const updateBook = (id, body) => apiRequest(`/api/studio/books/${id}`, { method: "PATCH", body });
export const archiveBook = (id) => apiRequest(`/api/studio/books/${id}`, { method: "DELETE" });
export const restoreBook = (id) => apiRequest(`/api/studio/books/${id}/restore`, { method: "POST" });
export const getArchivedBooks = () => apiRequest("/api/studio/books?archived=1");
export const toggleBookPublish = (id, is_published) => apiRequest(`/api/studio/books/${id}/publish`, { method: "PATCH", body: { is_published } });

export const getMyChapterById = (id) => apiRequest(`/api/studio/chapters/${id}`);
export const createChapter = (body) => apiRequest("/api/studio/chapters", { method: "POST", body });
export const updateChapter = (id, body) => apiRequest(`/api/studio/chapters/${id}`, { method: "PATCH", body });
export const archiveChapter = (id) => apiRequest(`/api/studio/chapters/${id}`, { method: "DELETE" });
export const restoreChapter = (id) => apiRequest(`/api/studio/chapters/${id}/restore`, { method: "POST" });
export const getArchivedChapters = () => apiRequest("/api/studio/chapters?archived=1");
export const toggleChapterPublish = (id, is_published) => apiRequest(`/api/studio/chapters/${id}/publish`, { method: "PATCH", body: { is_published } });

export const getMyBooks = () => apiRequest("/api/studio/books");
export const getMyChaptersByBook = (book_id) => apiRequest(`/api/studio/chapters?${query({ book_id })}`);
export const getPublishedBooks = () => apiRequest("/api/reader/books");
export const getPublishedBooksPaginated = (page = 1, pageSize = 12) => apiRequest(`/api/reader/books?${query({ page, pageSize })}`);
export const getBookBySlug = (slug) => apiRequest(`/api/reader/books/${encodeURIComponent(slug)}`);
export const getBookBySlugPreview = (slug) => apiRequest(`/api/studio/books/preview/${encodeURIComponent(slug)}`);
export const getPublishedChaptersByBook = (book) => apiRequest(`/api/reader/chapters?${query({ book })}`);
export const getPublishedChaptersByBookPaginated = (book, page = 1, pageSize = 12) => apiRequest(`/api/reader/chapters?${query({ book, page, pageSize })}`);
export const getPublishedChapter = (book, chapterNumber) => apiRequest(`/api/reader/chapters/${encodeURIComponent(chapterNumber)}?${query({ book })}`);
