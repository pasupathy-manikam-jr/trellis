import { QuizTaker } from '@/components/quiz-taker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { duration } from '@/lib/format';
import { type Course, type OutlineSection, type PlayerLesson, type PlayerQuiz, type Progress } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Award, Check, Download, FileText, ListChecks, Lock, Play } from 'lucide-react';

const icons = { video: Play, text: FileText, download: Download, quiz: ListChecks };

type Props = {
    course: Pick<Course, 'id' | 'slug' | 'title'>;
    outline: OutlineSection[];
    lesson: PlayerLesson;
    quiz: PlayerQuiz | null;
    enrolled: boolean;
    progress: Progress;
    certificate: { serial: string; issued_at: string } | null;
};

export default function LessonPlayer({
    course,
    outline,
    lesson,
    quiz,
    enrolled,
    progress,
    certificate,
}: Props) {
    const flat = outline.flatMap((s) => s.lessons);
    const index = flat.findIndex((l) => l.id === lesson.id);
    const previous = flat[index - 1];
    const next = flat[index + 1];

    const toggleComplete = () =>
        router[lesson.completed ? 'delete' : 'post'](
            `/lessons/${lesson.id}/complete`,
            {},
            { preserveScroll: true },
        );

    return (
        <PublicLayout>
            <Head title={lesson.title} />

            <div className="grid gap-8 lg:grid-cols-[280px_1fr]">
                <aside className="lg:order-first">
                    <Link href={`/courses/${course.slug}`} className="text-sm font-medium hover:underline">
                        ← {course.title}
                    </Link>

                    {certificate && (
                        <a
                            href={`/certificates/${certificate.serial}/download`}
                            className="mt-3 flex items-center gap-2 rounded-lg border border-emerald-600/20 bg-emerald-500/10 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-500/20 dark:text-emerald-400"
                        >
                            <Award className="size-4" /> Download certificate
                        </a>
                    )}

                    {enrolled && (
                        <div className="mt-3">
                            <div className="text-muted-foreground mb-1 flex justify-between text-xs">
                                <span>
                                    {progress.completed} of {progress.total} done
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
                                    className="h-full rounded-full bg-emerald-500 transition-[width]"
                                    style={{ width: `${progress.percent}%` }}
                                />
                            </div>
                        </div>
                    )}

                    <nav className="mt-4 flex flex-col gap-4">
                        {outline.map((section) => (
                            <div key={section.id}>
                                <h2 className="text-muted-foreground mb-1 text-xs font-semibold tracking-wide uppercase">
                                    {section.title}
                                </h2>
                                <ul className="flex flex-col">
                                    {section.lessons.map((item) => {
                                        const Icon = item.completed ? Check : item.locked ? Lock : icons[item.type];
                                        const current = item.id === lesson.id;

                                        const inner = (
                                            <>
                                                <Icon
                                                    className={`size-4 shrink-0 ${item.completed ? 'text-emerald-600' : 'text-muted-foreground'}`}
                                                />
                                                <span className="flex-1 truncate">{item.title}</span>
                                                {duration(item.duration_sec) && (
                                                    <span className="text-muted-foreground text-xs">
                                                        {duration(item.duration_sec)}
                                                    </span>
                                                )}
                                            </>
                                        );

                                        const className = `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                            current ? 'bg-muted font-medium' : ''
                                        }`;

                                        return (
                                            <li key={item.id}>
                                                {item.locked ? (
                                                    <div
                                                        className={`${className} text-muted-foreground cursor-not-allowed`}
                                                        title="Enrol to unlock"
                                                    >
                                                        {inner}
                                                    </div>
                                                ) : (
                                                    <Link
                                                        href={`/learn/${course.slug}/${item.id}`}
                                                        className={`${className} hover:bg-muted`}
                                                    >
                                                        {inner}
                                                    </Link>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        ))}
                    </nav>
                </aside>

                <article className="min-w-0">
                    <div className="mb-4 flex flex-wrap items-center gap-2">
                        <h1 className="text-xl font-semibold">{lesson.title}</h1>
                        {lesson.is_preview && <Badge variant="secondary">Free preview</Badge>}
                    </div>

                    {lesson.video_url ? (
                        <video
                            key={lesson.video_url}
                            controls
                            preload="metadata"
                            className="aspect-video w-full rounded-xl bg-black"
                        >
                            <source src={lesson.video_url} type="video/mp4" />
                            Your browser cannot play this video.
                        </video>
                    ) : lesson.type === 'video' ? (
                        <div className="text-muted-foreground flex aspect-video w-full items-center justify-center rounded-xl border border-dashed text-sm">
                            No video uploaded yet.
                        </div>
                    ) : null}

                    {lesson.content && (
                        <div className="mt-6 text-sm leading-relaxed whitespace-pre-wrap">{lesson.content}</div>
                    )}

                    {quiz && enrolled && (
                        <div className="mt-6">
                            <QuizTaker lessonId={lesson.id} quiz={quiz} />
                        </div>
                    )}

                    {quiz && !enrolled && (
                        <p className="text-muted-foreground mt-6 rounded-xl border border-dashed p-4 text-sm">
                            Enrol to sit this quiz.
                        </p>
                    )}

                    <div className="mt-8 flex flex-wrap items-center gap-2 border-t pt-4">
                        {enrolled && lesson.type !== 'quiz' && (
                            <Button
                                onClick={toggleComplete}
                                variant={lesson.completed ? 'secondary' : 'default'}
                            >
                                <Check className="size-4" />
                                {lesson.completed ? 'Completed' : 'Mark complete'}
                            </Button>
                        )}

                        <div className="ml-auto flex gap-2">
                            {previous && !previous.locked && (
                                <Button asChild variant="ghost">
                                    <Link href={`/learn/${course.slug}/${previous.id}`}>← Previous</Link>
                                </Button>
                            )}
                            {next && !next.locked && (
                                <Button asChild variant="ghost">
                                    <Link href={`/learn/${course.slug}/${next.id}`}>Next →</Link>
                                </Button>
                            )}
                        </div>
                    </div>

                    {!enrolled && (
                        <div className="mt-6 rounded-xl border border-dashed p-4 text-sm">
                            <p className="font-medium">You are previewing this course.</p>
                            <p className="text-muted-foreground mt-1">
                                Enrol to unlock every lesson and track your progress.
                            </p>
                            <Button asChild className="mt-3" size="sm">
                                <Link href={`/courses/${course.slug}`}>Back to course page</Link>
                            </Button>
                        </div>
                    )}
                </article>
            </div>
        </PublicLayout>
    );
}
