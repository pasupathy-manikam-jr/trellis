import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/format';
import { type Course, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Award, ListChecks, PlayCircle, ShieldCheck } from 'lucide-react';

const features = [
    {
        icon: PlayCircle,
        title: 'Lessons that stay yours',
        body: 'Video is served from private storage behind an access check, never a public URL anyone can pass around.',
    },
    {
        icon: ListChecks,
        title: 'Quizzes that mean something',
        body: 'A quiz lesson completes only by being passed — so finishing a course is a claim that holds up.',
    },
    {
        icon: Award,
        title: 'Certificates you can check',
        body: 'Every certificate carries a serial anyone can verify, without an account.',
    },
];

export default function Welcome({ courses }: { courses: Course[] }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Learn something properly">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="bg-background text-foreground flex min-h-screen flex-col">
                <header className="border-b">
                    <nav className="mx-auto flex w-full max-w-5xl items-center gap-4 px-6 py-4">
                        <Link href="/" className="flex items-center gap-2 font-semibold">
                            <span className="bg-primary text-primary-foreground flex size-7 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-4" />
                            </span>
                            Trellis
                        </Link>

                        <div className="ml-auto flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild size="sm">
                                    <Link href="/dashboard">Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost" size="sm">
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
                    <section className="mx-auto w-full max-w-5xl px-6 py-20 text-center">
                        <Badge variant="secondary" className="mb-6">
                            <ShieldCheck className="size-3.5" /> Built for people who sell courses
                        </Badge>

                        <h1 className="mx-auto max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                            A structure worth climbing.
                        </h1>

                        <p className="text-muted-foreground mx-auto mt-5 max-w-xl text-lg text-pretty">
                            Trellis gives a course the shape it needs — sections, lessons, quizzes,
                            certificates — and gets out of the way of the teaching.
                        </p>

                        <div className="mt-8 flex flex-wrap justify-center gap-3">
                            <Button asChild size="lg">
                                <Link href="/courses">Browse courses</Link>
                            </Button>
                            {!auth.user && (
                                <Button asChild size="lg" variant="outline">
                                    <Link href="/register">Create an account</Link>
                                </Button>
                            )}
                        </div>
                    </section>

                    {courses.length > 0 && (
                        <section className="mx-auto w-full max-w-5xl px-6 pb-20">
                            <div className="mb-6 flex items-end justify-between gap-4">
                                <h2 className="text-xl font-semibold">Available now</h2>
                                <Link href="/courses" className="text-muted-foreground text-sm hover:underline">
                                    See all →
                                </Link>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {courses.map((course) => (
                                    <Link
                                        key={course.id}
                                        href={`/courses/${course.slug}`}
                                        className="hover:border-foreground/20 flex flex-col gap-2 rounded-xl border p-5 transition-colors"
                                    >
                                        <h3 className="font-medium">{course.title}</h3>
                                        {course.summary && (
                                            <p className="text-muted-foreground line-clamp-3 text-sm">
                                                {course.summary}
                                            </p>
                                        )}
                                        <div className="mt-auto flex items-center gap-2 pt-3">
                                            <Badge variant={course.price_cents === 0 ? 'secondary' : 'default'}>
                                                {money(course.price_cents, course.currency)}
                                            </Badge>
                                            <span className="text-muted-foreground text-xs">
                                                {course.lessons_count} lesson
                                                {course.lessons_count === 1 ? '' : 's'}
                                            </span>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </section>
                    )}

                    <section className="border-t">
                        <div className="mx-auto grid w-full max-w-5xl gap-10 px-6 py-16 sm:grid-cols-3">
                            {features.map(({ icon: Icon, title, body }) => (
                                <div key={title}>
                                    <span className="bg-accent text-accent-foreground mb-4 flex size-9 items-center justify-center rounded-lg">
                                        <Icon className="size-5" />
                                    </span>
                                    <h3 className="mb-1.5 font-medium">{title}</h3>
                                    <p className="text-muted-foreground text-sm leading-relaxed">{body}</p>
                                </div>
                            ))}
                        </div>
                    </section>
                </main>

                <footer className="border-t">
                    <div className="text-muted-foreground mx-auto flex w-full max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-6 py-6 text-sm">
                        <span className="flex items-center gap-2 font-medium">
                            <AppLogoIcon className="size-4" /> Trellis
                        </span>
                        <Link href="/courses" className="hover:underline">
                            Courses
                        </Link>
                        <span className="ml-auto text-xs">Verify a certificate at /verify/&lt;serial&gt;</span>
                    </div>
                </footer>
            </div>
        </>
    );
}
