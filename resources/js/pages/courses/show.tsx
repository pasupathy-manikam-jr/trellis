import { CourseThumb } from '@/components/course-card';
import { Reviews, Stars, type ReviewSummary } from '@/components/reviews';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { duration, money } from '@/lib/format';
import { type Course, type Progress } from '@/types';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { type SharedData } from '@/types';
import { ArrowRight, Download, Eye, FileText, ListChecks, LoaderCircle, Lock, Pencil, PenLine, Play } from 'lucide-react';

const icons = { video: Play, text: FileText, download: Download, quiz: ListChecks, assignment: PenLine };

type Props = {
    course: Course;
    enrolled: boolean;
    progress: Progress | null;
    can_purchase: boolean;
    can_review: boolean;
    reviews: ReviewSummary;
    can_preview_all: boolean;
};

export default function CourseShow({
    course,
    enrolled,
    progress,
    can_purchase,
    can_review,
    reviews,
    can_preview_all,
}: Props) {
    const sections = course.sections ?? [];
    const lessonCount = sections.reduce((n, s) => n + s.lessons.length, 0);
    const previewCount = sections.reduce(
        (n, s) => n + s.lessons.filter((lesson) => lesson.is_preview).length,
        0,
    );

    return (
        <PublicLayout
            breadcrumbs={[
                { title: 'Courses', href: '/courses' },
                { title: course.title, href: `/courses/${course.slug}` },
            ]}
        >
            <Head title={course.title} />

            <div className="mb-4 flex items-center justify-between gap-3">
                <Button asChild variant="ghost" size="sm" className="-ml-2">
                    <Link href="/courses">← All courses</Link>
                </Button>

                {can_preview_all && (
                    <Button asChild variant="outline" size="sm">
                        <Link href={`/admin/courses/${course.slug}/edit`}>
                            <Pencil className="size-3.5" /> Edit course
                        </Link>
                    </Button>
                )}
            </div>

            <div className="relative mb-8 isolate overflow-hidden rounded-2xl">
                <div className="absolute inset-0 -z-10">
                    <CourseThumb course={{ id: course.id, title: course.title, thumbnail_url: course.thumbnail_url ?? null }} />
                    <div className="absolute inset-0 bg-gradient-to-r from-black/85 via-black/70 to-black/40" />
                </div>

                <div className="px-6 py-12 text-white sm:px-10 sm:py-16">
                    <h1 className="max-w-2xl text-3xl font-bold tracking-tight text-balance sm:text-4xl">
                        {course.title}
                    </h1>
                    {course.summary && (
                        <p className="mt-3 max-w-xl text-white/80">{course.summary}</p>
                    )}
                    <div className="mt-4 flex flex-wrap items-center gap-4 text-sm text-white/80">
                        {course.instructor && <span>By {course.instructor.name}</span>}
                        <span>{lessonCount} lessons</span>
                        {reviews.average !== null && (
                            <span className="flex items-center gap-1.5">
                                <Stars rating={Math.round(reviews.average)} className="size-3.5" />
                                {reviews.average.toFixed(1)} ({reviews.count})
                            </span>
                        )}
                    </div>
                </div>
            </div>

            <div className="grid gap-8 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    {course.description && (
                        <div className="text-sm leading-relaxed whitespace-pre-wrap">{course.description}</div>
                    )}

                    <h2 className="mt-8 font-semibold">
                        Curriculum
                        <span className="text-muted-foreground ml-2 text-sm font-normal">
                            {sections.length} section{sections.length === 1 ? '' : 's'} · {lessonCount} lessons
                        </span>
                    </h2>

                    {previewCount > 0 && !enrolled && (
                        <p className="border-primary/25 bg-primary/5 text-primary mt-3 flex items-center gap-2 rounded-lg border px-3 py-2 text-sm">
                            <Eye className="size-4 shrink-0" />
                            <span>
                                <strong className="font-semibold">
                                    {previewCount} lesson{previewCount === 1 ? ' is' : 's are'} free to read
                                </strong>{' '}
                                — open {previewCount === 1 ? 'it' : 'them'} below, no account needed.
                            </span>
                        </p>
                    )}

                    <div className="mt-3 flex flex-col gap-3">
                        {sections.map((section) => (
                            <div key={section.id} className="rounded-lg border">
                                <div className="border-b px-4 py-2 text-sm font-medium">{section.title}</div>
                                <ul className="divide-y">
                                    {section.lessons.map((lesson) => {
                                        const Icon = icons[lesson.type];

                                        const open = enrolled || can_preview_all || lesson.is_preview;
                                        const free = lesson.is_preview && !enrolled;

                                        const row = (
                                            <>
                                                <Icon
                                                    className={`size-4 shrink-0 ${free ? 'text-primary' : 'text-muted-foreground'}`}
                                                />
                                                <span
                                                    className={`flex-1 truncate ${
                                                        free
                                                            ? 'text-primary font-medium'
                                                            : open
                                                              ? ''
                                                              : 'text-muted-foreground'
                                                    }`}
                                                >
                                                    {lesson.title}
                                                </span>

                                                {free && (
                                                    <Badge className="shrink-0 gap-1">
                                                        Free <ArrowRight className="size-3" />
                                                    </Badge>
                                                )}

                                                {!open && (
                                                    <Lock className="text-muted-foreground/60 size-3.5 shrink-0" />
                                                )}

                                                {duration(lesson.duration_sec) && (
                                                    <span className="text-muted-foreground shrink-0 text-xs">
                                                        {duration(lesson.duration_sec)}
                                                    </span>
                                                )}
                                            </>
                                        );

                                        return (
                                            <li key={lesson.id}>
                                                {open ? (
                                                    <Link
                                                        href={`/learn/${course.slug}/${lesson.id}`}
                                                        className={`flex items-center gap-3 px-4 py-2.5 text-sm transition-colors ${
                                                            free ? 'hover:bg-primary/5' : 'hover:bg-muted'
                                                        }`}
                                                    >
                                                        {row}
                                                    </Link>
                                                ) : (
                                                    <div className="flex items-center gap-3 px-4 py-2.5 text-sm">
                                                        {row}
                                                    </div>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        ))}
                    </div>
                    <Reviews course={course} reviews={reviews} canReview={can_review} />
                </div>

                <aside className="lg:col-span-1">
                    <div className="sticky top-4 flex flex-col gap-3 rounded-xl border p-4">
                        <div className="text-2xl font-semibold">{money(course.price_cents, course.currency)}</div>
                        <EnrolBox course={course} enrolled={enrolled} progress={progress} canPurchase={can_purchase} />
                    </div>
                </aside>
            </div>
        </PublicLayout>
    );
}

function EnrolBox({
    course,
    enrolled,
    progress,
    canPurchase,
}: {
    course: Course;
    enrolled: boolean;
    progress: Progress | null;
    canPurchase: boolean;
}) {
    const { auth } = usePage<SharedData>().props;
    const { data, setData, post, processing, errors } = useForm({ coupon_code: '' });
    const [showCoupon, setShowCoupon] = useState(false);

    if (enrolled) {
        return (
            <>
                <Button asChild className="w-full">
                    <Link href={`/learn/${course.slug}`}>
                        {progress && progress.completed > 0 ? 'Continue learning' : 'Start learning'}
                    </Link>
                </Button>
                {progress && (
                    <p className="text-muted-foreground text-xs">
                        {progress.completed} of {progress.total} lessons done ({progress.percent}%)
                    </p>
                )}
            </>
        );
    }

    if (!auth.user) {
        return (
            <>
                <Button asChild className="w-full">
                    <Link href="/login">Log in to enrol</Link>
                </Button>
                <p className="text-muted-foreground text-xs">Preview lessons are open to everyone.</p>
            </>
        );
    }

    if (!canPurchase) {
        return <p className="text-muted-foreground text-sm">This course is not open for enrolment.</p>;
    }

    const free = course.price_cents === 0;

    return (
        <form noValidate
            onSubmit={(e) => {
                e.preventDefault();
                post(`/courses/${course.slug}/purchase`);
            }}
            className="flex flex-col gap-3"
        >
            {!free &&
                (showCoupon ? (
                    <div className="grid gap-1">
                        <Input
                            autoFocus
                            value={data.coupon_code}
                            onChange={(e) => setData('coupon_code', e.target.value.toUpperCase())}
                            placeholder="Coupon code"
                        />
                        {errors.coupon_code && (
                            <p className="text-xs text-red-600">{errors.coupon_code}</p>
                        )}
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={() => setShowCoupon(true)}
                        className="text-muted-foreground self-start text-xs underline"
                    >
                        Have a coupon?
                    </button>
                ))}

            <Button type="submit" className="w-full" disabled={processing}>
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                {free ? 'Enrol for free' : 'Buy this course'}
            </Button>

            {!free && (
                <p className="text-muted-foreground text-xs">
                    No card is charged — payment is not wired up yet. The order is still recorded.
                </p>
            )}
        </form>
    );
}
