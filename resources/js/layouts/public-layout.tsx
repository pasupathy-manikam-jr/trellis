import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

/**
 * Pages that read the same whether or not you are signed in — the catalogue, a
 * course, the player, the handbook.
 *
 * Signed in, they sit inside the app shell so the sidebar never disappears
 * mid-session. Signed out, there is no sidebar worth showing (no dashboard, no
 * orders), so they get a plain header instead.
 */
export default function PublicLayout({
    children,
    breadcrumbs,
}: {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    const { auth } = usePage<SharedData>().props;

    if (auth.user) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="mx-auto w-full max-w-5xl px-4 py-8">{children}</div>
            </AppLayout>
        );
    }

    return (
        <div className="flex min-h-screen flex-col">
            <header className="border-b">
                <nav className="mx-auto flex w-full max-w-5xl items-center gap-4 px-4 py-3">
                    <Link href="/" className="flex items-center gap-2 font-semibold">
                        <span className="bg-primary text-primary-foreground flex size-7 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-4" />
                        </span>
                        Trellis
                    </Link>

                    <Button asChild variant="ghost" size="sm" className="text-muted-foreground">
                        <Link href="/courses">Courses</Link>
                    </Button>
                    <Button asChild variant="ghost" size="sm" className="text-muted-foreground">
                        <Link href="/handbook">Handbook</Link>
                    </Button>

                    <div className="ml-auto flex items-center gap-2">
                        <Button asChild variant="ghost" size="sm">
                            <Link href="/login">Log in</Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href="/register">Register</Link>
                        </Button>
                    </div>
                </nav>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8">{children}</main>
        </div>
    );
}
