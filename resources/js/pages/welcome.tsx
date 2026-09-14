import AppLogoIcon from '@/components/app-logo-icon';
import { CourseCard } from '@/components/course-card';
import { HeroBackdrop } from '@/components/hero-backdrop';
import { Button } from '@/components/ui/button';
import { type CourseCardData, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Award, GraduationCap, ListChecks, PlayCircle, Users } from 'lucide-react';

type Props = {
    courses: CourseCardData[];
    hero_image: string | null;
    hero_video: string | null;
    stats: { courses: number; lessons: number; learners: number };
};

const features = [
    {
        icon: PlayCircle,
        tint: 'var(--brand-sky)',
        title: 'Lessons that stay yours',
        body: 'Video is served from private storage behind an access check — never a public URL that can be passed around.',
    },
    {
        icon: ListChecks,
        tint: 'var(--brand-violet)',
        title: 'Quizzes that mean something',
        body: 'A quiz lesson completes only by being passed, so finishing a course is a claim that holds up.',
    },
    {
        icon: Award,
        tint: 'var(--brand-amber)',
        title: 'Certificates you can check',
        body: 'Every certificate carries a serial anyone can verify, with no account needed.',
    },
];

export default function Welcome({ courses, hero_image, hero_video, stats }: Props) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Learn something properly">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
            </Head>

            <div className="bg-background text-foreground flex min-h-screen flex-col">
                <header className="absolute inset-x-0 top-0 z-10">
                    <nav className="mx-auto flex w-full max-w-6xl items-center gap-4 px-6 py-5">
                        <Link href="/" className="flex items-center gap-2 font-semibold text-white">
                            <span className="bg-primary flex size-8 items-center justify-center rounded-md text-white">
                                <AppLogoIcon className="size-4" />
                            </span>
                            Trellis
                        </Link>

                        <div className="ml-auto flex items-center gap-2">
                            <Button asChild variant="ghost" size="sm" className="text-white hover:bg-white/15 hover:text-white">
                                <Link href="/courses">Courses</Link>
                            </Button>
                            {auth.user ? (
                                <Button asChild size="sm">
                                    <Link href="/dashboard">Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost" size="sm" className="text-white hover:bg-white/15 hover:text-white">
                                        <Link href="/login">Log in</Link>
                                    </Button>
                                    <Button asChild size="sm">
                                        <Link href="/register">Register</Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </nav>
                </header>

                <main className="flex-1">
                    {/* Hero — the photograph is a real course thumbnail when one exists. */}
                    <section className="relative isolate overflow-hidden">
                        <HeroBackdrop video={hero_video} image={hero_image} />

                        <div className="mx-auto w-full max-w-6xl px-6 py-32 sm:py-40">
                            <div className="max-w-2xl text-white">
                                <span className="bg-primary/25 ring-primary/40 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1">
                                    <GraduationCap className="size-3.5" /> Courses, quizzes and certificates
                                </span>

                                <h1 className="mt-6 text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                                    Learn something properly.
                                </h1>


                                <p className="mt-6 max-w-xl text-lg text-pretty text-white/80">
                                    Structured courses with real assessment at the end — not a folder of videos.
                                    Work through it at your pace and leave with something you can show.
                                </p>

                                <div className="mt-8 flex flex-wrap gap-3">
                                    <Button asChild size="lg">
                                        <Link href="/courses">Browse courses</Link>
                                    </Button>
                                    <Button
                                        asChild
                                        size="lg"
                                        variant="outline"
                                        className="border-white/30 bg-white/5 text-white hover:bg-white/15 hover:text-white"
                                    >
                                        <Link href="#how">How it works</Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Stats band */}
                    <section className="relative overflow-hidden bg-[linear-gradient(100deg,hsl(168_62%_26%),hsl(190_64%_30%),hsl(263_48%_40%))] text-white">
                        <div className="mx-auto grid w-full max-w-6xl grid-cols-3 divide-x divide-white/20 px-6">
                            {[
                                { label: 'Courses', value: stats.courses, icon: GraduationCap },
                                { label: 'Lessons', value: stats.lessons, icon: PlayCircle },
                                { label: 'Learners', value: stats.learners, icon: Users },
                            ].map(({ label, value, icon: Icon }) => (
                                <div key={label} className="flex items-center justify-center gap-3 py-6 sm:py-8">
                                    <Icon className="size-6 opacity-70" />
                                    <div>
                                        <div className="text-2xl font-bold tabular-nums sm:text-3xl">{value}</div>
                                        <div className="text-xs opacity-80">{label}</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* Course grid */}
                    {courses.length > 0 && (
                        <section className="mx-auto w-full max-w-6xl px-6 py-20">
                            <div className="mb-10 text-center">
                                <span className="text-primary text-xs font-semibold tracking-widest uppercase">
                                    Available now
                                </span>
                                <h2 className="mt-2 text-3xl font-bold tracking-tight">Popular courses</h2>
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {courses.map((course) => (
                                    <CourseCard key={course.id} course={course} />
                                ))}
                            </div>

                            <div className="mt-10 text-center">
                                <Button asChild variant="outline" size="lg">
                                    <Link href="/courses">See all courses</Link>
                                </Button>
                            </div>
                        </section>
                    )}

                    {/* Why us */}
                    <section id="how" className="bg-muted/40 border-y">
                        <div className="mx-auto w-full max-w-6xl px-6 py-20">
                            <div className="mb-12 text-center">
                                <span className="text-primary text-xs font-semibold tracking-widest uppercase">
                                    How it works
                                </span>
                                <h2 className="mt-2 text-3xl font-bold tracking-tight">Built to be finished</h2>
                            </div>

                            <div className="grid gap-8 sm:grid-cols-3">
                                {features.map(({ icon: Icon, tint, title, body }) => (
                                    <div
                                        key={title}
                                        className="bg-card relative overflow-hidden rounded-xl border p-6 text-center transition-shadow hover:shadow-lg"
                                    >
                                        <span
                                            className="absolute inset-x-0 top-0 h-1"
                                            style={{ background: tint }}
                                        />
                                        <span
                                            className="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl text-white"
                                            style={{ background: tint }}
                                        >
                                            <Icon className="size-6" />
                                        </span>
                                        <h3 className="mb-2 font-semibold">{title}</h3>
                                        <p className="text-muted-foreground text-sm leading-relaxed">{body}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* CTA */}
                    <section className="relative overflow-hidden">
                        <div className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,hsl(38_92%_52%/0.14),transparent_60%),radial-gradient(ellipse_at_bottom_right,hsl(263_66%_62%/0.14),transparent_55%)]" />
                        <div className="mx-auto w-full max-w-6xl px-6 py-20 text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-balance">
                            Start with a free preview.
                        </h2>
                        <p className="text-muted-foreground mx-auto mt-3 max-w-lg">
                            Every course opens a lesson or two to everyone. Read one before you decide.
                        </p>
                        <Button asChild size="lg" className="mt-6">
                            <Link href="/courses">Browse courses</Link>
                        </Button>
                        </div>
                    </section>
                </main>

                <footer className="bg-muted/40 border-t">
                    <div className="text-muted-foreground mx-auto flex w-full max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-6 py-8 text-sm">
                        <span className="text-foreground flex items-center gap-2 font-semibold">
                            <AppLogoIcon className="size-4" /> Trellis
                        </span>
                        <Link href="/courses" className="hover:text-foreground">
                            Courses
                        </Link>
                        <Link href="/handbook" className="hover:text-foreground">
                            Handbook
                        </Link>
                        <span className="ml-auto text-xs">Verify a certificate at /verify/&lt;serial&gt;</span>
                    </div>
                </footer>
            </div>
        </>
    );
}
