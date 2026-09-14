import { ConfirmButton } from '@/components/confirm-button';
import { DatePicker } from '@/components/date-picker';
import { Flash } from '@/components/flash';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { money } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Coupons', href: '/admin/coupons' }];

type Coupon = {
    id: number;
    code: string;
    percent_off: number | null;
    amount_off_cents: number | null;
    max_redemptions: number | null;
    redeemed_count: number;
    expires_at: string | null;
    redeemable: boolean;
};

export default function Coupons({ coupons }: { coupons: Coupon[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        kind: 'percent',
        percent_off: '',
        amount_off_cents: '',
        max_redemptions: '',
        expires_at: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Coupons" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Flash />
                <h1 className="text-xl font-semibold">Coupons</h1>

                <form noValidate
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/admin/coupons', { onSuccess: () => reset() });
                    }}
                    className="border-sidebar-border/70 dark:border-sidebar-border grid gap-4 rounded-xl border p-4 md:grid-cols-5"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="code">Code</Label>
                        <Input
                            id="code"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value.toUpperCase())}
                            placeholder="LAUNCH20"
                        />
                        {errors.code && <p className="text-xs text-red-600">{errors.code}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="kind">Type</Label>
                        <select
                            id="kind"
                            value={data.kind}
                            onChange={(e) => setData('kind', e.target.value)}
                            className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                        >
                            <option value="percent">Percent off</option>
                            <option value="amount">Amount off</option>
                        </select>
                    </div>

                    {data.kind === 'percent' ? (
                        <div className="grid gap-2">
                            <Label htmlFor="percent">Percent</Label>
                            <Input
                                id="percent"
                                type="number"
                                value={data.percent_off}
                                onChange={(e) => setData('percent_off', e.target.value)}
                            />
                            {errors.percent_off && <p className="text-xs text-red-600">{errors.percent_off}</p>}
                        </div>
                    ) : (
                        <div className="grid gap-2">
                            <Label htmlFor="amount">Cents off</Label>
                            <Input
                                id="amount"
                                type="number"
                                value={data.amount_off_cents}
                                onChange={(e) => setData('amount_off_cents', e.target.value)}
                            />
                            {errors.amount_off_cents && (
                                <p className="text-xs text-red-600">{errors.amount_off_cents}</p>
                            )}
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="max">Max uses</Label>
                        <Input
                            id="max"
                            type="number"
                            value={data.max_redemptions}
                            onChange={(e) => setData('max_redemptions', e.target.value)}
                            placeholder="unlimited"
                        />
                        {errors.max_redemptions && (
                            <p className="text-xs text-red-600">{errors.max_redemptions}</p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="expires">Expires</Label>
                        <DatePicker
                            id="expires"
                            value={data.expires_at}
                            onChange={(value) => setData('expires_at', value)}
                            placeholder="Never"
                        />
                        {errors.expires_at && <p className="text-xs text-red-600">{errors.expires_at}</p>}
                    </div>

                    <Button type="submit" disabled={processing || !data.code} className="w-fit md:col-span-5">
                        <Plus className="size-4" /> Create coupon
                    </Button>
                </form>

                {coupons.length > 0 && (
                    <ul className="border-sidebar-border/70 dark:border-sidebar-border divide-y rounded-xl border">
                        {coupons.map((coupon) => (
                            <li key={coupon.id} className="flex flex-wrap items-center gap-3 p-3 text-sm">
                                <code className="bg-muted rounded px-2 py-1 font-mono text-xs">{coupon.code}</code>
                                <span>
                                    {coupon.percent_off !== null
                                        ? `${coupon.percent_off}% off`
                                        : `${money(coupon.amount_off_cents ?? 0)} off`}
                                </span>
                                <span className="text-muted-foreground text-xs">
                                    used {coupon.redeemed_count}
                                    {coupon.max_redemptions ? ` / ${coupon.max_redemptions}` : ''}
                                </span>
                                {coupon.expires_at && (
                                    <span className="text-muted-foreground text-xs">
                                        expires {new Date(coupon.expires_at).toLocaleDateString()}
                                    </span>
                                )}
                                {!coupon.redeemable && <Badge variant="outline">spent</Badge>}
                                <ConfirmButton
                                    size="icon"
                                    className="text-muted-foreground hover:text-destructive ml-auto"
                                    title={`Delete coupon ${coupon.code}?`}
                                    description={
                                        coupon.redeemed_count > 0
                                            ? `It has been redeemed ${coupon.redeemed_count} time(s). Existing orders keep their discount; the code stops working.`
                                            : 'The code will stop working immediately.'
                                    }
                                    confirmLabel="Delete coupon"
                                    onConfirm={() => router.delete(`/admin/coupons/${coupon.code}`)}
                                >
                                    <Trash2 className="size-4" />
                                    <span className="sr-only">Delete</span>
                                </ConfirmButton>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}
