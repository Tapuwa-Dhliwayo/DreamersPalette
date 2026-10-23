export function hexToRgb(hex) {
    const cleaned = hex.replace("#", "")
    const bigint = parseInt(cleaned, 16)
    return {
        r: (bigint >> 16) & 255,
        g: (bigint >> 8) & 255,
        b: bigint & 255
    }
}