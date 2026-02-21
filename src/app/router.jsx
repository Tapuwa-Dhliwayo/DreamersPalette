import { createBrowserRouter } from "react-router-dom";
import { lazy } from "react";

import ReaderLayout from "@/layouts/ReaderLayout";
import DashboardLayout from "@/layouts/DashboardLayout";
import ProtectedRoute from "./ProtectedRoute";

/* ---------- Public Pages ---------- */
const HomePage = lazy(() => import("@/pages/public/HomePage"));
const CollectionsPage = lazy(() => import("@/pages/public/CollectionsPage"));
const CollectionDetailPage = lazy(() => import("@/pages/public/CollectionDetailPage"));
const PoemPage = lazy(() => import("@/pages/public/PoemPage"));
const BooksPage = lazy(() => import("@/pages/public/BooksPage"));
const BookDetailPage = lazy(() => import("@/pages/public/BookDetailPage"));
const ChapterPage = lazy(() => import("@/pages/public/ChapterPage"));
const LoginPage = lazy(() => import("@/pages/public/LoginPage"));

/* ---------- Dashboard Pages ---------- */
const DashboardHome = lazy(() => import("@/pages/dashboard/DashboardHome"));
const DashboardCollections = lazy(() => import("@/pages/dashboard/Collections"));
const DashboardPoems = lazy(() => import("@/pages/dashboard/Poems"));
const DashboardBooks = lazy(() => import("@/pages/dashboard/Books"));
const DashboardChapters = lazy(() => import("@/pages/dashboard/Chapters"));
const DashboardPoemEditor = lazy(() => import("@/pages/dashboard/PoemEditorPage"));

export const router = createBrowserRouter([
    /* ---------- PUBLIC READER ---------- */
    {
        element: <ReaderLayout />,
        children: [
            { path: "/", element: <HomePage /> },
            { path: "/collections", element: <CollectionsPage /> },
            { path: "/collections/:slug", element: <CollectionDetailPage /> },
            { path: "/poems/:slug", element: <PoemPage /> },
            { path: "/books", element: <BooksPage /> },
            { path: "/books/:slug", element: <BookDetailPage /> },
            { path: "/books/:slug/chapter/:number", element: <ChapterPage /> },
            { path: "/login", element: <LoginPage /> },
        ],
    },

    /* ---------- DASHBOARD (PROTECTED) ---------- */
    {
        path: "/dashboard",
        element: (
            <ProtectedRoute>
                <DashboardLayout />
            </ProtectedRoute>
        ),
        children: [
            { index: true, element: <DashboardHome /> },
            { path: "collections", element: <DashboardCollections /> },
            { path: "poems", element: <DashboardPoems /> },
            { path: "poems/new", element: <DashboardPoemEditor /> },
            { path: "poems/:id/edit", element: <DashboardPoemEditor /> },
            { path: "books", element: <DashboardBooks /> },
            { path: "chapters", element: <DashboardChapters /> },
        ],
    },
]);