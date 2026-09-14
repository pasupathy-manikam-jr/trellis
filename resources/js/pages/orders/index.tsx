import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { money } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Orders', href: '/orders' }];

type Order = {
    id: number;
    course: { slug: string; title: string };
    coupon: string | null;
    subtotal_cents: number;
    discount_cents: number;
    total_cents: number;
    currency: string;
    status: string;
    paid_at: string | null;
};

export default function Orders({ orders }: { orders: Order[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Orders" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Orders</h1>

                {orders.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground text-sm">No orders yet.</p>
                        <Button asChild variant="link">
                            <Link href="/courses">Browse courses</Link>
                        </Button>
                    </div>
                ) : (
                    <ul className="border-sidebar-border/70 dark:border-sidebar-border divide-y rounded-xl border">
                        {orders.map((order) => (
                            <li key={order.id} className="flex flex-wrap items-center gap-3 p-4">
                                <div className="min-w-0 flex-1">
                                    <Link
                                        href={`/courses/${order.course.slug}`}
                                        className="font-medium hover:underline"
                                    >
                                        {order.course.title}
                                    </Link>
                                    <div className="text-muted-foreground text-xs">
                                        {order.paid_at && new Date(order.paid_at).toLocaleDateString()}
                                        {order.coupon && ` · coupon ${order.coupon}`}
                                    </div>
                                </div>

                                {order.discount_cents > 0 && (
                                    <span className="text-muted-foreground text-xs line-through">
                                        {money(order.subtotal_cents, order.currency)}
                                    </span>
                                )}
                                <span className="font-medium">{money(order.total_cents, order.currency)}</span>
                                <Badge variant={order.status === 'refunded' ? 'outline' : 'secondary'}>
                                    {order.status}
                                </Badge>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}
