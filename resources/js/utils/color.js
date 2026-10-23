function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value))
}

function channelToHex(channel) {
    return Math.round(clamp(channel, 0, 255)).toString(16).padStart(2, "0")
}

function rgbToHex(red, green, blue) {
    return `#${channelToHex(red)}${channelToHex(green)}${channelToHex(blue)}`
}

function parseRgbChannel(value) {
    const trimmed = value.trim()
    if (trimmed.endsWith("%")) {
        const percentage = Number(trimmed.slice(0, -1))
        if (!Number.isFinite(percentage) || percentage < 0 || percentage > 100) return null
        return (percentage / 100) * 255
    }

    const channel = Number(trimmed)
    if (!Number.isFinite(channel) || channel < 0 || channel > 255) return null
    return channel
}

function hslToRgb(hue, saturation, lightness) {
    const normalizedHue = ((hue % 360) + 360) % 360
    const chroma = (1 - Math.abs(2 * lightness - 1)) * saturation
    const section = normalizedHue / 60
    const intermediate = chroma * (1 - Math.abs((section % 2) - 1))
    let red = 0
    let green = 0
    let blue = 0

    if (section < 1) [red, green] = [chroma, intermediate]
    else if (section < 2) [red, green] = [intermediate, chroma]
    else if (section < 3) [green, blue] = [chroma, intermediate]
    else if (section < 4) [green, blue] = [intermediate, chroma]
    else if (section < 5) [red, blue] = [intermediate, chroma]
    else [red, blue] = [chroma, intermediate]

    const offset = lightness - chroma / 2
    return [
        (red + offset) * 255,
        (green + offset) * 255,
        (blue + offset) * 255
    ]
}

export function parseColorToHex(input) {
    if (typeof input !== "string") return null
    const value = input.trim().toLowerCase()

    const hexMatch = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i)
    if (hexMatch) {
        const hex = hexMatch[1]
        return hex.length === 3
            ? `#${hex.split("").map((character) => character.repeat(2)).join("")}`
            : `#${hex}`
    }

    const rgbMatch = value.match(/^rgb\(\s*([^,]+)\s*,\s*([^,]+)\s*,\s*([^,]+)\s*\)$/i)
    if (rgbMatch) {
        const channels = rgbMatch.slice(1).map(parseRgbChannel)
        if (channels.some((channel) => channel === null)) return null
        return rgbToHex(...channels)
    }

    const hslMatch = value.match(/^hsl\(\s*(-?(?:\d+\.?\d*|\.\d+))(?:deg)?\s*,\s*(\d+\.?\d*)%\s*,\s*(\d+\.?\d*)%\s*\)$/i)
    if (hslMatch) {
        const hue = Number(hslMatch[1])
        const saturation = Number(hslMatch[2])
        const lightness = Number(hslMatch[3])
        if (![hue, saturation, lightness].every(Number.isFinite)) return null
        if (saturation < 0 || saturation > 100 || lightness < 0 || lightness > 100) return null
        return rgbToHex(...hslToRgb(hue, saturation / 100, lightness / 100))
    }

    return null
}

function hexToChannels(color) {
    const normalized = parseColorToHex(color)
    if (!normalized) return null
    return [
        Number.parseInt(normalized.slice(1, 3), 16),
        Number.parseInt(normalized.slice(3, 5), 16),
        Number.parseInt(normalized.slice(5, 7), 16)
    ]
}

export function compositeColors(foreground, background, opacity) {
    const foregroundChannels = hexToChannels(foreground)
    const backgroundChannels = hexToChannels(background)
    if (!foregroundChannels || !backgroundChannels) return null

    const alpha = clamp(Number(opacity), 0, 1)
    return rgbToHex(...foregroundChannels.map(
        (channel, index) => channel * alpha + backgroundChannels[index] * (1 - alpha)
    ))
}

export function relativeLuminance(color) {
    const channels = hexToChannels(color)
    if (!channels) return null

    const linear = channels.map((channel) => {
        const value = channel / 255
        return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
    })

    return 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2]
}

export function contrastRatio(firstColor, secondColor) {
    const first = relativeLuminance(firstColor)
    const second = relativeLuminance(secondColor)
    if (first === null || second === null) return null

    const lighter = Math.max(first, second)
    const darker = Math.min(first, second)
    return (lighter + 0.05) / (darker + 0.05)
}
