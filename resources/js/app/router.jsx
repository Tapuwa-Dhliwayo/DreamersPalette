import { createBrowserRouter } from "react-router-dom";
import { lazy } from "react";

import ReaderLayout from "@/layouts/ReaderLayout";
import DashboardLayout from "@/layouts/DashboardLayout";
import ProtectedRoute from "./ProtectedRoute";

/* ---------- Public Pages ---------- */
const HomePage = lazy(() => import("@/pages/public/HomePage"));
const CollectionsPage = lazy(() => import("@/pages/public/CollectionsPage"));
const CollectionDetailPage = lazy(
    () => import("@/pages/public/CollectionDetailPage"),
);
const PoemPage = lazy(() => import("@/pages/public/PoemPage"));
const BooksPage = lazy(() => import("@/pages/public/BooksPage"));
const BookDetailPage = lazy(() => import("@/pages/public/BookDetailPage"));
const ChapterPage = lazy(() => import("@/pages/public/ChapterPage"));
const LoginPage = lazy(() => import("@/pages/public/LoginPage"));
const VerifyEmailPage = lazy(() => import("@/pages/public/VerifyEmailPage"));
const SecurityPage = lazy(() => import("@/pages/dashboard/SecurityPage"));

/* ---------- Dashboard Pages ---------- */
const DashboardHome = lazy(() => import("@/pages/dashboard/DashboardHome"));
const DashboardCollections = lazy(
    () => import("@/pages/dashboard/Collections"),
);
const DashboardCollectionEditor = lazy(
    () => import("@/pages/dashboard/CollectionEditorPage"),
);
const DashboardPoems = lazy(() => import("@/pages/dashboard/Poems"));
const DashboardBooks = lazy(() => import("@/pages/dashboard/Books"));
const DashboardChapters = lazy(() => import("@/pages/dashboard/Chapters"));
const DashboardPoemEditor = lazy(
    () => import("@/pages/dashboard/PoemEditorPage"),
);
const DashboardBookEditor = lazy(
    () => import("@/pages/dashboard/BookEditorPage"),
);
const DashboardChapterEditor = lazy(
    () => import("@/pages/dashboard/ChapterEditorPage"),
);
const DashboardTrash = lazy(() => import("@/pages/dashboard/TrashPage"));

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
            { path: "/verify-email", element: <VerifyEmailPage /> },
            /* ---------- PREVIEW (AUTH REQUIRED) ---------- */
            {
                path: "/preview/collections/:slug",
                element: (
                    <ProtectedRoute permission="collections.preview">
                        <CollectionDetailPage />
                    </ProtectedRoute>
                ),
            },
        ],
    },

    {
        path: "/dashboard/security",
        element: (
            <ProtectedRoute permission="account.security.manage">
                <DashboardLayout />
            </ProtectedRoute>
        ),
        children: [{ index: true, element: <SecurityPage /> }],
    },
    {
        path: "/user/confirm-password",
        element: (
            <ProtectedRoute permission="account.security.manage">
                <DashboardLayout />
            </ProtectedRoute>
        ),
        children: [{ index: true, element: <SecurityPage /> }],
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
            {
                path: "collections",
                element: (
                    <ProtectedRoute permission="collections.view">
                        <DashboardCollections />
                    </ProtectedRoute>
                ),
            },
            {
                path: "collections/new",
                element: (
                    <ProtectedRoute permission="collections.create">
                        <DashboardCollectionEditor />
                    </ProtectedRoute>
                ),
            },
            {
                path: "collections/:id/edit",
                element: (
                    <ProtectedRoute permission="collections.update">
                        <DashboardCollectionEditor />
                    </ProtectedRoute>
                ),
            },
            {
                path: "poems",
                element: (
                    <ProtectedRoute permission="poems.view">
                        <DashboardPoems />
                    </ProtectedRoute>
                ),
            },
            {
                path: "poems/new",
                element: (
                    <ProtectedRoute permission="poems.create">
                        <DashboardPoemEditor />
                    </ProtectedRoute>
                ),
            },
            {
                path: "poems/:id/edit",
                element: (
                    <ProtectedRoute permission="poems.update">
                        <DashboardPoemEditor />
                    </ProtectedRoute>
                ),
            },
            {
                path: "books",
                element: (
                    <ProtectedRoute permission="books.view">
                        <DashboardBooks />
                    </ProtectedRoute>
                ),
            },
            {
                path: "books/new",
                element: (
                    <ProtectedRoute permission="books.create">
                        <DashboardBookEditor />
                    </ProtectedRoute>
                ),
            },
            {
                path: "books/:id/edit",
                element: (
                    <ProtectedRoute permission="books.update">
                        <DashboardBookEditor />
                    </ProtectedRoute>
                ),
            },
            {
                path: "chapters",
                element: (
                    <ProtectedRoute permission="chapters.view">
                        <DashboardChapters />
                    </ProtectedRoute>
                ),
            },
            {
                path: "chapters/new",
                element: (
                    <ProtectedRoute permission="chapters.create">
                        <DashboardChapterEditor />
                    </ProtectedRoute>
                ),
            },
            {
                path: "chapters/:id/edit",
                element: (
                    <ProtectedRoute permission="chapters.update">
                        <DashboardChapterEditor />
                    </ProtectedRoute>
                ),
            },
            {
                path: "trash",
                element: (
                    <ProtectedRoute permission="trash.view">
                        <DashboardTrash />
                    </ProtectedRoute>
                ),
            },
        ],
    },
]);
