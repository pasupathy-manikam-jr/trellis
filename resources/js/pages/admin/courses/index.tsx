import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Flash } from '@/components/flash';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { money } from '@/lib/format';
import { type BreadcrumbItem, type Course } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Courses', href: '/admin/courses' }];

const statusVariant = {
    published: 'default',
    draft: 'secondary',
    archived: 'outline',
} as const;

export default function CourseIndex({ courses }: { courses: Course[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Courses" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Flash />

                <PageHeader
                    eyebrow="Workspace"
                    title="Courses"
                    actions={
                        <Button asChild>
                            <Link href="/admin/courses/create">
                                <Plus className="size-4" /> New course
                            </Link>
                        </Button>
                    }
                />

                {courses.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground text-sm">No courses yet.</p>
                        <Button asChild variant="link">
                            <Link href="/admin/courses/create">Create the first one</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="text-muted-foreground border-b text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Title</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Sections</th>
                                    <th className="px-4 py-3 font-medium">Price</th>
                                    <th className="px-4 py-3 font-medium">Instructor</th>
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
                                            <div className="text-muted-foreground text-xs">/{course.slug}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={statusVariant[course.status]}>{course.status}</Badge>
                                        </td>
                                        <td className="px-4 py-3">{course.sections_count ?? 0}</td>
                                        <td className="px-4 py-3">{money(course.price_cents, course.currency)}</td>
                                        <td className="text-muted-foreground px-4 py-3">{course.instructor?.name}</td>
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
