import { ConfirmButton } from '@/components/confirm-button';
import { Flash } from '@/components/flash';
import { PageHeader } from '@/components/page-header';
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
import { type BreadcrumbItem, type QuestionType } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, CircleCheck, LoaderCircle, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

type Category = { id: number; name: string; questions_count: number };

type BankOption = { id: number; text: string; is_correct: boolean };

type BankQuestion = {
    id: number;
    prompt: string;
    type: QuestionType;
    points: number;
    category_id: number | null;
    options: BankOption[];
    used_by: string[];
};

type Props = {
    course: { id: number; slug: string; title: string };
    categories: Category[];
    questions: BankQuestion[];
    types: QuestionType[];
};

export default function QuestionBank({ course, categories, questions, types }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Courses', href: '/admin/courses' },
        { title: course.title, href: `/admin/courses/${course.slug}/edit` },
        { title: 'Question bank', href: `/admin/courses/${course.slug}/questions` },
    ];

    const [filter, setFilter] = useState<number | null | 'all'>('all');
    const [editing, setEditing] = useState<BankQuestion | 'new' | null>(null);

    const shown =
        filter === 'all' ? questions : questions.filter((question) => question.category_id === filter);

    const uncategorised = questions.filter((question) => question.category_id === null).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Question bank · ${course.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Flash />

                <PageHeader
                    eyebrow="Workspace"
                    title="Question bank"
                    lede="Every question written for this course. A quiz borrows from here, so the same question can appear in a practice quiz and a final without being written twice."
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link href={`/admin/courses/${course.slug}/edit`}>Back to course</Link>
                            </Button>
                            <Button onClick={() => setEditing('new')}>
                                <Plus className="size-4" /> Write a question
                            </Button>
                        </>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    {(
                        [
                            ['all', `All (${questions.length})`],
                            ...categories.map((c) => [c.id, `${c.name} (${c.questions_count})`] as const),
                            ...(uncategorised > 0 ? [[null, `Unfiled (${uncategorised})`] as const] : []),
                        ] as [number | null | 'all', string][]
                    ).map(([value, label]) => (
                        <button
                            key={String(value)}
                            type="button"
                            onClick={() => setFilter(value)}
                            className={`rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                                filter === value
                                    ? 'bg-primary text-primary-foreground border-primary'
                                    : 'hover:border-foreground/30'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                {shown.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        {questions.length === 0
                            ? 'Nothing here yet. Questions written inside a quiz land here automatically.'
                            : 'Nothing in that category.'}
                    </p>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {shown.map((question) => (
                            <li key={question.id} className="bg-card rounded-xl border p-4">
                                <div className="flex flex-wrap items-start gap-2">
                                    <p className="min-w-0 flex-1 text-sm font-medium">{question.prompt}</p>
                                    <Badge variant="outline">{question.type}</Badge>
                                    <Badge variant="outline">{question.points} pt</Badge>

                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="text-muted-foreground"
                                        onClick={() => setEditing(question)}
                                    >
                                        <Pencil className="size-3.5" /> Edit
                                    </Button>

                                    <ConfirmButton
                                        size="icon"
                                        className="text-muted-foreground hover:text-destructive"
                                        title="Delete from the bank?"
                                        description={
                                            question.used_by.length > 0
                                                ? `It is used by ${question.used_by.join(', ')}. Deleting removes it from ${question.used_by.length === 1 ? 'that quiz' : 'those quizzes'} too.`
                                                : 'It is not used by any quiz.'
                                        }
                                        confirmLabel="Delete everywhere"
                                        onConfirm={() =>
                                            router.delete(`/admin/questions/${question.id}`, {
                                                preserveScroll: true,
                                            })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                        <span className="sr-only">Delete</span>
                                    </ConfirmButton>
                                </div>

                                <ul className="mt-2 flex flex-col gap-1">
                                    {question.options.map((option) => (
                                        <li
                                            key={option.id}
                                            className={`flex items-center gap-2 text-sm ${
                                                option.is_correct
                                                    ? 'text-emerald-700 dark:text-emerald-400'
                                                    : 'text-muted-foreground'
                                            }`}
                                        >
                                            {option.is_correct ? (
                                                <CircleCheck className="size-3.5 shrink-0" />
                                            ) : (
                                                <span className="border-muted-foreground/40 size-3.5 shrink-0 rounded-full border" />
                                            )}
                                            {option.text}
                                        </li>
                                    ))}
                                </ul>

                                {question.used_by.length > 0 && (
                                    <p className="text-muted-foreground mt-3 text-xs">
                                        Used by {question.used_by.join(', ')}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                <Categories course={course} categories={categories} />

                {editing && (
                    <QuestionDialog
                        course={course}
                        categories={categories}
                        types={types}
                        question={editing === 'new' ? null : editing}
                        onClose={() => setEditing(null)}
                    />
                )}
            </div>
        </AppLayout>
    );
}

function Categories({ course, categories }: { course: Props['course']; categories: Category[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });

    return (
        <section className="rounded-xl border p-4">
            <h2 className="font-semibold">Categories</h2>
            <p className="text-muted-foreground mt-1 text-sm">
                Folders for the bank. Removing one leaves its questions in place, just unfiled.
            </p>

            {categories.length > 0 && (
                <ul className="mt-4 divide-y rounded-lg border">
                    {categories.map((category, i) => (
                        <li key={category.id} className="flex items-center gap-3 p-3 text-sm">
                            <span className="font-medium">{category.name}</span>
                            <Badge variant="outline">{category.questions_count}</Badge>

                            <div className="ml-auto flex">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    disabled={i === 0}
                                    onClick={() =>
                                        router.patch(
                                            `/admin/question-categories/${category.id}/move`,
                                            { direction: 'up' },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <ChevronUp className="size-4" />
                                    <span className="sr-only">Move up</span>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    disabled={i === categories.length - 1}
                                    onClick={() =>
                                        router.patch(
                                            `/admin/question-categories/${category.id}/move`,
                                            { direction: 'down' },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <ChevronDown className="size-4" />
                                    <span className="sr-only">Move down</span>
                                </Button>
                                <ConfirmButton
                                    size="icon"
                                    className="text-muted-foreground hover:text-destructive"
                                    title={`Remove ${category.name}?`}
                                    description="Its questions stay in the bank, unfiled."
                                    confirmLabel="Remove"
                                    destructive={false}
                                    onConfirm={() =>
                                        router.delete(`/admin/question-categories/${category.id}`, {
                                            preserveScroll: true,
                                        })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                    <span className="sr-only">Remove</span>
                                </ConfirmButton>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <form
                noValidate
                onSubmit={(e) => {
                    e.preventDefault();
                    post(`/admin/courses/${course.slug}/question-categories`, {
                        onSuccess: () => reset(),
                    });
                }}
                className="mt-4 flex max-w-md items-start gap-2"
            >
                <div className="flex-1">
                    <Input
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Fundamentals"
                    />
                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                </div>
                <Button type="submit" variant="secondary" disabled={processing || !data.name}>
                    <Plus className="size-4" /> Add
                </Button>
            </form>
        </section>
    );
}

function QuestionDialog({
    course,
    categories,
    types,
    question,
    onClose,
}: {
    course: Props['course'];
    categories: Category[];
    types: QuestionType[];
    question: BankQuestion | null;
    onClose: () => void;
}) {
    const { data, setData, post, patch, processing, errors } = useForm<{
        type: QuestionType;
        prompt: string;
        points: number;
        question_category_id: number | '';
        options: { text: string; is_correct: boolean }[];
    }>({
        type: question?.type ?? 'single',
        prompt: question?.prompt ?? '',
        points: question?.points ?? 1,
        question_category_id: question?.category_id ?? '',
        options: question?.options.map((o) => ({ text: o.text, is_correct: o.is_correct })) ?? [
            { text: '', is_correct: true },
            { text: '', is_correct: false },
        ],
    });

    const setOption = (index: number, patchValue: Partial<{ text: string; is_correct: boolean }>) =>
        setData(
            'options',
            data.options.map((o, i) => {
                if (i !== index) {
                    return data.type === 'single' && patchValue.is_correct ? { ...o, is_correct: false } : o;
                }

                return { ...o, ...patchValue };
            }),
        );

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{question ? 'Edit question' : 'Write a question'}</DialogTitle>
                    <DialogDescription>
                        {question
                            ? 'Changes apply everywhere this question is used.'
                            : 'It goes into the bank. Add it to a quiz from the quiz builder.'}
                    </DialogDescription>
                </DialogHeader>

                <form
                    noValidate
                    onSubmit={(e) => {
                        e.preventDefault();
                        const options = { onSuccess: onClose, preserveScroll: true };

                        question
                            ? patch(`/admin/questions/${question.id}`, options)
                            : post(`/admin/courses/${course.slug}/questions`, options);
                    }}
                    className="flex flex-col gap-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="bank-prompt">Prompt</Label>
                        <Textarea
                            id="bank-prompt"
                            rows={3}
                            autoFocus
                            value={data.prompt}
                            onChange={(e) => setData('prompt', e.target.value)}
                        />
                        {errors.prompt && <p className="text-sm text-red-600">{errors.prompt}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="bank-type">Type</Label>
                            <select
                                id="bank-type"
                                value={data.type}
                                onChange={(e) => setData('type', e.target.value as QuestionType)}
                                className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                            >
                                {types.map((type) => (
                                    <option key={type} value={type}>
                                        {type}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="bank-points">Points</Label>
                            <Input
                                id="bank-points"
                                type="number"
                                value={data.points}
                                onChange={(e) => setData('points', Number(e.target.value))}
                            />
                            {errors.points && <p className="text-sm text-red-600">{errors.points}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="bank-category">Category</Label>
                            <select
                                id="bank-category"
                                value={data.question_category_id}
                                onChange={(e) =>
                                    setData(
                                        'question_category_id',
                                        e.target.value === '' ? '' : Number(e.target.value),
                                    )
                                }
                                className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                            >
                                <option value="">Unfiled</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label>Options — tick the correct one{data.type === 'multi' ? 's' : ''}</Label>

                        {data.options.map((option, i) => (
                            <div key={i} className="flex items-center gap-2">
                                <Checkbox
                                    checked={option.is_correct}
                                    onCheckedChange={(checked) => setOption(i, { is_correct: checked === true })}
                                />
                                <Input
                                    value={option.text}
                                    onChange={(e) => setOption(i, { text: e.target.value })}
                                    placeholder={`Option ${i + 1}`}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    disabled={data.options.length <= 2}
                                    onClick={() => setData('options', data.options.filter((_, x) => x !== i))}
                                >
                                    <Trash2 className="size-4" />
                                    <span className="sr-only">Remove option</span>
                                </Button>
                            </div>
                        ))}

                        {errors.options && <p className="text-sm text-red-600">{errors.options}</p>}

                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="w-fit"
                            disabled={data.options.length >= 10}
                            onClick={() => setData('options', [...data.options, { text: '', is_correct: false }])}
                        >
                            <Plus className="size-4" /> Add option
                        </Button>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            {question ? 'Save' : 'Add to bank'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
