import { formatDate } from "@/utils/formatDate";

const CARD_WIDTH = 1080;
const CARD_HEIGHT = 1920;
const CARD_PADDING = 96;
const DEFAULT_BACKGROUND = "/assets/global_atmosphere.png";
const DEFAULT_INK = "#f5f5f5";
const DEFAULT_MUTED_INK = "#cbd5e1";

function getCollection(poem) {
    return Array.isArray(poem.poetry_collections)
        ? poem.poetry_collections[0]
        : poem.poetry_collections;
}

async function loadImage(source) {
    const response = await fetch(source, { mode: "cors" });

    if (!response.ok) throw new Error("The collection background could not be loaded.");

    return createImageBitmap(await response.blob());
}

function drawCoverImage(context, image) {
    const scale = Math.max(CARD_WIDTH / image.width, CARD_HEIGHT / image.height);
    const width = image.width * scale;
    const height = image.height * scale;

    context.drawImage(
        image,
        (CARD_WIDTH - width) / 2,
        (CARD_HEIGHT - height) / 2,
        width,
        height,
    );
}

function wrapTitle(context, title, maxWidth) {
    const words = title.trim().split(/\s+/).flatMap((word) => {
        if (context.measureText(word).width <= maxWidth) return [word];

        const fragments = [];
        let fragment = "";

        Array.from(word).forEach((character) => {
            const candidate = `${fragment}${character}`;

            if (context.measureText(candidate).width <= maxWidth || !fragment) {
                fragment = candidate;
                return;
            }

            fragments.push(fragment);
            fragment = character;
        });

        if (fragment) fragments.push(fragment);
        return fragments;
    });
    const lines = [];
    let line = "";

    words.forEach((word) => {
        const candidate = line ? `${line} ${word}` : word;

        if (context.measureText(candidate).width <= maxWidth || !line) {
            line = candidate;
            return;
        }

        lines.push(line);
        line = word;
    });

    if (line) lines.push(line);

    return lines;
}

function fitTitle(context, title) {
    let fontSize = 132;
    let lines = [];

    while (fontSize >= 60) {
        context.font = `500 ${fontSize}px Georgia, "Times New Roman", serif`;
        lines = wrapTitle(context, title, CARD_WIDTH - CARD_PADDING * 2);

        if (lines.length <= 6) break;
        fontSize -= 8;
    }

    return { fontSize, lines };
}

function drawCardContent(context, poem) {
    const collection = getCollection(poem);
    const date = formatDate(poem.published_at || poem.created_at);
    const metadata = date ? `Published · ${date}` : "";
    const { fontSize, lines } = fitTitle(context, poem.title);
    const lineHeight = fontSize * 1.08;
    const titleHeight = lines.length * lineHeight;
    const titleY = Math.min(1180, CARD_HEIGHT - CARD_PADDING - 300 - titleHeight);

    context.textBaseline = "top";
    context.shadowColor = "rgba(0, 0, 0, 0.55)";
    context.shadowBlur = 18;
    context.fillStyle = DEFAULT_MUTED_INK;
    context.font = "500 34px ui-sans-serif, system-ui, sans-serif";
    context.fillText(metadata, CARD_PADDING, titleY - 90);

    context.fillStyle = DEFAULT_INK;
    context.font = `500 ${fontSize}px Georgia, "Times New Roman", serif`;
    lines.forEach((line, index) => {
        context.fillText(line, CARD_PADDING, titleY + index * lineHeight);
    });

    const ruleY = CARD_HEIGHT - CARD_PADDING - 112;
    context.fillStyle = collection?.accent_color || DEFAULT_MUTED_INK;
    context.fillRect(CARD_PADDING, ruleY, 112, 4);

    context.fillStyle = DEFAULT_INK;
    context.font = "500 30px ui-sans-serif, system-ui, sans-serif";
    context.fillText("Dreamer’s Palette", CARD_PADDING, ruleY + 28);
}

function canvasToBlob(canvas) {
    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
                return;
            }

            reject(new Error("The share image could not be created."));
        }, "image/png");
    });
}

async function createPoemCard(poem) {
    const collection = getCollection(poem);
    const canvas = document.createElement("canvas");
    const context = canvas.getContext("2d");

    canvas.width = CARD_WIDTH;
    canvas.height = CARD_HEIGHT;

    if (!context) throw new Error("Image creation is not supported in this browser.");

    context.fillStyle = "#080d16";
    context.fillRect(0, 0, CARD_WIDTH, CARD_HEIGHT);

    try {
        const background = await loadImage(collection?.theme_background_url || DEFAULT_BACKGROUND);
        drawCoverImage(context, background);
        background.close();
    } catch {
        // The atmospheric colour fallback still produces a usable, untainted image.
    }

    const authoredOpacity = Number(collection?.theme_overlay_opacity ?? 0.65);
    const overlayOpacity = Math.max(0.68, Math.min(authoredOpacity, 0.86));
    context.fillStyle = `rgba(2, 7, 15, ${overlayOpacity})`;
    context.fillRect(0, 0, CARD_WIDTH, CARD_HEIGHT);

    if (document.fonts?.ready) await document.fonts.ready;
    drawCardContent(context, poem);

    return canvasToBlob(canvas);
}

function downloadCard(file) {
    const downloadUrl = URL.createObjectURL(file);
    const link = document.createElement("a");

    link.href = downloadUrl;
    link.download = file.name;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(downloadUrl);
}

async function copyPoemUrl(poemUrl) {
    if (!navigator.clipboard?.writeText) return false;

    try {
        await navigator.clipboard.writeText(poemUrl);
        return true;
    } catch {
        return false;
    }
}

export async function sharePoem(poem, poemUrl) {
    const card = await createPoemCard(poem);
    const safeSlug = poem.slug.replace(/[^a-z0-9-]/gi, "-").toLowerCase();
    const file = new File([card], `${safeSlug}-dreamers-palette.png`, { type: "image/png" });
    const sharePayload = {
        files: [file],
        title: poem.title,
        text: `${poem.title}\n${poemUrl}`,
    };

    if (navigator.share && navigator.canShare?.({ files: [file] })) {
        try {
            await navigator.share(sharePayload);
            return { outcome: "shared" };
        } catch (error) {
            if (error.name === "AbortError") return { outcome: "cancelled" };
            throw error;
        }
    }

    downloadCard(file);
    const linkCopied = await copyPoemUrl(poemUrl);

    return { outcome: "downloaded", linkCopied };
}
