import { useEffect, useState } from "react"
import { useParams } from "react-router-dom"
import ReactMarkdown from "react-markdown"

import { getPublishedChapter } from "@/services/bookService"

export default function ChapterPage() {
    const { slug, number } = useParams()

    const [chapter, setChapter] = useState(null)
    const [loading, setLoading] = useState(true)
    const [notFound, setNotFound] = useState(false)

    useEffect(() => {
        async function load() {
            try {
                const data = await getPublishedChapter(slug, parseInt(number, 10))
                setChapter(data)
            } catch (err) {
                console.error("Chapter not found:", err)
                setNotFound(true)
            } finally {
                setLoading(false)
            }
        }

        load()
    }, [slug, number])

    if (loading) {
        return (
            <div className="text-sm text-neutral-500">
                Loading chapter...
            </div>
        )
    }

    if (notFound || !chapter) {
        return (
            <div className="py-20 text-center space-y-4">
                <h2 className="text-2xl font-semibold">
                    Chapter not found
                </h2>
                <p className="text-neutral-500">
                    It may not be published yet.
                </p>
            </div>
        )
    }

    return (
        <article className="reader-fade-in space-y-6 md:space-y-12 pt-2 md:pt-0">

            <header className="space-y-3 md:space-y-4">
                <p className="text-xs md:text-sm opacity-50">
                    Chapter {chapter.chapter_number}
                </p>
                <h1 className="text-3xl md:text-4xl tracking-tight accent-underline">
                    {chapter.title}
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
                                className="text-xl md:text-2xl font-medium mt-8 mb-4"
                                {...props}
                            />
                        )
                    }}
                >
                    {chapter.content_md}
                </ReactMarkdown>

            </div>

        </article>
    )
}
