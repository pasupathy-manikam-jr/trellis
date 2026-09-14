import { ConfirmButton } from '@/components/confirm-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { router, useForm } from '@inertiajs/react';
import { Check, CircleHelp, LoaderCircle, MessageSquare, Trash2 } from 'lucide-react';
import { useState } from 'react';

export type Comment = {
    id: number;
    body: string;
    created_at: string;
    author: string;
    from_staff: boolean;
    can_delete: boolean;
};

export type Question = Comment & {
    resolved: boolean;
    can_resolve: boolean;
    replies: Comment[];
};

function when(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function Initials({ name, staff }: { name: string; staff: boolean }) {
    const initials = name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('');

    return (
        <span
            className={`flex size-7 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold ${
                staff ? 'bg-primary text-primary-foreground' : 'bg-accent text-accent-foreground'
            }`}
        >
            {initials}
        </span>
    );
}

export function Discussion({
    lessonId,
    questions,
    canComment,
}: {
    lessonId: number;
    questions: Question[];
    canComment: boolean;
}) {
    const open = questions.filter((q) => !q.resolved).length;

    return (
        <section className="mt-10 border-t pt-8">
            <div className="mb-4 flex flex-wrap items-center gap-3">
                <h2 className="flex items-center gap-2 font-semibold">
                    <MessageSquare className="size-4" /> Questions
                </h2>
                <span className="text-muted-foreground text-sm">
                    {questions.length === 0
                        ? 'None yet'
                        : `${questions.length} asked${open ? ` · ${open} unanswered` : ''}`}
                </span>
            </div>

            {canComment ? (
                <Composer lessonId={lessonId} placeholder="Stuck on something in this lesson?" submit="Ask" />
            ) : (
                <p className="text-muted-foreground rounded-xl border border-dashed p-4 text-sm">
                    Enrol to ask questions on this lesson.
                </p>
            )}

            {questions.length > 0 && (
                <ul className="mt-6 flex flex-col gap-4">
                    {questions.map((question) => (
                        <Thread key={question.id} lessonId={lessonId} question={question} canReply={canComment} />
                    ))}
                </ul>
            )}
        </section>
    );
}

function Thread({
    lessonId,
    question,
    canReply,
}: {
    lessonId: number;
    question: Question;
    canReply: boolean;
}) {
    const [replying, setReplying] = useState(false);

    return (
        <li className={`rounded-xl border p-4 ${question.resolved ? 'bg-muted/40' : ''}`}>
            <div className="flex items-start gap-3">
                <Initials name={question.author} staff={question.from_staff} />

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-sm font-medium">{question.author}</span>
                        {question.from_staff && <Badge variant="secondary">instructor</Badge>}
                        <span className="text-muted-foreground text-xs">{when(question.created_at)}</span>

                        {question.resolved ? (
                            <Badge className="gap-1">
                                <Check className="size-3" /> answered
                            </Badge>
                        ) : (
                            <Badge variant="outline" className="gap-1">
                                <CircleHelp className="size-3" /> open
                            </Badge>
                        )}

                        <div className="ml-auto flex items-center">
                            {question.can_resolve && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        router.patch(`/comments/${question.id}/resolve`, {}, { preserveScroll: true })
                                    }
                                >
                                    {question.resolved ? 'Reopen' : 'Mark answered'}
                                </Button>
                            )}
                            {question.can_delete && (
                                <ConfirmButton
                                    size="icon"
                                    className="text-muted-foreground hover:text-destructive size-8"
                                    title="Delete this question?"
                                    description={
                                        question.replies.length > 0
                                            ? `Its ${question.replies.length} repl${question.replies.length === 1 ? 'y goes' : 'ies go'} too.`
                                            : undefined
                                    }
                                    confirmLabel="Delete"
                                    onConfirm={() =>
                                        router.delete(`/comments/${question.id}`, { preserveScroll: true })
                                    }
                                >
                                    <Trash2 className="size-3.5" />
                                    <span className="sr-only">Delete</span>
                                </ConfirmButton>
                            )}
                        </div>
                    </div>

                    <p className="mt-2 text-sm leading-relaxed whitespace-pre-wrap">{question.body}</p>
                </div>
            </div>

            {question.replies.length > 0 && (
                <ul className="mt-4 flex flex-col gap-4 border-l pl-4 sm:ml-10">
                    {question.replies.map((reply) => (
                        <li key={reply.id} className="flex items-start gap-3">
                            <Initials name={reply.author} staff={reply.from_staff} />
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-sm font-medium">{reply.author}</span>
                                    {reply.from_staff && <Badge variant="secondary">instructor</Badge>}
                                    <span className="text-muted-foreground text-xs">{when(reply.created_at)}</span>
                                    {reply.can_delete && (
                                        <ConfirmButton
                                            size="icon"
                                            className="text-muted-foreground hover:text-destructive ml-auto size-7"
                                            title="Delete this reply?"
                                            confirmLabel="Delete"
                                            onConfirm={() =>
                                                router.delete(`/comments/${reply.id}`, { preserveScroll: true })
                                            }
                                        >
                                            <Trash2 className="size-3.5" />
                                            <span className="sr-only">Delete</span>
                                        </ConfirmButton>
                                    )}
                                </div>
                                <p className="mt-1 text-sm leading-relaxed whitespace-pre-wrap">{reply.body}</p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {canReply && (
                <div className="mt-4 sm:ml-10">
                    {replying ? (
                        <Composer
                            lessonId={lessonId}
                            parentId={question.id}
                            placeholder="Answer this question"
                            submit="Reply"
                            onDone={() => setReplying(false)}
                            autoFocus
                        />
                    ) : (
                        <Button type="button" variant="ghost" size="sm" onClick={() => setReplying(true)}>
                            Reply
                        </Button>
                    )}
                </div>
            )}
        </li>
    );
}

function Composer({
    lessonId,
    parentId,
    placeholder,
    submit,
    onDone,
    autoFocus,
}: {
    lessonId: number;
    parentId?: number;
    placeholder: string;
    submit: string;
    onDone?: () => void;
    autoFocus?: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        body: string;
        parent_id?: number;
    }>({ body: '', ...(parentId ? { parent_id: parentId } : {}) });

    return (
        <form
            noValidate
            onSubmit={(e) => {
                e.preventDefault();
                post(`/lessons/${lessonId}/comments`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        reset('body');
                        onDone?.();
                    },
                });
            }}
        >
            <Textarea
                rows={3}
                autoFocus={autoFocus}
                value={data.body}
                onChange={(e) => setData('body', e.target.value)}
                placeholder={placeholder}
            />
            {errors.body && <p className="mt-1 text-sm text-red-600">{errors.body}</p>}

            <div className="mt-2 flex gap-2">
                <Button type="submit" size="sm" disabled={processing || !data.body.trim()}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    {submit}
                </Button>
                {onDone && (
                    <Button type="button" size="sm" variant="ghost" onClick={onDone}>
                        Cancel
                    </Button>
                )}
            </div>
        </form>
    );
}
