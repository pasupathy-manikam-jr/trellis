import { ConfirmButton } from '@/components/confirm-button';
import { Flash } from '@/components/flash';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Download, LoaderCircle, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

type Item = {
    id: number;
    name: string;
    source: 'quiz' | 'assignment' | 'manual';
    max_points: number;
    weight: number;
    lesson_id: number | null;
};

type Learner = {
    id: number;
    name: string;
    email: string;
    grade: { points: number; max: number; percent: number | null; graded: number; total: number };
    marks: Record<number, number>;
};

type Awaiting = {
    id: number;
    learner: string;
    lesson: string;
    submitted_at: string;
    has_file: boolean;
    body: string | null;
};

type Props = {
    course: { id: number; slug: string; title: string };
    items: Item[];
    learners: Learner[];
    awaiting: Awaiting[];
};

export default function Gradebook({ course, items, learners, awaiting }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Courses', href: '/admin/courses' },
        { title: course.title, href: `/admin/courses/${course.slug}/edit` },
        { title: 'Gradebook', href: `/admin/courses/${course.slug}/gradebook` },
    ];

    const [marking, setMarking] = useState<{ item: Item; learner: Learner } | null>(null);
    const totalWeight = items.reduce((n, i) => n + i.weight, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Gradebook · ${course.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Flash />

                <PageHeader
                    eyebrow="Workspace"
                    title="Gradebook"
                    lede="A learner's grade is a weighted average over the columns marked so far. Work not yet marked is left out rather than counted as zero."
                    actions={
                        <Button asChild variant="outline">
                            <Link href={`/admin/courses/${course.slug}/edit`}>Back to course</Link>
                        </Button>
                    }
                />

                {awaiting.length > 0 && (
                    <section>
                        <h2 className="mb-3 font-semibold">
                            Waiting to be marked
                            <Badge variant="secondary" className="ml-2">
                                {awaiting.length}
                            </Badge>
                        </h2>
                        <ul className="divide-y rounded-xl border">
                            {awaiting.map((item) => (
                                <li key={item.id} className="flex flex-wrap items-center gap-3 p-3 text-sm">
                                    <span className="font-medium">{item.learner}</span>
                                    <span className="text-muted-foreground">{item.lesson}</span>
                                    <span className="text-muted-foreground text-xs">
                                        {new Date(item.submitted_at).toLocaleDateString()}
                                    </span>
                                    {item.has_file && (
                                        <Button asChild variant="ghost" size="sm">
                                            <a href={`/submissions/${item.id}/file`}>
                                                <Download className="size-3.5" /> File
                                            </a>
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                        <p className="text-muted-foreground mt-2 text-xs">
                            Mark these in the grid below — click a cell.
                        </p>
                    </section>
                )}

                {items.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        Nothing is graded yet. Quizzes and assignments get a column automatically, or add
                        one by hand below.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 border-b">
                                <tr>
                                    <th className="text-muted-foreground px-4 py-3 text-left text-xs font-semibold tracking-widest uppercase">
                                        Learner
                                    </th>
                                    {items.map((item) => (
                                        <th key={item.id} className="px-3 py-3 text-center font-medium">
                                            <div className="whitespace-nowrap">{item.name}</div>
                                            <div className="text-muted-foreground text-xs font-normal">
                                                /{item.max_points} · weight {item.weight}
                                            </div>
                                        </th>
                                    ))}
                                    <th className="text-muted-foreground px-4 py-3 text-right text-xs font-semibold tracking-widest uppercase">
                                        Grade
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {learners.map((learner) => (
                                    <tr key={learner.id} className="border-b last:border-0">
                                        <td className="px-4 py-2.5">
                                            <div className="font-medium">{learner.name}</div>
                                            <div className="text-muted-foreground text-xs">
                                                {learner.email}
                                            </div>
                                        </td>
                                        {items.map((item) => (
                                            <td key={item.id} className="px-3 py-2.5 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() => setMarking({ item, learner })}
                                                    className="hover:bg-muted min-w-14 rounded-md px-2 py-1 tabular-nums"
                                                >
                                                    {learner.marks[item.id] ?? (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                </button>
                                            </td>
                                        ))}
                                        <td className="px-4 py-2.5 text-right font-semibold tabular-nums">
                                            {learner.grade.percent === null ? (
                                                <span className="text-muted-foreground font-normal">—</span>
                                            ) : (
                                                `${learner.grade.percent}%`
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {learners.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={items.length + 2}
                                            className="text-muted-foreground px-4 py-8 text-center"
                                        >
                                            Nobody is enrolled yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}

                <Columns course={course} items={items} totalWeight={totalWeight} />

                {marking && (
                    <MarkDialog
                        item={marking.item}
                        learner={marking.learner}
                        onClose={() => setMarking(null)}
                    />
                )}
            </div>
        </AppLayout>
    );
}

function Columns({
    course,
    items,
    totalWeight,
}: {
    course: Props['course'];
    items: Item[];
    totalWeight: number;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        max_points: 100,
        weight: 1,
    });

    return (
        <section className="rounded-xl border p-4">
            <h2 className="font-semibold">Columns</h2>
            <p className="text-muted-foreground mt-1 text-sm">
                Weights are relative, not percentages — they are normalised against the course total
                ({totalWeight || 0}), so they never have to add up to anything.
            </p>

            {items.length > 0 && (
                <ul className="mt-4 divide-y rounded-lg border">
                    {items.map((item) => (
                        <li key={item.id} className="flex flex-wrap items-center gap-3 p-3 text-sm">
                            <span className="font-medium">{item.name}</span>
                            <Badge variant="outline">{item.source}</Badge>
                            <span className="text-muted-foreground text-xs">
                                out of {item.max_points} · weight {item.weight}
                                {totalWeight > 0 && (
                                    <> · {Math.round((item.weight / totalWeight) * 100)}% of the grade</>
                                )}
                            </span>

                            {item.source === 'manual' ? (
                                <ConfirmButton
                                    size="icon"
                                    className="text-muted-foreground hover:text-destructive ml-auto"
                                    title={`Remove “${item.name}”?`}
                                    description="Any marks recorded against it go too."
                                    confirmLabel="Remove column"
                                    onConfirm={() =>
                                        router.delete(`/admin/grade-items/${item.id}`, {
                                            preserveScroll: true,
                                        })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                    <span className="sr-only">Remove</span>
                                </ConfirmButton>
                            ) : (
                                <span className="text-muted-foreground ml-auto text-xs">
                                    from the activity
                                </span>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <form
                noValidate
                onSubmit={(e) => {
                    e.preventDefault();
                    post(`/admin/courses/${course.slug}/grade-items`, { onSuccess: () => reset() });
                }}
                className="mt-4 grid gap-3 sm:grid-cols-[1fr_120px_120px_auto]"
            >
                <div className="grid gap-1.5">
                    <Label htmlFor="col-name">Add a column</Label>
                    <Input
                        id="col-name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Participation"
                    />
                    {errors.name && <p className="text-xs text-red-600">{errors.name}</p>}
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="col-max">Out of</Label>
                    <Input
                        id="col-max"
                        type="number"
                        value={data.max_points}
                        onChange={(e) => setData('max_points', Number(e.target.value))}
                    />
                    {errors.max_points && <p className="text-xs text-red-600">{errors.max_points}</p>}
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="col-weight">Weight</Label>
                    <Input
                        id="col-weight"
                        type="number"
                        value={data.weight}
                        onChange={(e) => setData('weight', Number(e.target.value))}
                    />
                    {errors.weight && <p className="text-xs text-red-600">{errors.weight}</p>}
                </div>
                <Button type="submit" variant="secondary" disabled={processing || !data.name} className="self-end">
                    <Plus className="size-4" /> Add
                </Button>
            </form>
        </section>
    );
}

function MarkDialog({
    item,
    learner,
    onClose,
}: {
    item: Item;
    learner: Learner;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: learner.id,
        points: learner.marks[item.id] ?? 0,
        feedback: '',
    });

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{item.name}</DialogTitle>
                    <DialogDescription>
                        Marking {learner.name}, out of {item.max_points}.
                    </DialogDescription>
                </DialogHeader>

                <form
                    noValidate
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(`/admin/grade-items/${item.id}/grades`, {
                            preserveScroll: true,
                            onSuccess: onClose,
                        });
                    }}
                    className="flex flex-col gap-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="mark-points">Points</Label>
                        <Input
                            id="mark-points"
                            type="number"
                            step="0.5"
                            autoFocus
                            value={data.points}
                            onChange={(e) => setData('points', Number(e.target.value))}
                        />
                        {errors.points && <p className="text-sm text-red-600">{errors.points}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="mark-feedback">Feedback</Label>
                        <Textarea
                            id="mark-feedback"
                            rows={5}
                            value={data.feedback}
                            onChange={(e) => setData('feedback', e.target.value)}
                            placeholder="What they did well, and what to do next time."
                        />
                        {errors.feedback && <p className="text-sm text-red-600">{errors.feedback}</p>}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Record mark
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
