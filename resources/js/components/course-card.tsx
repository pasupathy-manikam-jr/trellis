import { Stars } from '@/components/reviews';
import { Badge } from '@/components/ui/badge';
import { money } from '@/lib/format';
import { type CourseCardData } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';

/** Initials stand in for an avatar — no upload, no broken image. */
function Initials({ name }: { name: string }) {
    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');

    return (
        <span className="bg-accent text-accent-foreground flex size-6 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold">
            {initials}
        </span>
    );
}

/**
 * Courses without a thumbnail get a deterministic lattice panel rather than a
 * grey box, so a fresh catalogue still looks deliberate.
 */
export function CourseThumb({
    course,
    className = '',
}: {
    course: Pick<CourseCardData, 'id' | 'title' | 'thumbnail_url'>;
    className?: string;
}) {
    if (course.thumbnail_url) {
        return (
            <img
                src={course.thumbnail_url}
                alt=""
                loading="lazy"
                className={`size-full object-cover ${className}`}
            />
        );
    }

    const hue = 150 + ((course.id * 37) % 90);

    return (
        <div
            className={`size-full ${className}`}
            style={{
                backgroundImage:
                    `linear-gradient(135deg, hsl(${hue} 45% 22%), hsl(${hue + 25} 55% 34%))`,
            }}
            aria-hidden
        >
            <svg className="size-full opacity-20" viewBox="0 0 120 80" preserveAspectRatio="none">
                <defs>
                    <pattern id={`lattice-${course.id}`} width="20" height="20" patternUnits="userSpaceOnUse">
                        <path
                            d="M10 0 L20 10 L10 20 L0 10 Z"
                            fill="none"
                            stroke="white"
                            strokeWidth="1"
                        />
                    </pattern>
                </defs>
                <rect width="120" height="80" fill={`url(#lattice-${course.id})`} />
            </svg>
        </div>
    );
}

export function CourseCard({ course }: { course: CourseCardData }) {
    return (
        <Link
            href={`/courses/${course.slug}`}
            className="group bg-card hover:border-primary/30 flex flex-col overflow-hidden rounded-xl border transition-all hover:shadow-lg"
        >
            <div className="relative aspect-[16/10] overflow-hidden">
                <CourseThumb
                    course={course}
                    className="transition-transform duration-300 group-hover:scale-105"
                />
                <Badge
                    variant={course.price_cents === 0 ? 'secondary' : 'default'}
                    className="absolute top-3 left-3 shadow-sm"
                >
                    {money(course.price_cents, course.currency)}
                </Badge>
            </div>

            <div className="flex flex-1 flex-col gap-2 p-4">
                <h3 className="group-hover:text-primary font-semibold transition-colors">{course.title}</h3>

                {course.summary && (
                    <p className="text-muted-foreground line-clamp-2 text-sm">{course.summary}</p>
                )}

                <div className="mt-auto flex flex-wrap items-center gap-x-3 gap-y-2 pt-3">
                    {course.instructor && (
                        <span className="flex items-center gap-1.5 text-xs font-medium">
                            <Initials name={course.instructor} />
                            {course.instructor}
                        </span>
                    )}

                    <span className="text-muted-foreground ml-auto flex items-center gap-1 text-xs">
                        <BookOpen className="size-3.5" />
                        {course.lessons_count}
                    </span>
                </div>

                {course.rating !== null && (
                    <div className="flex items-center gap-1.5 border-t pt-2.5">
                        <Stars rating={Math.round(course.rating)} className="size-3.5" />
                        <span className="text-xs font-medium">{course.rating.toFixed(1)}</span>
                        <span className="text-muted-foreground text-xs">({course.reviews_count})</span>
                    </div>
                )}
            </div>
        </Link>
    );
}
