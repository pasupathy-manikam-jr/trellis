import { CourseCard } from '@/components/course-card';
import PublicLayout from '@/layouts/public-layout';
import { type CourseCardData } from '@/types';
import { Head } from '@inertiajs/react';

export default function CourseCatalog({ courses }: { courses: CourseCardData[] }) {
    return (
        <PublicLayout>
            <Head title="Courses" />

            <div className="mb-10">
                <span className="text-primary text-xs font-semibold tracking-widest uppercase">Catalogue</span>
                <h1 className="mt-2 text-3xl font-bold tracking-tight">Courses</h1>
                <div className="bg-primary mt-4 h-1 w-16 rounded-full" />
                <p className="text-muted-foreground mt-4 text-sm">
                    {courses.length} course{courses.length === 1 ? '' : 's'} available.
                </p>
            </div>

            {courses.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border border-dashed p-16 text-center text-sm">
                    No published courses yet.
                </p>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {courses.map((course) => (
                        <CourseCard key={course.id} course={course} />
                    ))}
                </div>
            )}
        </PublicLayout>
    );
}
