import { ConfirmButton } from '@/components/confirm-button';
import { Flash } from '@/components/flash';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { money } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Undo2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Orders', href: '/admin/orders' }];

type Order = {
    id: number;
    user: { name: string; email: string };
    course: { slug: string; title: string };
    coupon: string | null;
    subtotal_cents: number;
    discount_cents: number;
    total_cents: number;
    currency: string;
    status: string;
    paid_at: string | null;
};

export default function AdminOrders({ orders }: { orders: Order[] }) {
    const revenue = orders
        .filter((o) => o.status === 'paid')
        .reduce((sum, o) => sum + o.total_cents, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Orders" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Flash />

                <div>
                    <h1 className="text-xl font-semibold">Orders</h1>
                    <p className="text-muted-foreground text-sm">
                        {orders.length} order{orders.length === 1 ? '' : 's'} · {money(revenue)} collected
                        (nothing was actually charged — no gateway yet)
                    </p>
                </div>

                {orders.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        No orders yet.
                    </p>
                ) : (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="text-muted-foreground border-b text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Buyer</th>
                                    <th className="px-4 py-3 font-medium">Course</th>
                                    <th className="px-4 py-3 font-medium">Coupon</th>
                                    <th className="px-4 py-3 font-medium">Total</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {orders.map((order) => (
                                    <tr key={order.id} className="border-b last:border-0">
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{order.user.name}</div>
                                            <div className="text-muted-foreground text-xs">{order.user.email}</div>
                                        </td>
                                        <td className="px-4 py-3">{order.course.title}</td>
                                        <td className="text-muted-foreground px-4 py-3">{order.coupon ?? '—'}</td>
                                        <td className="px-4 py-3">
                                            {money(order.total_cents, order.currency)}
                                            {order.discount_cents > 0 && (
                                                <span className="text-muted-foreground ml-1 text-xs line-through">
                                                    {money(order.subtotal_cents, order.currency)}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={order.status === 'refunded' ? 'outline' : 'secondary'}>
                                                {order.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {order.status === 'paid' && (
                                                <ConfirmButton
                                                    size="sm"
                                                    title={`Refund ${order.user.name}?`}
                                                    description={`They lose access to “${order.course.title}” immediately, and any coupon they used is freed for someone else.`}
                                                    confirmLabel="Refund and revoke"
                                                    onConfirm={() =>
                                                        router.post(`/admin/orders/${order.id}/refund`)
                                                    }
                                                >
                                                    <Undo2 className="size-4" /> Refund
                                                </ConfirmButton>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
