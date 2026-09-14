import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';
import { Award, Check, ListChecks } from 'lucide-react';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

const points = [
    { icon: Check, tint: 'var(--brand-teal)', text: 'Free preview lessons on every course' },
    { icon: ListChecks, tint: 'var(--brand-sky)', text: 'Quizzes that actually gate completion' },
    { icon: Award, tint: 'var(--brand-amber)', text: 'A certificate anyone can verify' },
];

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="grid min-h-dvh lg:grid-cols-2">
            {/* Brand panel — gradient and lattice, so it needs no image asset. */}
            <div className="relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex">
                <div className="absolute inset-0 -z-10 overflow-hidden bg-[linear-gradient(140deg,hsl(168_66%_11%),hsl(196_58%_18%))]">
                    <div
                        className="animate-drift absolute -top-1/4 -left-[15%] size-[40vw] rounded-full opacity-50 blur-3xl"
                        style={{ background: 'radial-gradient(circle, var(--brand-teal), transparent 70%)' }}
                    />
                    <div
                        className="animate-drift absolute bottom-[-15%] right-[-10%] size-[38vw] rounded-full opacity-40 blur-3xl"
                        style={{
                            background: 'radial-gradient(circle, var(--brand-violet), transparent 70%)',
                            animationDelay: '-9s',
                        }}
                    />
                </div>
                <svg className="absolute inset-0 -z-10 size-full opacity-[0.12]" aria-hidden>
                    <defs>
                        <pattern id="auth-lattice" width="28" height="28" patternUnits="userSpaceOnUse">
                            <path d="M14 0 L28 14 L14 28 L0 14 Z" fill="none" stroke="white" strokeWidth="1" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#auth-lattice)" />
                </svg>

                <Link href="/" className="flex items-center gap-2.5 text-lg font-semibold">
                    <span className="flex size-9 items-center justify-center rounded-lg bg-white/15">
                        <AppLogoIcon className="size-5" />
                    </span>
                    Trellis
                </Link>

                <div className="max-w-md">
                    <h2 className="text-3xl font-bold tracking-tight text-balance">
                        Structured courses, finished properly.
                    </h2>
                    <div className="mt-5 h-1 w-16 rounded-full bg-white/50" />

                    <ul className="mt-8 flex flex-col gap-4">
                        {points.map(({ icon: Icon, text, tint }) => (
                            <li key={text} className="flex items-start gap-3 text-sm text-white/85">
                                <span
                                    className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full text-white"
                                    style={{ background: tint }}
                                >
                                    <Icon className="size-3.5" />
                                </span>
                                {text}
                            </li>
                        ))}
                    </ul>
                </div>

                <p className="text-xs text-white/50">Verify a certificate at /verify/&lt;serial&gt;</p>
            </div>

            {/* Form panel */}
            <div className="flex items-center justify-center px-6 py-12">
                <div className="w-full max-w-sm">
                    <Link href="/" className="mb-8 flex items-center justify-center gap-2 font-semibold lg:hidden">
                        <span className="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-4" />
                        </span>
                        Trellis
                    </Link>

                    <div className="mb-6">
                        <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                        {description && (
                            <p className="text-muted-foreground mt-2 text-sm text-pretty">{description}</p>
                        )}
                        <div className="bg-primary mt-4 h-1 w-12 rounded-full" />
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
