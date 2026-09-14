import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { duration, money } from '@/lib/format';
import { type Course } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Eye, FileText, Play, Download } from 'lucide-react';

const icons = { video: Play, text: FileText, download: Download };

export default function CourseShow({ course }: { course: Course }) {
    const sections = course.sections ?? [];
    const lessonCount = sections.reduce((n, s) => n + s.lessons.length, 0);

    return (
        <PublicLayout>
            <Head title={course.title} />

            <Button asChild variant="ghost" size="sm" className="mb-4 -ml-2">
                <Link href="/courses">← All courses</Link>
            </Button>

            <div className="grid gap-8 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <h1 className="text-2xl font-semibold">{course.title}</h1>
                    {course.summary && <p className="text-muted-foreground mt-2">{course.summary}</p>}
                    {course.instructor && (
                        <p className="text-muted-foreground mt-2 text-sm">By {course.instructor.name}</p>
                    )}

                    {course.description && (
                        <div className="mt-6 text-sm leading-relaxed whitespace-pre-wrap">{course.description}</div>
                    )}

                    <h2 className="mt-8 font-semibold">
                        Curriculum
                        <span className="text-muted-foreground ml-2 text-sm font-normal">
                            {sections.length} section{sections.length === 1 ? '' : 's'} · {lessonCount} lessons
                        </span>
                    </h2>

                    <div className="mt-3 flex flex-col gap-3">
                        {sections.map((section) => (
                            <div key={section.id} className="rounded-lg border">
                                <div className="border-b px-4 py-2 text-sm font-medium">{section.title}</div>
                                <ul className="divide-y">
                                    {section.lessons.map((lesson) => {
                                        const Icon = icons[lesson.type];

                                        return (
                                            <li key={lesson.id} className="flex items-center gap-3 px-4 py-2 text-sm">
                                                <Icon className="text-muted-foreground size-4 shrink-0" />
                                                <span className="flex-1 truncate">{lesson.title}</span>
                                                {lesson.is_preview && (
                                                    <Badge variant="secondary" className="shrink-0">
                                                        <Eye className="size-3" /> preview
                                                    </Badge>
                                                )}
                                                {duration(lesson.duration_sec) && (
                                                    <span className="text-muted-foreground shrink-0 text-xs">
                                                        {duration(lesson.duration_sec)}
                                                    </span>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>

                <aside className="lg:col-span-1">
                    <div className="sticky top-4 flex flex-col gap-3 rounded-xl border p-4">
                        <div className="text-2xl font-semibold">{money(course.price_cents, course.currency)}</div>
                        <Button disabled className="w-full">
                            Enrol — Phase 2
                        </Button>
                        <p className="text-muted-foreground text-xs">
                            Enrolment and the lesson player arrive in the next phase.
                        </p>
                    </div>
                </aside>
            </div>
        </PublicLayout>
    );
}
