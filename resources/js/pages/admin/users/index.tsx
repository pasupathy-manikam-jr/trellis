import { Flash } from '@/components/flash';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData, type UserRole } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'People', href: '/admin/users' }];

type Row = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    courses_count: number;
};

const blurb: Record<UserRole, string> = {
    admin: 'Everything, including money and people',
    instructor: 'Their own courses and learners',
    student: 'Learning only',
};

export default function Users({ users, roles }: { users: Row[]; roles: UserRole[] }) {
    const { auth, errors } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="People" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Flash />

                <div>
                    <h1 className="text-xl font-semibold">People</h1>
                    <p className="text-muted-foreground text-sm">
                        Instructors get the course workspace, scoped to courses they own.
                    </p>
                </div>

                {errors.role && (
                    <p className="rounded-lg border border-red-600/20 bg-red-500/10 px-3 py-2 text-sm text-red-700 dark:text-red-400">
                        {errors.role}
                    </p>
                )}

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="text-muted-foreground border-b text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Name</th>
                                <th className="px-4 py-3 font-medium">Courses</th>
                                <th className="px-4 py-3 font-medium">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.map((user) => (
                                <tr key={user.id} className="border-b last:border-0">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2 font-medium">
                                            {user.name}
                                            {user.id === auth.user.id && (
                                                <Badge variant="secondary">you</Badge>
                                            )}
                                        </div>
                                        <div className="text-muted-foreground text-xs">{user.email}</div>
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 tabular-nums">
                                        {user.courses_count || '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <select
                                            value={user.role}
                                            aria-label={`Role for ${user.name}`}
                                            onChange={(e) =>
                                                router.patch(
                                                    `/admin/users/${user.id}/role`,
                                                    { role: e.target.value },
                                                    { preserveScroll: true },
                                                )
                                            }
                                            className="border-input bg-background h-9 rounded-md border px-2 text-sm"
                                        >
                                            {roles.map((role) => (
                                                <option key={role} value={role}>
                                                    {role}
                                                </option>
                                            ))}
                                        </select>
                                        <p className="text-muted-foreground mt-1 text-xs">{blurb[user.role]}</p>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
