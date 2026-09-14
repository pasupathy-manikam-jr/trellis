import { Badge } from '@/components/ui/badge';
import PublicLayout from '@/layouts/public-layout';
import { money } from '@/lib/format';
import { type Course } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function CourseCatalog({ courses }: { courses: Course[] }) {
    return (
        <PublicLayout>
            <Head title="Courses" />

            <h1 className="mb-6 text-2xl font-semibold">Courses</h1>

            {courses.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                    No published courses yet.
                </p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {courses.map((course) => (
                        <Link
                            key={course.id}
                            href={`/courses/${course.slug}`}
                            className="hover:border-foreground/20 flex flex-col gap-2 rounded-xl border p-4 transition-colors"
                        >
                            <h2 className="font-medium">{course.title}</h2>
                            {course.summary && (
                                <p className="text-muted-foreground line-clamp-3 text-sm">{course.summary}</p>
                            )}
                            <div className="mt-auto flex items-center gap-2 pt-2">
                                <Badge variant={course.price_cents === 0 ? 'secondary' : 'default'}>
                                    {money(course.price_cents, course.currency)}
                                </Badge>
                                <span className="text-muted-foreground text-xs">
                                    {course.lessons_count} lesson{course.lessons_count === 1 ? '' : 's'}
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </PublicLayout>
    );
}
