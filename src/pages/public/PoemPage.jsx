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
            <div className="text-sm text-neutral-500">
                Loading poem...
            </div>
        )
    }

    if (notFound || !poem) {
        return (
            <div className="py-20 text-center space-y-4">
                <h2 className="text-2xl font-semibold">
                    Poem not found
                </h2>
                <p className="text-neutral-500">
                    It may not be published yet.
                </p>
            </div>
        )
    }

    return (
        <article className="reader-fade-in space-y-8 md:space-y-12">

            <header className="space-y-4">
                <h1 className="text-4xl tracking-tight accent-underline">
                    {poem.title}
                </h1>
            </header>

            <div className="max-w-3xl">

                <ReactMarkdown
                    skipHtml
                    components={{
                        p: ({ node, ...props }) => (
                            <p
                                className="whitespace-pre-wrap leading-relaxed text-lg mb-6"
                                {...props}
                            />
                        ),
                        blockquote: ({ node, ...props }) => (
                            <blockquote
                                className="border-l-2 border-current/30 pl-4 italic my-6 whitespace-pre-wrap opacity-85"
                                {...props}
                            />
                        ),
                        h2: ({ node, ...props }) => (
                            <h2
                                className="text-2xl font-medium mt-8 mb-4"
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