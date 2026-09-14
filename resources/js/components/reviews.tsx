import { ConfirmButton } from '@/components/confirm-button';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { type Course } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { LoaderCircle, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';

export type ReviewItem = {
    id: number;
    rating: number;
    body: string | null;
    author: string;
    created_at: string;
};

export type ReviewSummary = {
    average: number | null;
    count: number;
    mine: { id: number; rating: number; body: string | null } | null;
    items: ReviewItem[];
};

export function Stars({ rating, className = 'size-4' }: { rating: number; className?: string }) {
    return (
        <span className="flex items-center gap-0.5" aria-label={`${rating} out of 5`}>
            {[1, 2, 3, 4, 5].map((n) => (
                <Star
                    key={n}
                    className={`${className} ${n <= rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/40'}`}
                />
            ))}
        </span>
    );
}

export function Reviews({
    course,
    reviews,
    canReview,
}: {
    course: Course;
    reviews: ReviewSummary;
    canReview: boolean;
}) {
    return (
        <section className="mt-10">
            <div className="mb-4 flex flex-wrap items-center gap-3">
                <h2 className="font-semibold">Reviews</h2>
                {reviews.average === null ? (
                    <span className="text-muted-foreground text-sm">No ratings yet</span>
                ) : (
                    <>
                        <Stars rating={Math.round(reviews.average)} />
                        <span className="text-sm font-medium">{reviews.average.toFixed(1)}</span>
                        <span className="text-muted-foreground text-sm">
                            from {reviews.count} review{reviews.count === 1 ? '' : 's'}
                        </span>
                    </>
                )}
            </div>

            {canReview && <ReviewForm course={course} mine={reviews.mine} />}

            {reviews.items.length > 0 && (
                <ul className="mt-6 flex flex-col gap-4">
                    {reviews.items.map((review) => (
                        <li key={review.id} className="rounded-xl border p-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Stars rating={review.rating} className="size-3.5" />
                                <span className="text-sm font-medium">{review.author}</span>
                                <span className="text-muted-foreground text-xs">
                                    {new Date(review.created_at).toLocaleDateString()}
                                </span>
                                {reviews.mine?.id === review.id && (
                                    <ConfirmButton
                                        size="icon"
                                        className="text-muted-foreground hover:text-destructive ml-auto size-7"
                                        title="Remove your review?"
                                        confirmLabel="Remove"
                                        onConfirm={() =>
                                            router.delete(`/reviews/${review.id}`, { preserveScroll: true })
                                        }
                                    >
                                        <Trash2 className="size-3.5" />
                                        <span className="sr-only">Delete review</span>
                                    </ConfirmButton>
                                )}
                            </div>
                            {review.body && <p className="mt-2 text-sm leading-relaxed">{review.body}</p>}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

function ReviewForm({ course, mine }: { course: Course; mine: ReviewSummary['mine'] }) {
    const { data, setData, post, processing, errors } = useForm({
        rating: mine?.rating ?? 0,
        body: mine?.body ?? '',
    });
    const [hover, setHover] = useState(0);

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                post(`/courses/${course.slug}/reviews`, { preserveScroll: true });
            }}
            className="rounded-xl border p-4"
        >
            <p className="mb-2 text-sm font-medium">{mine ? 'Your review' : 'Leave a review'}</p>

            <div className="flex items-center gap-1" onMouseLeave={() => setHover(0)}>
                {[1, 2, 3, 4, 5].map((n) => (
                    <button
                        key={n}
                        type="button"
                        aria-label={`${n} star${n === 1 ? '' : 's'}`}
                        onMouseEnter={() => setHover(n)}
                        onClick={() => setData('rating', n)}
                        className="p-0.5"
                    >
                        <Star
                            className={`size-6 transition-colors ${
                                n <= (hover || data.rating)
                                    ? 'fill-amber-400 text-amber-400'
                                    : 'text-muted-foreground/40'
                            }`}
                        />
                    </button>
                ))}
            </div>
            {errors.rating && <p className="mt-1 text-sm text-red-600">{errors.rating}</p>}

            <Textarea
                rows={3}
                className="mt-3"
                value={data.body}
                onChange={(e) => setData('body', e.target.value)}
                placeholder="What was it like? (optional)"
            />
            {errors.body && <p className="mt-1 text-sm text-red-600">{errors.body}</p>}

            <Button type="submit" size="sm" className="mt-3" disabled={processing || data.rating === 0}>
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                {mine ? 'Update review' : 'Post review'}
            </Button>
        </form>
    );
}
