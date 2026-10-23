import React from "react";
import ReactDOM from "react-dom/client";
import { RouterProvider } from "react-router-dom";
import { Suspense } from "react";
import { router } from "./app/router";
import "./index.css";
import { Analytics } from "@vercel/analytics/react";

const preloadReloadKey = "vite-preload-reload";

window.addEventListener("vite:preloadError", (event) => {
    event.preventDefault();

    if (!sessionStorage.getItem(preloadReloadKey)) {
        sessionStorage.setItem(preloadReloadKey, "1");
        window.location.reload();
    }
});

window.addEventListener("load", () => {
    sessionStorage.removeItem(preloadReloadKey);
});

ReactDOM.createRoot(document.getElementById("root")).render(
    <React.StrictMode>
        <Suspense fallback={<div>Loading...</div>}>
            <>
                <RouterProvider router={router} />
                <Analytics />
            </>
        </Suspense>
    </React.StrictMode>
);
