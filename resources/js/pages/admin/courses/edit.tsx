import { ConfirmButton } from '@/components/confirm-button';
import { Flash } from '@/components/flash';
import { QuizDialog, type BankQuestion } from '@/components/quiz-builder';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { duration } from '@/lib/format';
import { type BreadcrumbItem, type Course, type Lesson, type LessonType, type Section } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronUp, ExternalLink, Eye, EyeOff, ListChecks, LoaderCircle, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const LESSON_TYPES: LessonType[] = ['text', 'video', 'download', 'quiz', 'assignment'];

type AdminEnrollment = {
    id: number;
    source: string;
    started_at: string;
    completed_at: string | null;
    user: { id: number; name: string; email: string };
    progress: { completed: number; total: number; percent: number };
};

type AdminCategory = { id: number; name: string };

export default function CourseEdit({
    course,
    enrollments,
    categories,
    bank,
}: {
    course: Course;
    enrollments: AdminEnrollment[];
    categories: AdminCategory[];
    bank: BankQuestion[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Courses', href: '/admin/courses' },
        { title: course.title, href: `/admin/courses/${course.slug}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={course.title} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Flash />
                <Details course={course} categories={categories} />
                <Curriculum course={course} bank={bank} />
                <Enrollments course={course} enrollments={enrollments} />
            </div>
        </AppLayout>
    );
}

function Details({ course, categories }: { course: Course; categories: AdminCategory[] }) {
    const { data, setData, post, processing, errors, isDirty } = useForm<{
        title: string;
        slug: string;
        summary: string;
        description: string;
        price_cents: number;
        status: Course['status'];
        thumbnail: File | null;
        categories: number[];
        _method: string;
    }>({
        title: course.title,
        slug: course.slug,
        summary: course.summary ?? '',
        description: course.description ?? '',
        price_cents: course.price_cents,
        status: course.status,
        thumbnail: null,
        categories: course.category_ids ?? [],
        // A multipart body can only be POSTed, so the update spoofs PATCH.
        _method: 'patch',
    });

    return (
        <form noValidate
            onSubmit={(e) => {
                e.preventDefault();
                post(`/admin/courses/${course.slug}`, { forceFormData: true });
            }}
            className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-4 rounded-xl border p-4"
        >
            <div className="flex items-center justify-between gap-4">
                <h2 className="font-semibold">Details</h2>
                <Button asChild variant="outline" size="sm">
                    <Link href={`/admin/courses/${course.slug}/questions`}>Question bank</Link>
                </Button>
                <Button asChild variant="outline" size="sm">
                    <Link href={`/admin/courses/${course.slug}/gradebook`}>Gradebook</Link>
                </Button>
                {course.status === 'published' && (
                    <Button asChild variant="ghost" size="sm">
                        <Link href={`/courses/${course.slug}`}>
                            View public page <ExternalLink className="size-3.5" />
                        </Link>
                    </Button>
                )}
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <Field label="Title" error={errors.title}>
                    <Input value={data.title} onChange={(e) => setData('title', e.target.value)} />
                </Field>

                <Field label="URL slug" error={errors.slug}>
                    <Input value={data.slug} onChange={(e) => setData('slug', e.target.value)} />
                </Field>
            </div>

            <Field label="Summary" error={errors.summary}>
                <Input value={data.summary} onChange={(e) => setData('summary', e.target.value)} />
            </Field>

            <Field label="Categories" error={errors.categories}>
                {categories.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        None defined yet — add some under Categories.
                    </p>
                ) : (
                    <div className="flex flex-wrap gap-x-4 gap-y-2">
                        {categories.map((category) => (
                            <label key={category.id} className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={data.categories.includes(category.id)}
                                    onCheckedChange={(checked) =>
                                        setData(
                                            'categories',
                                            checked === true
                                                ? [...data.categories, category.id]
                                                : data.categories.filter((id) => id !== category.id),
                                        )
                                    }
                                />
                                {category.name}
                            </label>
                        ))}
                    </div>
                )}
            </Field>

            <Field label="Cover image" error={errors.thumbnail}>
                <div className="flex items-center gap-3">
                    {course.thumbnail_url && (
                        <img
                            src={course.thumbnail_url}
                            alt=""
                            className="h-16 w-24 shrink-0 rounded-md border object-cover"
                        />
                    )}
                    <Input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(e) => setData('thumbnail', e.target.files?.[0] ?? null)}
                    />
                </div>
                <p className="text-muted-foreground text-xs">
                    Shown on the catalogue and behind the course header. Public — it is the
                    marketing image, not gated content. Max 4 MB.
                </p>
            </Field>

            <Field label="Description" error={errors.description}>
                <Textarea
                    rows={6}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
            </Field>

            <div className="grid gap-4 md:grid-cols-2">
                <Field label="Price (cents — 0 is free)" error={errors.price_cents}>
                    <Input
                        type="number"
                        value={data.price_cents}
                        onChange={(e) => setData('price_cents', Number(e.target.value))}
                    />
                </Field>

                <Field label="Status" error={errors.status}>
                    <select
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value as Course['status'])}
                        className="border-input bg-background focus-visible:ring-ring h-10 rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                    >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </Field>
            </div>

            <Button type="submit" disabled={processing || (!isDirty && !data.thumbnail)} className="w-fit">
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                Save details
            </Button>
        </form>
    );
}

function Curriculum({ course, bank }: { course: Course; bank: BankQuestion[] }) {
    const sections = course.sections ?? [];
    const previewCount = sections.reduce(
        (n, section) => n + section.lessons.filter((lesson) => lesson.is_preview).length,
        0,
    );
    const { data, setData, post, processing, errors, reset } = useForm({ title: '' });

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-4 rounded-xl border p-4">
            <div>
                <h2 className="font-semibold">Curriculum</h2>
                <p className="text-muted-foreground text-sm">
                    {sections.length} section{sections.length === 1 ? '' : 's'} ·{' '}
                    {sections.reduce((n, s) => n + s.lessons.length, 0)} lessons ·{' '}
                    {previewCount} free preview{previewCount === 1 ? '' : 's'}
                </p>
            </div>

            <p className="text-muted-foreground text-xs">
                <strong className="text-foreground">Edit</strong> opens the lesson — its body, type,
                video, quiz or brief. <strong className="text-foreground">preview / private</strong>
                decides whether a visitor can read it without enrolling.
            </p>

            {previewCount === 0 && sections.some((s) => s.lessons.length > 0) && (
                <p className="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-800 dark:text-amber-300">
                    Nothing in this course is readable without enrolling, so a visitor has no way to
                    judge it. Mark a lesson <strong>preview</strong> below — a strong one that stands on
                    its own.
                </p>
            )}

            {sections.map((section, i) => (
                <SectionCard
                    key={section.id}
                    section={section}
                    bank={bank}
                    isFirst={i === 0}
                    isLast={i === sections.length - 1}
                />
            ))}

            <form noValidate
                onSubmit={(e) => {
                    e.preventDefault();
                    post(`/admin/courses/${course.slug}/sections`, { onSuccess: () => reset() });
                }}
                className="flex items-start gap-2"
            >
                <div className="flex-1">
                    <Input
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder="New section title"
                    />
                    {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                </div>
                <Button type="submit" variant="secondary" disabled={processing || !data.title}>
                    <Plus className="size-4" /> Add section
                </Button>
            </form>
        </div>
    );
}

function SectionCard({
    section,
    bank,
    isFirst,
    isLast,
}: {
    section: Section;
    bank: BankQuestion[];
    isFirst: boolean;
    isLast: boolean;
}) {
    const [editing, setEditing] = useState<Lesson | 'new' | null>(null);
    const [quizFor, setQuizFor] = useState<Lesson | null>(null);
    const { data, setData, patch, errors } = useForm({ title: section.title });

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border">
            <div className="flex items-start gap-2 border-b p-3">
                <div className="flex-1">
                    <Input
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        onBlur={() =>
                            data.title !== section.title &&
                            patch(`/admin/sections/${section.id}`, { preserveScroll: true })
                        }
                        className="h-8 font-medium"
                    />
                    {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                </div>
                <Move type="sections" id={section.id} isFirst={isFirst} isLast={isLast} />
                <Destroy
                    url={`/admin/sections/${section.id}`}
                    confirm={`Delete "${section.title}" and its ${section.lessons.length} lesson(s)?`}
                />
            </div>

            <ul className="divide-y">
                {section.lessons.map((lesson, i) => (
                    <li key={lesson.id} className="flex items-center gap-2 px-3 py-2">
                        <button
                            type="button"
                            onClick={() => setEditing(lesson)}
                            className="hover:text-primary flex-1 truncate text-left text-sm hover:underline"
                            title="Edit this lesson"
                        >
                            {lesson.title}
                        </button>

                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="text-muted-foreground shrink-0"
                            onClick={() => setEditing(lesson)}
                        >
                            <Pencil className="size-3.5" /> Edit
                        </Button>
                        <Badge variant="outline" className="shrink-0">
                            {lesson.type}
                        </Badge>
                        {duration(lesson.duration_sec) && (
                            <span className="text-muted-foreground shrink-0 text-xs">
                                {duration(lesson.duration_sec)}
                            </span>
                        )}
                        <Button
                            type="button"
                            variant={lesson.is_preview ? 'secondary' : 'ghost'}
                            size="sm"
                            className={`shrink-0 ${lesson.is_preview ? '' : 'text-muted-foreground'}`}
                            title={
                                lesson.is_preview
                                    ? 'Readable without enrolling — click to close it'
                                    : 'Open this lesson to everyone as a free preview'
                            }
                            onClick={() =>
                                router.patch(`/admin/lessons/${lesson.id}/preview`, {}, { preserveScroll: true })
                            }
                        >
                            {lesson.is_preview ? <Eye className="size-3.5" /> : <EyeOff className="size-3.5" />}
                            {lesson.is_preview ? 'preview' : 'private'}
                        </Button>
                        {lesson.type === 'quiz' && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="shrink-0"
                                onClick={() => setQuizFor(lesson)}
                            >
                                <ListChecks className="size-4" />
                                {lesson.quiz ? `${lesson.quiz.questions.length} Q` : 'Set up'}
                            </Button>
                        )}
                        <Move
                            type="lessons"
                            id={lesson.id}
                            isFirst={i === 0}
                            isLast={i === section.lessons.length - 1}
                        />
                        <Destroy url={`/admin/lessons/${lesson.id}`} confirm={`Delete "${lesson.title}"?`} />
                    </li>
                ))}
            </ul>

            <div className="p-3">
                <Button type="button" variant="ghost" size="sm" onClick={() => setEditing('new')}>
                    <Plus className="size-4" /> Add lesson
                </Button>
            </div>

            {quizFor && <QuizDialog lesson={quizFor} bank={bank} onClose={() => setQuizFor(null)} />}

            {editing && (
                <LessonDialog
                    section={section}
                    lesson={editing === 'new' ? null : editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </div>
    );
}

function LessonDialog({
    section,
    lesson,
    onClose,
}: {
    section: Section;
    lesson: Lesson | null;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        slug: string;
        type: LessonType;
        content: string;
        duration_sec: number | string;
        is_preview: boolean;
        drip_days: number | string;
        assignment: { instructions: string; points: number | string; due_days: number | string };
        video: File | null;
        _method?: string;
    }>({
        title: lesson?.title ?? '',
        slug: lesson?.slug ?? '',
        type: lesson?.type ?? 'text',
        content: lesson?.content ?? '',
        duration_sec: lesson?.duration_sec ?? '',
        is_preview: lesson?.is_preview ?? false,
        drip_days: lesson?.drip_days ?? 0,
        video: null,
        assignment: {
            instructions: lesson?.assignment?.instructions ?? '',
            points: lesson?.assignment?.points ?? 100,
            due_days: lesson?.assignment?.due_days ?? '',
        },
        // A multipart body can only be POSTed, so an edit spoofs PATCH.
        ...(lesson ? { _method: 'patch' } : {}),
    });

    // Nested rules report back as "assignment.points"; Inertia's typed errors
    // only know the form's top-level keys, so read those from the shared bag.
    const nested = usePage().props.errors as Record<string, string>;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        post(lesson ? `/admin/lessons/${lesson.id}` : `/admin/sections/${section.id}/lessons`, {
            onSuccess: onClose,
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{lesson ? 'Edit lesson' : 'New lesson'}</DialogTitle>
                    <DialogDescription>In section “{section.title}”.</DialogDescription>
                </DialogHeader>

                <form noValidate onSubmit={submit} className="flex flex-col gap-4">
                    <Field label="Title" error={errors.title}>
                        <Input autoFocus value={data.title} onChange={(e) => setData('title', e.target.value)} />
                    </Field>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Type" error={errors.type}>
                            <select
                                value={data.type}
                                onChange={(e) => setData('type', e.target.value as LessonType)}
                                className="border-input bg-background focus-visible:ring-ring h-10 rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                            >
                                {LESSON_TYPES.map((t) => (
                                    <option key={t} value={t}>
                                        {t}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Duration (seconds)" error={errors.duration_sec}>
                            <Input
                                type="number"
                                value={data.duration_sec}
                                onChange={(e) => setData('duration_sec', e.target.value)}
                            />
                        </Field>

                        <Field label="Unlock after (days)" error={errors.drip_days}>
                            <Input
                                type="number"
                                value={data.drip_days}
                                onChange={(e) => setData('drip_days', e.target.value)}
                            />
                            <p className="text-muted-foreground text-xs">
                                Days after each learner enrols. 0 opens immediately.
                            </p>
                        </Field>
                    </div>

                    {data.type === 'video' && (
                        <Field label="Video file" error={errors.video}>
                            <Input
                                type="file"
                                accept="video/mp4,video/webm,video/quicktime"
                                onChange={(e) => setData('video', e.target.files?.[0] ?? null)}
                            />
                            <p className="text-muted-foreground text-xs">
                                Stored on the private disk and streamed through an access-checked route.
                                Max 500 MB.
                            </p>
                        </Field>
                    )}

                    {data.type === 'assignment' && (
                        <div className="grid gap-4 rounded-lg border p-3">
                            <Field label="The brief" error={nested['assignment.instructions']}>
                                <Textarea
                                    rows={6}
                                    value={data.assignment.instructions}
                                    onChange={(e) =>
                                        setData('assignment', {
                                            ...data.assignment,
                                            instructions: e.target.value,
                                        })
                                    }
                                    placeholder="What should they produce, and what does good look like?"
                                />
                            </Field>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Out of" error={nested['assignment.points']}>
                                    <Input
                                        type="number"
                                        value={data.assignment.points}
                                        onChange={(e) =>
                                            setData('assignment', {
                                                ...data.assignment,
                                                points: e.target.value,
                                            })
                                        }
                                    />
                                </Field>

                                <Field label="Due after (days)" error={nested['assignment.due_days']}>
                                    <Input
                                        type="number"
                                        value={data.assignment.due_days}
                                        onChange={(e) =>
                                            setData('assignment', {
                                                ...data.assignment,
                                                due_days: e.target.value,
                                            })
                                        }
                                        placeholder="no deadline"
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Counted from each learner's enrolment. Late work is accepted and
                                        flagged, never refused.
                                    </p>
                                </Field>
                            </div>
                        </div>
                    )}

                    <Field label="Content" error={errors.content}>
                        <Textarea
                            rows={8}
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            placeholder="The written part of the lesson."
                        />
                    </Field>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={data.is_preview}
                            onCheckedChange={(checked) => setData('is_preview', checked === true)}
                        />
                        Free preview — visible without enrolling
                    </label>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            {lesson ? 'Save lesson' : 'Add lesson'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// ponytail: up/down buttons instead of drag-and-drop — no dnd dependency, and
// keyboard/screen-reader accessible for free. Swap in @dnd-kit if courses grow
// long enough that clicking up 30 times is the bottleneck.
function Move({
    type,
    id,
    isFirst,
    isLast,
}: {
    type: 'sections' | 'lessons';
    id: number;
    isFirst: boolean;
    isLast: boolean;
}) {
    const move = (direction: 'up' | 'down') =>
        router.patch(`/admin/${type}/${id}/move`, { direction }, { preserveScroll: true });

    return (
        <div className="flex shrink-0">
            <Button type="button" variant="ghost" size="icon" disabled={isFirst} onClick={() => move('up')}>
                <ChevronUp className="size-4" />
                <span className="sr-only">Move up</span>
            </Button>
            <Button type="button" variant="ghost" size="icon" disabled={isLast} onClick={() => move('down')}>
                <ChevronDown className="size-4" />
                <span className="sr-only">Move down</span>
            </Button>
        </div>
    );
}

function Destroy({ url, confirm: message }: { url: string; confirm: string }) {
    return (
        <ConfirmButton
            size="icon"
            className="text-muted-foreground hover:text-destructive shrink-0"
            title={message}
            description="This cannot be undone."
            confirmLabel="Delete"
            onConfirm={() => router.delete(url, { preserveScroll: true })}
        >
            <Trash2 className="size-4" />
            <span className="sr-only">Delete</span>
        </ConfirmButton>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-sm text-red-600">{error}</p>}
        </div>
    );
}

function Enrollments({ course, enrollments }: { course: Course; enrollments: AdminEnrollment[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({ email: '' });

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-4 rounded-xl border p-4">
            <div>
                <h2 className="font-semibold">Enrolments</h2>
                <p className="text-muted-foreground text-sm">
                    {enrollments.length} enrolled. Until checkout exists, this is how someone gets access
                    to a paid course.
                </p>
            </div>

            <form noValidate
                onSubmit={(e) => {
                    e.preventDefault();
                    post(`/admin/courses/${course.slug}/enrollments`, { onSuccess: () => reset() });
                }}
                className="flex items-start gap-2"
            >
                <div className="flex-1">
                    <Input
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="student@lms.test"
                    />
                    {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                </div>
                <Button type="submit" variant="secondary" disabled={processing || !data.email}>
                    <Plus className="size-4" /> Enrol
                </Button>
            </form>

            {enrollments.length > 0 && (
                <ul className="divide-y rounded-lg border">
                    {enrollments.map((enrollment) => (
                        <li key={enrollment.id} className="flex items-center gap-3 px-3 py-2 text-sm">
                            <div className="min-w-0 flex-1">
                                <div className="truncate font-medium">{enrollment.user.name}</div>
                                <div className="text-muted-foreground truncate text-xs">
                                    {enrollment.user.email}
                                </div>
                            </div>
                            <Badge variant="outline" className="shrink-0">
                                {enrollment.source}
                            </Badge>
                            <span className="text-muted-foreground shrink-0 text-xs">
                                {enrollment.progress.percent}%
                            </span>
                            {enrollment.completed_at && (
                                <Badge variant="secondary" className="shrink-0">
                                    completed
                                </Badge>
                            )}
                            <Destroy
                                url={`/admin/enrollments/${enrollment.id}`}
                                confirm={`Revoke access for ${enrollment.user.name}?`}
                            />
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
