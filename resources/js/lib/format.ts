export function money(cents: number, currency = 'USD'): string {
    if (cents === 0) return 'Free';

    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(cents / 100);
}

export function duration(seconds: number | null): string | null {
    if (!seconds) return null;

    const m = Math.floor(seconds / 60);
    const s = seconds % 60;

    return m > 0 ? `${m}m${s ? ` ${s}s` : ''}` : `${s}s`;
}
