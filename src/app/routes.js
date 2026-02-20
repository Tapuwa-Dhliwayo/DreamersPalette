/* ---------- PUBLIC ROUTES ---------- */

export const PUBLIC_ROUTES = {
    HOME: "/",
    COLLECTIONS: "/collections",
    COLLECTION_DETAIL: (slug) => `/collections/${slug}`,
    POEM: (slug) => `/poems/${slug}`,
    BOOKS: "/books",
    BOOK_DETAIL: (slug) => `/books/${slug}`,
    CHAPTER: (slug, number) => `/books/${slug}/chapter/${number}`,
    LOGIN: "/login",
};

/* ---------- DASHBOARD ROUTES ---------- */

export const DASHBOARD_ROUTES = {
    ROOT: "/dashboard",
    COLLECTIONS: "/dashboard/collections",
    POEMS: "/dashboard/poems",
    BOOKS: "/dashboard/books",
    CHAPTERS: "/dashboard/chapters",
};

/* ---------- OPTIONAL: ROUTE META ---------- */

export const ROUTE_META = {
    "/collections": { requiresAuth: false },
    "/dashboard": { requiresAuth: true },
};