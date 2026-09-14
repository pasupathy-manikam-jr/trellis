import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { money } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { PageHeader, StatTile } from '@/components/page-header';
import { Head, Link } from '@inertiajs/react';
import { ChartNoAxesColumn, GraduationCap, Trophy, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Insights', href: '/admin/insights' }];

type Row = {
    id: number;
    slug: string;
    title: string;
    status: string;
    price_cents: number;
    currency: string;
    enrollments: number;
    completed: number;
    completion_rate: number | null;
    lessons: number;
    revenue_cents: number;
    rating: number | null;
    reviews: number;
};

type Totals = {
    revenue_cents: number;
    enrollments: number;
    completed: number;
    published: number;
};

export default function Insights({ courses, totals }: { courses: Row[]; totals: Totals }) {
    const stats = [
        { label: 'Revenue', value: money(totals.revenue_cents), icon: ChartNoAxesColumn, tint: 'var(--brand-teal)' },
        { label: 'Enrolments', value: totals.enrollments.toLocaleString(), icon: Users, tint: 'var(--brand-sky)' },
        { label: 'Completions', value: totals.completed.toLocaleString(), icon: Trophy, tint: 'var(--brand-amber)' },
        { label: 'Published courses', value: totals.published.toLocaleString(), icon: GraduationCap, tint: 'var(--brand-violet)' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Insights" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    eyebrow="Performance"
                    title="Insights"
                    lede="Revenue counts paid orders only — refunds are excluded."
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <StatTile key={stat.label} {...stat} />
                    ))}
                </div>

                {courses.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        No courses yet.
                    </p>
                ) : (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="text-muted-foreground border-b text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Course</th>
                                    <th className="px-4 py-3 text-right font-medium">Enrolled</th>
                                    <th className="px-4 py-3 text-right font-medium">Completed</th>
                                    <th className="px-4 py-3 text-right font-medium">Revenue</th>
                                    <th className="px-4 py-3 text-right font-medium">Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                {courses.map((course) => (
                                    <tr key={course.id} className="border-b last:border-0">
                                        <td className="px-4 py-3">
                                            <Link
                                                href={`/admin/courses/${course.slug}/edit`}
                                                className="font-medium hover:underline"
                                            >
                                                {course.title}
                                            </Link>
                                            <div className="text-muted-foreground mt-0.5 flex items-center gap-2 text-xs">
                                                <Badge
                                                    variant={course.status === 'published' ? 'secondary' : 'outline'}
                                                >
                                                    {course.status}
                                                </Badge>
                                                {course.lessons} lesson{course.lessons === 1 ? '' : 's'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">{course.enrollments}</td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {course.completion_rate === null ? (
                                                <span className="text-muted-foreground">—</span>
                                            ) : (
                                                <>
                                                    {course.completed}
                                                    <span className="text-muted-foreground ml-1 text-xs">
                                                        ({course.completion_rate}%)
                                                    </span>
                                                </>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {money(course.revenue_cents, course.currency)}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {course.rating === null ? (
                                                <span className="text-muted-foreground">—</span>
                                            ) : (
                                                <>
                                                    {course.rating.toFixed(1)}
                                                    <span className="text-muted-foreground ml-1 text-xs">
                                                        ({course.reviews})
                                                    </span>
                                                </>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
