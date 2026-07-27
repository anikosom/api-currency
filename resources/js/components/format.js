export function formatRate(value) {
    const abs = Math.abs(value);
    const digits = abs >= 100 ? 2 : abs >= 1 ? 4 : 6;

    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: digits,
    }).format(value);
}

export function formatTime(iso) {
    return new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}
