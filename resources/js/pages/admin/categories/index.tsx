import { ConfirmButton } from '@/components/confirm-button';
import { Flash } from '@/components/flash';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Plus, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Categories', href: '/admin/categories' }];

type Category = { id: number; slug: string; name: string; courses_count: number };

export default function Categories({ categories }: { categories: Category[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categories" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Flash />

                <div>
                    <h1 className="text-xl font-semibold">Categories</h1>
                    <p className="text-muted-foreground text-sm">
                        Shown as filters on the catalogue, in this order.
                    </p>
                </div>

                <form
                    noValidate
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/admin/categories', { onSuccess: () => reset() });
                    }}
                    className="flex max-w-md items-start gap-2"
                >
                    <div className="flex-1">
                        <Input
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Databases"
                        />
                        {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                    </div>
                    <Button type="submit" variant="secondary" disabled={processing || !data.name}>
                        <Plus className="size-4" /> Add
                    </Button>
                </form>

                {categories.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        No categories yet. The catalogue simply shows everything until there are some.
                    </p>
                ) : (
                    <ul className="border-sidebar-border/70 dark:border-sidebar-border max-w-2xl divide-y rounded-xl border">
                        {categories.map((category, i) => (
                            <li key={category.id} className="flex items-center gap-3 p-3 text-sm">
                                <span className="font-medium">{category.name}</span>
                                <code className="text-muted-foreground text-xs">/{category.slug}</code>
                                <Badge variant="outline">
                                    {category.courses_count} course{category.courses_count === 1 ? '' : 's'}
                                </Badge>

                                <div className="ml-auto flex">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        disabled={i === 0}
                                        onClick={() =>
                                            router.patch(`/admin/categories/${category.slug}/move`,
                                                { direction: 'up' }, { preserveScroll: true })
                                        }
                                    >
                                        <ChevronUp className="size-4" />
                                        <span className="sr-only">Move up</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        disabled={i === categories.length - 1}
                                        onClick={() =>
                                            router.patch(`/admin/categories/${category.slug}/move`,
                                                { direction: 'down' }, { preserveScroll: true })
                                        }
                                    >
                                        <ChevronDown className="size-4" />
                                        <span className="sr-only">Move down</span>
                                    </Button>
                                    <ConfirmButton
                                        size="icon"
                                        className="text-muted-foreground hover:text-destructive"
                                        title={`Remove ${category.name}?`}
                                        description={
                                            category.courses_count > 0
                                                ? `${category.courses_count} course(s) lose this label. The courses themselves are untouched.`
                                                : 'Nothing is using it.'
                                        }
                                        confirmLabel="Remove"
                                        onConfirm={() =>
                                            router.delete(`/admin/categories/${category.slug}`, {
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
            </div>
        </AppLayout>
    );
}
