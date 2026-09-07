import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import ReactMarkdown from "react-markdown";

import { getPublishedPoemBySlug } from "@/services/contentService";
import { sharePoem } from "@/services/poemShareService";

function ShareIcon() {
    return (
        <svg
            aria-hidden="true"
            className="size-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth="1.75"
        >
            <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 12 12 7.5m0 0 4.5 4.5M12 7.5V18" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5.25 9.75v-3A1.5 1.5 0 0 1 6.75 5.25h10.5a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5h-3" />
        </svg>
    );
}

export default function PoemPage() {
    const { slug } = useParams();

    const [poem, setPoem] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);
    const [shareState, setShareState] = useState("ready");
    const [shareMessage, setShareMessage] = useState("");

    useEffect(() => {
        async function load() {
            try {
                const poemData = await getPublishedPoemBySlug(slug);
                setPoem(poemData);
            } catch (err) {
                console.error("Poem not found:", err);
                setNotFound(true);
            } finally {
                setLoading(false);
            }
        }

        load();
    }, [slug]);

    async function handleShare() {
        setShareState("preparing");
        setShareMessage("");

        try {
            const shareResult = await sharePoem(poem, window.location.href);

            if (shareResult.outcome === "cancelled") {
                setShareState("ready");
                return;
            }

            if (shareResult.outcome === "downloaded") {
                setShareMessage(
                    shareResult.linkCopied
                        ? "Image downloaded and poem link copied."
                        : "Image downloaded. Copy the page address to share the poem link.",
                );
            } else {
                setShareMessage("Poem ready to share.");
            }

            setShareState("complete");
        } catch (error) {
            console.error("Poem share failed:", error);
            setShareState("error");
            setShareMessage("The image could not be prepared. You can still copy this page’s address.");
        }
    }

    if (loading) {
        return (
            <div className="reader-muted text-sm">
                Loading poem...
            </div>
        )
    }

    if (notFound || !poem) {
        return (
            <div className="py-20 text-center space-y-4">
                <h2 className="reader-heading text-2xl font-semibold">
                    Poem not found
                </h2>
                <p className="reader-muted">
                    It may not be published yet.
                </p>
            </div>
        )
    }

    return (
        <article className="reader-fade-in space-y-6 md:space-y-12 pt-2 md:pt-0">

            <header className="flex flex-col items-start gap-5 md:flex-row md:items-end md:justify-between">
                <h1 className="reader-heading max-w-2xl text-3xl tracking-tight accent-underline md:text-4xl">
                    {poem.title}
                </h1>

                <button
                    type="button"
                    className="accent-button inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 disabled:cursor-wait disabled:opacity-60"
                    disabled={shareState === "preparing"}
                    onClick={handleShare}
                >
                    <ShareIcon />
                    {shareState === "preparing" ? "Preparing image…" : "Share poem"}
                </button>
            </header>

            {shareMessage && (
                <p
                    className={`text-sm ${shareState === "error" ? "text-red-300" : "reader-muted"}`}
                    role={shareState === "error" ? "alert" : "status"}
                    aria-live="polite"
                >
                    {shareMessage}
                </p>
            )}

            <div className="max-w-3xl">

                <ReactMarkdown
                    skipHtml
                    components={{
                        p: ({ ...props }) => (
                            <p
                                className="whitespace-pre-wrap leading-relaxed text-base md:text-lg mb-5 md:mb-6"
                                {...props}
                            />
                        ),
                        blockquote: ({ ...props }) => (
                            <blockquote
                                className="border-l-2 border-current/30 pl-4 italic my-6 whitespace-pre-wrap opacity-85"
                                {...props}
                            />
                        ),
                        h2: ({ ...props }) => (
                            <h2
                                className="reader-heading text-xl md:text-2xl font-medium mt-8 mb-4"
                                {...props}
                            />
                        )
                    }}
                >
                    {poem.content_md}
                </ReactMarkdown>

            </div>

        </article>
    );
}
