import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function PublicLayout({ children }: { children: React.ReactNode }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <div className="flex min-h-screen flex-col">
            <header className="border-b">
                <nav className="mx-auto flex w-full max-w-5xl items-center gap-4 px-4 py-3">
                    <Link href="/courses" className="flex items-center gap-2 font-semibold">
                        <span className="bg-primary text-primary-foreground flex size-7 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-4" />
                        </span>
                        Trellis
                    </Link>

                    <div className="ml-auto flex items-center gap-2">
                        {auth.user ? (
                            <>
                                {(auth.user.role === 'admin' || auth.user.role === 'instructor') && (
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href="/admin/courses">Admin</Link>
                                    </Button>
                                )}
                                <Button asChild size="sm">
                                    <Link href="/dashboard">Dashboard</Link>
                                </Button>
                            </>
                        ) : (
                            <>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/login">Log in</Link>
                                </Button>
                                <Button asChild size="sm">
                                    <Link href="/register">Register</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </nav>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8">{children}</main>
        </div>
    );
}
