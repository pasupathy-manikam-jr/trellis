import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Progress } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Award, CheckCircle2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

type Enrollment = {
    id: number;
    course: { slug: string; title: string; summary: string | null };
    progress: Progress;
    completed_at: string | null;
    certificate: { serial: string } | null;
};

export default function Dashboard({ enrollments }: { enrollments: Enrollment[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">My courses</h1>

                {enrollments.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground text-sm">You are not enrolled in anything yet.</p>
                        <Button asChild variant="link">
                            <Link href="/courses">Browse courses</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {enrollments.map(({ id, course, progress, completed_at, certificate }) => (
                            <Link
                                key={id}
                                href={`/learn/${course.slug}`}
                                className="border-sidebar-border/70 dark:border-sidebar-border hover:border-foreground/20 flex flex-col gap-3 rounded-xl border p-4 transition-colors"
                            >
                                <div className="flex items-start gap-2">
                                    <h2 className="flex-1 font-medium">{course.title}</h2>
                                    {completed_at && (
                                        <CheckCircle2 className="size-4 shrink-0 text-emerald-600" />
                                    )}
                                </div>

                                {course.summary && (
                                    <p className="text-muted-foreground line-clamp-2 text-sm">{course.summary}</p>
                                )}

                                {certificate && (
                                    <span
                                        role="link"
                                        tabIndex={0}
                                        onClick={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            window.location.href = `/certificates/${certificate.serial}/download`;
                                        }}
                                        className="flex w-fit items-center gap-1.5 rounded-md border border-emerald-600/20 bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                                    >
                                        <Award className="size-3.5" /> Certificate
                                    </span>
                                )}

                                <div className="mt-auto">
                                    <div className="text-muted-foreground mb-1 flex justify-between text-xs">
                                        <span>
                                            {progress.completed}/{progress.total} lessons
                                        </span>
                                        <span>{progress.percent}%</span>
                                    </div>
                                    <div
                                        role="progressbar"
                                        aria-valuenow={progress.percent}
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        className="bg-muted h-1.5 overflow-hidden rounded-full"
                                    >
                                        <div
                                            className="bg-primary h-full rounded-full"
                                            style={{ width: `${progress.percent}%` }}
                                        />
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
