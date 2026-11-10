export function originLabel(origin) {
    return [origin?.client, origin?.model].filter(Boolean).join(" · ");
}
