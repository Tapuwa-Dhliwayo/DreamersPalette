import { useEffect, useState } from "react"
import { useParams } from "react-router-dom"
import ReactMarkdown from "react-markdown"

import { getPublishedPoemBySlug } from "@/services/contentService"

export default function PoemPage() {
    const { slug } = useParams()

    const [poem, setPoem] = useState(null)
    const [loading, setLoading] = useState(true)
    const [notFound, setNotFound] = useState(false)

    useEffect(() => {
        async function load() {
            try {
                const data = await getPublishedPoemBySlug(slug)
                setPoem(data)
            } catch (err) {
                console.error("Poem not found:", err)
                setNotFound(true)
            } finally {
                setLoading(false)
            }
        }

        load()
    }, [slug])

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

            <header className="space-y-3 md:space-y-4">
                <h1 className="reader-heading text-3xl md:text-4xl tracking-tight accent-underline">
                    {poem.title}
                </h1>
            </header>

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
    )
}
