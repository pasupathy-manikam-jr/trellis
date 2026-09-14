import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type Lesson, type QuestionType, type Quiz, type QuizQuestion } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, LoaderCircle, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

export function QuizDialog({ lesson, onClose }: { lesson: Lesson; onClose: () => void }) {
    const quiz = lesson.quiz;

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Quiz</DialogTitle>
                    <DialogDescription>For lesson “{lesson.title}”.</DialogDescription>
                </DialogHeader>

                {quiz ? (
                    <div className="flex flex-col gap-6">
                        <Settings quiz={quiz} />
                        <Questions quiz={quiz} />
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed p-6 text-center">
                        <p className="text-muted-foreground mb-3 text-sm">
                            No quiz attached to this lesson yet.
                        </p>
                        <Button onClick={() => router.post(`/admin/lessons/${lesson.id}/quiz`, {}, { preserveScroll: true })}>
                            <Plus className="size-4" /> Create quiz
                        </Button>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}

function Settings({ quiz }: { quiz: Quiz }) {
    const { data, setData, patch, processing, errors, isDirty } = useForm({
        pass_percent: quiz.pass_percent,
        max_attempts: quiz.max_attempts ?? '',
        shuffle: quiz.shuffle,
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                patch(`/admin/quizzes/${quiz.id}`, { preserveScroll: true });
            }}
            className="grid gap-4 rounded-lg border p-3 sm:grid-cols-2"
        >
            <div className="grid gap-2">
                <Label htmlFor="pass">Pass mark (%)</Label>
                <Input
                    id="pass"
                    type="number"
                    min={1}
                    max={100}
                    value={data.pass_percent}
                    onChange={(e) => setData('pass_percent', Number(e.target.value))}
                />
                {errors.pass_percent && <p className="text-xs text-red-600">{errors.pass_percent}</p>}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="attempts">Max attempts</Label>
                <Input
                    id="attempts"
                    type="number"
                    min={1}
                    value={data.max_attempts}
                    onChange={(e) => setData('max_attempts', e.target.value)}
                    placeholder="unlimited"
                />
                {errors.max_attempts && <p className="text-xs text-red-600">{errors.max_attempts}</p>}
            </div>

            <label className="flex items-center gap-2 text-sm">
                <Checkbox
                    checked={data.shuffle}
                    onCheckedChange={(checked) => setData('shuffle', checked === true)}
                />
                Shuffle questions and options
            </label>

            <Button type="submit" size="sm" disabled={processing || !isDirty} className="w-fit justify-self-end">
                Save settings
            </Button>
        </form>
    );
}

function Questions({ quiz }: { quiz: Quiz }) {
    const [editing, setEditing] = useState<QuizQuestion | 'new' | null>(null);
    const total = quiz.questions.reduce((n, q) => n + q.points, 0);

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold">
                    Questions
                    <span className="text-muted-foreground ml-2 font-normal">
                        {quiz.questions.length} · {total} point{total === 1 ? '' : 's'}
                    </span>
                </h3>
                <Button type="button" size="sm" variant="secondary" onClick={() => setEditing('new')}>
                    <Plus className="size-4" /> Add question
                </Button>
            </div>

            {quiz.questions.length === 0 ? (
                <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                    A quiz with no questions cannot be attempted.
                </p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {quiz.questions.map((question, i) => (
                        <li key={question.id} className="flex items-start gap-2 p-3">
                            <button
                                type="button"
                                onClick={() => setEditing(question)}
                                className="min-w-0 flex-1 text-left"
                            >
                                <div className="truncate text-sm hover:underline">{question.prompt}</div>
                                <div className="text-muted-foreground text-xs">
                                    {question.type} · {question.points} pt ·{' '}
                                    {question.options.filter((o) => o.is_correct).length}/
                                    {question.options.length} correct
                                </div>
                            </button>

                            <div className="flex shrink-0">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    disabled={i === 0}
                                    onClick={() =>
                                        router.patch(
                                            `/admin/questions/${question.id}/move`,
                                            { direction: 'up' },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <ChevronUp className="size-4" />
                                    <span className="sr-only">Move up</span>
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    disabled={i === quiz.questions.length - 1}
                                    onClick={() =>
                                        router.patch(
                                            `/admin/questions/${question.id}/move`,
                                            { direction: 'down' },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <ChevronDown className="size-4" />
                                    <span className="sr-only">Move down</span>
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="text-muted-foreground hover:text-red-600"
                                    onClick={() =>
                                        confirm('Delete this question?') &&
                                        router.delete(`/admin/questions/${question.id}`, {
                                            preserveScroll: true,
                                        })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                    <span className="sr-only">Delete</span>
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {editing && (
                <QuestionForm
                    quiz={quiz}
                    question={editing === 'new' ? null : editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </div>
    );
}

function QuestionForm({
    quiz,
    question,
    onClose,
}: {
    quiz: Quiz;
    question: QuizQuestion | null;
    onClose: () => void;
}) {
    const { data, setData, post, patch, processing, errors } = useForm<{
        type: QuestionType;
        prompt: string;
        points: number;
        options: { text: string; is_correct: boolean }[];
    }>({
        type: question?.type ?? 'single',
        prompt: question?.prompt ?? '',
        points: question?.points ?? 1,
        options: question?.options.map((o) => ({ text: o.text, is_correct: !!o.is_correct })) ?? [
            { text: '', is_correct: true },
            { text: '', is_correct: false },
        ],
    });

    const setOption = (index: number, patchValue: Partial<{ text: string; is_correct: boolean }>) =>
        setData(
            'options',
            data.options.map((o, i) => {
                if (i !== index) {
                    // Single-choice means exactly one correct, so ticking one unticks the rest.
                    return data.type === 'single' && patchValue.is_correct ? { ...o, is_correct: false } : o;
                }

                return { ...o, ...patchValue };
            }),
        );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const options = { onSuccess: onClose, preserveScroll: true };

        question
            ? patch(`/admin/questions/${question.id}`, options)
            : post(`/admin/quizzes/${quiz.id}/questions`, options);
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{question ? 'Edit question' : 'New question'}</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="grid gap-2">
                        <Label>Prompt</Label>
                        <Textarea
                            autoFocus
                            rows={3}
                            value={data.prompt}
                            onChange={(e) => setData('prompt', e.target.value)}
                        />
                        {errors.prompt && <p className="text-sm text-red-600">{errors.prompt}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Type</Label>
                            <select
                                value={data.type}
                                onChange={(e) => {
                                    const type = e.target.value as QuestionType;
                                    setData((current) => ({
                                        ...current,
                                        type,
                                        // Dropping to single-choice would otherwise leave
                                        // several correct options and fail validation.
                                        options:
                                            type === 'single'
                                                ? current.options.map((o, i) => ({
                                                      ...o,
                                                      is_correct:
                                                          i ===
                                                          current.options.findIndex((x) => x.is_correct),
                                                  }))
                                                : current.options,
                                    }));
                                }}
                                className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                            >
                                <option value="single">Single choice</option>
                                <option value="multi">Multiple choice</option>
                            </select>
                        </div>

                        <div className="grid gap-2">
                            <Label>Points</Label>
                            <Input
                                type="number"
                                min={1}
                                value={data.points}
                                onChange={(e) => setData('points', Number(e.target.value))}
                            />
                            {errors.points && <p className="text-sm text-red-600">{errors.points}</p>}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label>Options — tick the correct one{data.type === 'multi' ? 's' : ''}</Label>

                        {data.options.map((option, i) => (
                            <div key={i} className="flex items-center gap-2">
                                <Checkbox
                                    checked={option.is_correct}
                                    onCheckedChange={(checked) =>
                                        setOption(i, { is_correct: checked === true })
                                    }
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
                                    onClick={() =>
                                        setData('options', data.options.filter((_, x) => x !== i))
                                    }
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

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            {question ? 'Save question' : 'Add question'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
