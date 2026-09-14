import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { type PlayerQuiz } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { BadgeCheck, LoaderCircle } from 'lucide-react';

export function QuizTaker({ lessonId, quiz }: { lessonId: number; quiz: PlayerQuiz }) {
    const { data, setData, post, processing } = useForm<{ answers: Record<number, number[]> }>({
        answers: {},
    });

    // "No attempts left" is raised by the server against a key the form does not
    // own, so it arrives on the shared error bag rather than this form's.
    const { errors } = usePage().props;

    const choose = (questionId: number, optionId: number, multi: boolean) => {
        const current = data.answers[questionId] ?? [];

        setData('answers', {
            ...data.answers,
            [questionId]: multi
                ? current.includes(optionId)
                    ? current.filter((id) => id !== optionId)
                    : [...current, optionId]
                : [optionId],
        });
    };

    if (quiz.passed) {
        return (
            <div className="rounded-xl border border-emerald-600/20 bg-emerald-500/10 p-4">
                <div className="flex items-center gap-2 font-medium text-emerald-700 dark:text-emerald-400">
                    <BadgeCheck className="size-5" />
                    Passed with {quiz.best_score}%
                </div>
                <p className="text-muted-foreground mt-1 text-sm">
                    This lesson is complete. Quizzes cannot be retaken once passed.
                </p>
            </div>
        );
    }

    if (!quiz.can_attempt) {
        return (
            <div className="rounded-xl border border-dashed p-4">
                <p className="font-medium">
                    {quiz.questions.length === 0 ? 'This quiz has no questions yet.' : 'No attempts left.'}
                </p>
                {quiz.best_score !== null && (
                    <p className="text-muted-foreground mt-1 text-sm">
                        Best score {quiz.best_score}% · {quiz.pass_percent}% needed to pass.
                    </p>
                )}
            </div>
        );
    }

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                post(`/lessons/${lessonId}/quiz`, { preserveScroll: true });
            }}
            className="flex flex-col gap-5"
        >
            <div className="text-muted-foreground flex flex-wrap items-center gap-2 text-sm">
                <Badge variant="outline">{quiz.pass_percent}% to pass</Badge>
                {quiz.attempts_left !== null && (
                    <Badge variant="outline">
                        {quiz.attempts_left} attempt{quiz.attempts_left === 1 ? '' : 's'} left
                    </Badge>
                )}
                {quiz.best_score !== null && <span>Best so far {quiz.best_score}%</span>}
            </div>

            {errors.quiz && <p className="text-sm text-red-600">{errors.quiz}</p>}

            {quiz.questions.map((question, i) => {
                const multi = question.type === 'multi';
                const chosen = data.answers[question.id] ?? [];

                return (
                    <fieldset key={question.id} className="rounded-xl border p-4">
                        <legend className="px-1 text-sm font-medium">
                            {i + 1}. {question.prompt}
                            <span className="text-muted-foreground ml-2 font-normal">
                                {question.points} pt{question.points === 1 ? '' : 's'}
                                {multi && ' · choose all that apply'}
                            </span>
                        </legend>

                        <div className="mt-3 flex flex-col gap-2">
                            {question.options.map((option) => (
                                <label key={option.id} className="flex items-center gap-2 text-sm">
                                    {multi ? (
                                        <Checkbox
                                            checked={chosen.includes(option.id)}
                                            onCheckedChange={() => choose(question.id, option.id, true)}
                                        />
                                    ) : (
                                        <input
                                            type="radio"
                                            name={`question-${question.id}`}
                                            checked={chosen.includes(option.id)}
                                            onChange={() => choose(question.id, option.id, false)}
                                            className="size-4"
                                        />
                                    )}
                                    {option.text}
                                </label>
                            ))}
                        </div>
                    </fieldset>
                );
            })}

            <Button type="submit" disabled={processing} className="w-fit">
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                Submit answers
            </Button>
        </form>
    );
}
