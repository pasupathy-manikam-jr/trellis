import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Courses', href: '/admin/courses' },
    { title: 'New', href: '/admin/courses/create' },
];

export default function CourseCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        summary: '',
        description: '',
        price_cents: 0,
        status: 'draft',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New course" />

            <form noValidate
                onSubmit={(e) => {
                    e.preventDefault();
                    post('/admin/courses');
                }}
                className="flex max-w-xl flex-col gap-6 p-4"
            >
                <div>
                    <h1 className="text-xl font-semibold">New course</h1>
                    <p className="text-muted-foreground text-sm">
                        Just a title to start — everything else is editable next.
                    </p>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="title">Title</Label>
                    <Input
                        id="title"
                        autoFocus
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder="Designing for the web"
                    />
                    {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="summary">Summary</Label>
                    <Input
                        id="summary"
                        value={data.summary}
                        onChange={(e) => setData('summary', e.target.value)}
                        placeholder="One line shown on the catalog card"
                    />
                    {errors.summary && <p className="text-sm text-red-600">{errors.summary}</p>}
                </div>

                <Button type="submit" disabled={processing} className="w-fit">
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Create course
                </Button>
            </form>
        </AppLayout>
    );
}
