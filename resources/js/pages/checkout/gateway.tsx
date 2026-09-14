import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/format';
import { Head, Link, useForm } from '@inertiajs/react';
import { CreditCard, LoaderCircle, Lock, TriangleAlert } from 'lucide-react';

type Props = {
    order: {
        reference: string;
        total_cents: number;
        subtotal_cents: number;
        discount_cents: number;
        currency: string;
        course: { slug: string; title: string };
        coupon: string | null;
    };
};

export default function Gateway({ order }: Props) {
    const { post, processing, data, transform } = useForm({ outcome: 'paid' });

    const choose = (outcome: 'paid' | 'declined' | 'abandoned') => {
        transform(() => ({ outcome }));
        post(`/checkout/${order.reference}`);
    };

    return (
        <>
            <Head title="Checkout" />

            <div className="bg-muted/40 flex min-h-dvh flex-col items-center justify-center px-6 py-12">
                <div className="w-full max-w-md">
                    <div className="mb-4 flex items-center justify-center gap-2 text-sm font-semibold">
                        <span className="bg-primary text-primary-foreground flex size-7 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-4" />
                        </span>
                        Trellis
                    </div>

                    <div className="rounded-xl border bg-amber-500/10 p-3 text-center text-xs text-amber-800 dark:text-amber-300">
                        <TriangleAlert className="mr-1 inline size-3.5" />
                        Stand-in checkout. No card is taken and no money moves — this page exists
                        to exercise the real flow.
                    </div>

                    <div className="bg-card mt-4 rounded-xl border p-6 shadow-sm">
                        <h1 className="text-lg font-semibold">{order.course.title}</h1>

                        <dl className="mt-4 flex flex-col gap-2 border-y py-4 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">Price</dt>
                                <dd>{money(order.subtotal_cents, order.currency)}</dd>
                            </div>
                            {order.discount_cents > 0 && (
                                <div className="flex justify-between text-emerald-700 dark:text-emerald-400">
                                    <dt>Discount{order.coupon ? ` (${order.coupon})` : ''}</dt>
                                    <dd>−{money(order.discount_cents, order.currency)}</dd>
                                </div>
                            )}
                            <div className="flex justify-between text-base font-semibold">
                                <dt>Total</dt>
                                <dd>{money(order.total_cents, order.currency)}</dd>
                            </div>
                        </dl>

                        <div className="mt-4 flex flex-col gap-2">
                            <Button
                                className="w-full"
                                disabled={processing}
                                onClick={() => choose('paid')}
                            >
                                {processing && data.outcome === 'paid' ? (
                                    <LoaderCircle className="size-4 animate-spin" />
                                ) : (
                                    <CreditCard className="size-4" />
                                )}
                                Pay {money(order.total_cents, order.currency)}
                            </Button>

                            <Button
                                variant="outline"
                                className="w-full"
                                disabled={processing}
                                onClick={() => choose('declined')}
                            >
                                Simulate a declined card
                            </Button>

                            <Button
                                variant="ghost"
                                className="w-full"
                                disabled={processing}
                                onClick={() => choose('abandoned')}
                            >
                                Abandon checkout
                            </Button>
                        </div>

                        <p className="text-muted-foreground mt-4 flex items-center justify-center gap-1.5 text-xs">
                            <Lock className="size-3" />
                            Reference {order.reference}
                        </p>
                    </div>

                    <p className="text-muted-foreground mt-4 text-center text-xs">
                        <Link href={`/courses/${order.course.slug}`} className="hover:underline">
                            Back to the course
                        </Link>
                    </p>
                </div>
            </div>
        </>
    );
}
