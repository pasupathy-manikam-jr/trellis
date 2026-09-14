import { CourseCard } from '@/components/course-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PublicLayout from '@/layouts/public-layout';
import { type CourseCardData, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

type Category = { slug: string; name: string; courses_count: number };

type Filters = {
    q: string;
    category: string | null;
    price: 'free' | 'paid' | null;
    sort: 'newest' | 'popular' | 'rating';
};

const sorts: { value: Filters['sort']; label: string }[] = [
    { value: 'newest', label: 'Newest' },
    { value: 'popular', label: 'Most enrolled' },
    { value: 'rating', label: 'Best rated' },
];

export default function CourseCatalog({
    courses,
    categories,
    filters,
}: {
    courses: Paginated<CourseCardData>;
    categories: Category[];
    filters: Filters;
}) {
    const [term, setTerm] = useState(filters.q);
    const first = useRef(true);

    // Typing filters as you go, but not on every keystroke.
    useEffect(() => {
        if (first.current) {
            first.current = false;
            return;
        }

        const id = setTimeout(() => apply({ q: term || null }), 350);

        return () => clearTimeout(id);
    }, [term]);

    const apply = (changes: Partial<Record<string, string | null>>) => {
        const next = { ...filters, q: term, ...changes };

        router.get('/courses', Object.fromEntries(
            Object.entries(next).filter(([, v]) => v !== null && v !== '' && v !== 'newest'),
        ), { preserveState: true, preserveScroll: true, replace: true });
    };

    const active = filters.q || filters.category || filters.price;

    return (
        <PublicLayout>
            <Head title="Courses" />

            <div className="mb-8">
                <span className="text-primary text-xs font-semibold tracking-widest uppercase">Catalogue</span>
                <h1 className="mt-2 text-3xl font-bold tracking-tight">Courses</h1>
                <div className="bg-primary mt-4 h-1 w-16 rounded-full" />
            </div>

            <div className="mb-6 flex flex-col gap-4">
                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative min-w-56 flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={term}
                            onChange={(e) => setTerm(e.target.value)}
                            placeholder="Search courses"
                            className="pl-9"
                            aria-label="Search courses"
                        />
                    </div>

                    <div className="flex rounded-md border p-0.5">
                        {([null, 'free', 'paid'] as const).map((value) => (
                            <button
                                key={value ?? 'all'}
                                type="button"
                                onClick={() => apply({ price: value })}
                                className={`rounded px-3 py-1.5 text-sm transition-colors ${
                                    filters.price === value
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-muted'
                                }`}
                            >
                                {value === null ? 'All' : value === 'free' ? 'Free' : 'Paid'}
                            </button>
                        ))}
                    </div>

                    <select
                        value={filters.sort}
                        onChange={(e) => apply({ sort: e.target.value })}
                        aria-label="Sort courses"
                        className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                    >
                        {sorts.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                </div>

                {categories.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => apply({ category: null })}
                            className={`rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                                filters.category === null
                                    ? 'bg-primary text-primary-foreground border-primary'
                                    : 'hover:border-foreground/30'
                            }`}
                        >
                            All
                        </button>
                        {categories.map((category) => (
                            <button
                                key={category.slug}
                                type="button"
                                onClick={() =>
                                    apply({ category: filters.category === category.slug ? null : category.slug })
                                }
                                className={`rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                                    filters.category === category.slug
                                        ? 'bg-primary text-primary-foreground border-primary'
                                        : 'hover:border-foreground/30'
                                }`}
                            >
                                {category.name}
                                <span className="ml-1.5 opacity-60">{category.courses_count}</span>
                            </button>
                        ))}
                    </div>
                )}
            </div>

            <div className="text-muted-foreground mb-6 flex flex-wrap items-center gap-3 text-sm">
                <span>
                    {courses.total} course{courses.total === 1 ? '' : 's'}
                </span>
                {active && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setTerm('');
                            router.get('/courses', {}, { preserveScroll: true, replace: true });
                        }}
                    >
                        <X className="size-3.5" /> Clear filters
                    </Button>
                )}
            </div>

            {courses.data.length === 0 ? (
                <p className="text-muted-foreground rounded-xl border border-dashed p-16 text-center text-sm">
                    {active ? 'Nothing matches those filters.' : 'No published courses yet.'}
                </p>
            ) : (
                <>
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {courses.data.map((course) => (
                            <CourseCard key={course.id} course={course} />
                        ))}
                    </div>

                    {courses.last_page > 1 && (
                        <nav className="mt-10 flex flex-wrap justify-center gap-1" aria-label="Pagination">
                            {courses.links.map((link, i) =>
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        preserveScroll
                                        className={`rounded-md border px-3 py-1.5 text-sm transition-colors ${
                                            link.active
                                                ? 'bg-primary text-primary-foreground border-primary'
                                                : 'hover:border-foreground/30'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        className="text-muted-foreground rounded-md border px-3 py-1.5 text-sm opacity-50"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ),
                            )}
                        </nav>
                    )}
                </>
            )}
        </PublicLayout>
    );
}
