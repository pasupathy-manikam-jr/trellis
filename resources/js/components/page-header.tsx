import { type LucideIcon } from 'lucide-react';

/**
 * The same masthead the public pages use — eyebrow, title, accent rule — so
 * signing in does not drop you into an unbranded shell.
 */
export function PageHeader({
    eyebrow,
    title,
    lede,
    actions,
    tone = 'primary',
}: {
    eyebrow: string;
    title: string;
    lede?: string;
    actions?: React.ReactNode;
    tone?: 'primary' | 'amber';
}) {
    const accent = tone === 'amber' ? 'text-amber-600 dark:text-amber-400' : 'text-primary';

    return (
        <div className="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span className={`text-xs font-semibold tracking-widest uppercase ${accent}`}>
                    {eyebrow}
                </span>
                <h1 className="mt-1.5 text-2xl font-bold tracking-tight">{title}</h1>
                {lede && <p className="text-muted-foreground mt-3 max-w-prose text-sm">{lede}</p>}
            </div>
            {actions && <div className="flex items-center gap-2">{actions}</div>}
        </div>
    );
}

/** A figure that matters, carrying its own colour. */
export function StatTile({
    label,
    value,
    icon: Icon,
    tint,
}: {
    label: string;
    value: string | number;
    icon?: LucideIcon;
    tint: string;
}) {
    return (
        <div className="bg-card relative overflow-hidden rounded-xl border p-4">
            <span className="absolute inset-x-0 top-0 h-1" style={{ background: tint }} aria-hidden />
            <div className="text-muted-foreground flex items-center gap-1.5 text-xs">
                {Icon && <Icon className="size-3.5" style={{ color: tint }} />}
                {label}
            </div>
            <div className="mt-1 text-2xl font-bold tabular-nums">{value}</div>
        </div>
    );
}
