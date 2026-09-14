import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { CalendarClock, CheckCircle2, Download, LoaderCircle, Paperclip } from 'lucide-react';

export type AssignmentPayload = {
    id: number;
    instructions: string | null;
    points: number;
    allow_file: boolean;
    due_at: string | null;
    submission: {
        body: string | null;
        file_name: string | null;
        file_url: string | null;
        submitted_at: string;
        late: boolean;
    } | null;
    grade: {
        points: number;
        percent: number;
        feedback: string | null;
        graded_at: string;
    } | null;
};

function when(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

export function AssignmentPanel({
    assignment,
    enrolled,
}: {
    assignment: AssignmentPayload;
    enrolled: boolean;
}) {
    const { data, setData, post, processing, errors } = useForm<{ body: string; file: File | null }>({
        body: assignment.submission?.body ?? '',
        file: null,
    });

    const marked = assignment.grade !== null;

    return (
        <section className="mt-6 flex flex-col gap-5">
            <div className="rounded-xl border p-5">
                <div className="mb-3 flex flex-wrap items-center gap-2">
                    <h2 className="font-semibold">The brief</h2>
                    <Badge variant="outline">{assignment.points} points</Badge>
                    {assignment.due_at && (
                        <Badge variant="secondary" className="gap-1">
                            <CalendarClock className="size-3" /> due {when(assignment.due_at)}
                        </Badge>
                    )}
                </div>

                {assignment.instructions ? (
                    <p className="text-sm leading-relaxed whitespace-pre-wrap">{assignment.instructions}</p>
                ) : (
                    <p className="text-muted-foreground text-sm">No brief was written for this one.</p>
                )}
            </div>

            {marked && (
                <div className="rounded-xl border border-emerald-600/20 bg-emerald-500/10 p-5">
                    <div className="flex flex-wrap items-baseline gap-2">
                        <CheckCircle2 className="size-5 text-emerald-700 dark:text-emerald-400" />
                        <span className="text-lg font-semibold text-emerald-800 dark:text-emerald-300">
                            {assignment.grade!.points} / {assignment.points}
                        </span>
                        <span className="text-muted-foreground text-sm">
                            ({assignment.grade!.percent}%) · marked {when(assignment.grade!.graded_at)}
                        </span>
                    </div>

                    {assignment.grade!.feedback && (
                        <p className="mt-3 text-sm leading-relaxed whitespace-pre-wrap">
                            {assignment.grade!.feedback}
                        </p>
                    )}
                </div>
            )}

            {!enrolled ? (
                <p className="text-muted-foreground rounded-xl border border-dashed p-4 text-sm">
                    Enrol to hand work in for this assignment.
                </p>
            ) : marked ? (
                <div className="rounded-xl border p-5">
                    <h3 className="mb-2 text-sm font-semibold">What you handed in</h3>
                    {assignment.submission?.body && (
                        <p className="text-muted-foreground text-sm leading-relaxed whitespace-pre-wrap">
                            {assignment.submission.body}
                        </p>
                    )}
                    {assignment.submission?.file_url && (
                        <Button asChild variant="outline" size="sm" className="mt-3">
                            <a href={assignment.submission.file_url}>
                                <Download className="size-4" /> {assignment.submission.file_name}
                            </a>
                        </Button>
                    )}
                    <p className="text-muted-foreground mt-3 text-xs">
                        Marked work cannot be changed.
                    </p>
                </div>
            ) : (
                <form
                    noValidate
                    encType="multipart/form-data"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(`/assignments/${assignment.id}/submissions`, {
                            forceFormData: true,
                            preserveScroll: true,
                        });
                    }}
                    className="flex flex-col gap-4 rounded-xl border p-5"
                >
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-sm font-semibold">
                            {assignment.submission ? 'Your work' : 'Hand in your work'}
                        </h3>
                        {assignment.submission && (
                            <>
                                <Badge variant="secondary">
                                    handed in {when(assignment.submission.submitted_at)}
                                </Badge>
                                {assignment.submission.late && <Badge variant="outline">late</Badge>}
                                <span className="text-muted-foreground text-xs">
                                    You can keep changing this until it is marked.
                                </span>
                            </>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="submission-body">Your answer</Label>
                        <Textarea
                            id="submission-body"
                            rows={8}
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            placeholder="Write your answer, or attach a file below."
                        />
                        {errors.body && <p className="text-sm text-red-600">{errors.body}</p>}
                    </div>

                    {assignment.allow_file && (
                        <div className="grid gap-2">
                            <Label htmlFor="submission-file">
                                <Paperclip className="mr-1 inline size-3.5" />
                                Attachment
                            </Label>
                            <Input
                                id="submission-file"
                                type="file"
                                onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                            />
                            {assignment.submission?.file_url && !data.file && (
                                <p className="text-muted-foreground text-xs">
                                    Currently attached: {assignment.submission.file_name}. Choosing a new
                                    file replaces it.
                                </p>
                            )}
                            {errors.file && <p className="text-sm text-red-600">{errors.file}</p>}
                        </div>
                    )}

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing && <LoaderCircle className="size-4 animate-spin" />}
                        {assignment.submission ? 'Update my work' : 'Hand in'}
                    </Button>
                </form>
            )}
        </section>
    );
}
