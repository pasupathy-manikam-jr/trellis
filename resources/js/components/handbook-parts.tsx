import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { type LucideIcon } from 'lucide-react';

type Tone = 'learner' | 'admin' | 'note';

const toneClass: Record<Tone, string> = {
    learner: 'text-primary',
    admin: 'text-amber-700 dark:text-amber-400',
    note: 'text-muted-foreground',
};

/** Page shell shared by both handbooks: title, switcher, contents rail. */
export function HandbookPage({
    eyebrow,
    title,
    lede,
    tone,
    contents,
    switcher,
    children,
}: {
    eyebrow: string;
    title: string;
    lede: string;
    tone: Tone;
    contents: { href: string; label: string }[];
    switcher?: { href: string; label: string };
    children: React.ReactNode;
}) {
    return (
        <PublicLayout>
            <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span
                        className={`text-xs font-semibold tracking-widest uppercase ${toneClass[tone]}`}
                    >
                        {eyebrow}
                    </span>
                    <h1 className="mt-2 text-3xl font-bold tracking-tight">{title}</h1>
                    <p className="text-muted-foreground mt-4 max-w-prose">{lede}</p>
                </div>

                {switcher && (
                    <Link
                        href={switcher.href}
                        className="hover:border-foreground/30 hover:bg-muted rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                    >
                        {switcher.label} →
                    </Link>
                )}
            </div>

            <nav aria-label="Contents" className="mb-12 flex flex-wrap gap-x-4 gap-y-1 border-y py-4">
                {contents.map((item) => (
                    <a
                        key={item.href}
                        href={item.href}
                        className="text-muted-foreground hover:text-foreground text-sm"
                    >
                        {item.label}
                    </a>
                ))}
            </nav>

            <div className="flex flex-col gap-14">{children}</div>
        </PublicLayout>
    );
}

export function Section({
    id,
    eyebrow,
    tone,
    title,
    lede,
    children,
}: {
    id: string;
    eyebrow: string;
    tone: Tone;
    title: string;
    lede?: string;
    children: React.ReactNode;
}) {
    return (
        <section id={id} className="scroll-mt-6">
            <p
                className={`flex items-center gap-2 text-xs font-semibold tracking-widest uppercase ${toneClass[tone]}`}
            >
                <span className="size-1.5 rotate-45 bg-current" aria-hidden />
                {eyebrow}
            </p>
            <h2 className="mt-2 text-2xl font-bold tracking-tight text-balance">{title}</h2>
            {lede && <p className="text-muted-foreground mt-2 max-w-prose">{lede}</p>}
            {children}
        </section>
    );
}

export function Cards({
    items,
}: {
    items: { icon: LucideIcon; title: string; body: string; where?: string }[];
}) {
    return (
        <div className="mt-6 grid overflow-hidden rounded-xl border sm:grid-cols-2">
            {items.map(({ icon: Icon, title, body, where }, i) => (
                <div
                    key={title}
                    className={`bg-card p-5 ${i % 2 === 0 ? 'sm:border-r' : ''} ${
                        i < items.length - (items.length % 2 === 0 ? 2 : 1) ? 'border-b' : ''
                    }`}
                >
                    <h3 className="flex items-center gap-2 font-semibold">
                        <Icon className="text-muted-foreground size-4" />
                        {title}
                    </h3>
                    <p className="text-muted-foreground mt-1.5 text-sm">{body}</p>
                    {where && <code className="text-muted-foreground mt-2 block text-xs">{where}</code>}
                </div>
            ))}
        </div>
    );
}

export function Steps({
    items,
    tone = 'learner',
}: {
    items: { title: string; body: React.ReactNode }[];
    tone?: Tone;
}) {
    return (
        <ol className="mt-6 flex flex-col gap-6">
            {items.map((step, i) => (
                <li key={step.title} className="grid grid-cols-[2rem_1fr] items-start gap-4">
                    <span
                        className={`bg-card grid size-8 place-items-center rounded-full border font-mono text-xs font-semibold tabular-nums ${toneClass[tone]}`}
                    >
                        {i + 1}
                    </span>
                    <div>
                        <h3 className="font-semibold">{step.title}</h3>
                        <div className="text-muted-foreground mt-1 max-w-prose text-sm">{step.body}</div>
                    </div>
                </li>
            ))}
        </ol>
    );
}

export function Prose({ children }: { children: React.ReactNode }) {
    return <div className="text-muted-foreground mt-4 flex max-w-prose flex-col gap-3">{children}</div>;
}

export function Notes({ items }: { items: [string, string][] }) {
    return (
        <ul className="mt-6 flex flex-col gap-4">
            {items.map(([title, body]) => (
                <li key={title} className="border-l-2 border-amber-500/70 pl-4">
                    <strong className="block text-sm font-semibold">{title}</strong>
                    <span className="text-muted-foreground text-sm">{body}</span>
                </li>
            ))}
        </ul>
    );
}
