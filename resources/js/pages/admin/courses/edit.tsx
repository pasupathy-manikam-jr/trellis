import { Flash } from '@/components/flash';
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
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Eye, ExternalLink, LoaderCircle, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const LESSON_TYPES: LessonType[] = ['text', 'video', 'download'];

export default function CourseEdit({ course }: { course: Course }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Courses', href: '/admin/courses' },
        { title: course.title, href: `/admin/courses/${course.slug}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={course.title} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Flash />
                <Details course={course} />
                <Curriculum course={course} />
            </div>
        </AppLayout>
    );
}

function Details({ course }: { course: Course }) {
    const { data, setData, patch, processing, errors, isDirty } = useForm({
        title: course.title,
        slug: course.slug,
        summary: course.summary ?? '',
        description: course.description ?? '',
        price_cents: course.price_cents,
        status: course.status,
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                patch(`/admin/courses/${course.slug}`);
            }}
            className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-4 rounded-xl border p-4"
        >
            <div className="flex items-center justify-between gap-4">
                <h2 className="font-semibold">Details</h2>
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
                        min={0}
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

            <Button type="submit" disabled={processing || !isDirty} className="w-fit">
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                Save details
            </Button>
        </form>
    );
}

function Curriculum({ course }: { course: Course }) {
    const sections = course.sections ?? [];
    const { data, setData, post, processing, errors, reset } = useForm({ title: '' });

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-4 rounded-xl border p-4">
            <div>
                <h2 className="font-semibold">Curriculum</h2>
                <p className="text-muted-foreground text-sm">
                    {sections.length} section{sections.length === 1 ? '' : 's'} ·{' '}
                    {sections.reduce((n, s) => n + s.lessons.length, 0)} lessons
                </p>
            </div>

            {sections.map((section, i) => (
                <SectionCard
                    key={section.id}
                    section={section}
                    isFirst={i === 0}
                    isLast={i === sections.length - 1}
                />
            ))}

            <form
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

function SectionCard({ section, isFirst, isLast }: { section: Section; isFirst: boolean; isLast: boolean }) {
    const [title, setTitle] = useState(section.title);
    const [editing, setEditing] = useState<Lesson | 'new' | null>(null);

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border">
            <div className="flex items-center gap-2 border-b p-3">
                <Input
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    onBlur={() => title !== section.title && router.patch(`/admin/sections/${section.id}`, { title })}
                    className="h-8 font-medium"
                />
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
                            className="flex-1 truncate text-left text-sm hover:underline"
                        >
                            {lesson.title}
                        </button>
                        <Badge variant="outline" className="shrink-0">
                            {lesson.type}
                        </Badge>
                        {duration(lesson.duration_sec) && (
                            <span className="text-muted-foreground shrink-0 text-xs">
                                {duration(lesson.duration_sec)}
                            </span>
                        )}
                        {lesson.is_preview && (
                            <Badge variant="secondary" className="shrink-0">
                                <Eye className="size-3" /> preview
                            </Badge>
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
    const { data, setData, post, patch, processing, errors } = useForm({
        title: lesson?.title ?? '',
        slug: lesson?.slug ?? '',
        type: lesson?.type ?? ('text' as LessonType),
        content: lesson?.content ?? '',
        duration_sec: lesson?.duration_sec ?? '',
        is_preview: lesson?.is_preview ?? false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const options = { onSuccess: onClose, preserveScroll: true };

        lesson
            ? patch(`/admin/lessons/${lesson.id}`, options)
            : post(`/admin/sections/${section.id}/lessons`, options);
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{lesson ? 'Edit lesson' : 'New lesson'}</DialogTitle>
                    <DialogDescription>In section “{section.title}”.</DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="flex flex-col gap-4">
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
                                min={0}
                                value={data.duration_sec}
                                onChange={(e) => setData('duration_sec', e.target.value)}
                            />
                        </Field>
                    </div>

                    <Field label="Content" error={errors.content}>
                        <Textarea
                            rows={8}
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            placeholder="Lesson body. Video upload lands in Phase 2."
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
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className="text-muted-foreground hover:text-red-600 shrink-0"
            onClick={() => confirm(message) && router.delete(url, { preserveScroll: true })}
        >
            <Trash2 className="size-4" />
            <span className="sr-only">Delete</span>
        </Button>
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
