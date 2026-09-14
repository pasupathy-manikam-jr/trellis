import { NavMain, type NavGroup } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    ChartNoAxesColumn,
    GraduationCap,
    LayoutGrid,
    Library,
    Receipt,
    Tag,
    Ticket,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const role = auth.user?.role;
    const staff = role === 'admin' || role === 'instructor';

    // Teaching comes first for staff: it is what they signed in to do. Their own
    // enrolments still appear, just not at the top pretending to be the job.
    const groups: NavGroup[] = [
        {
            label: 'Teaching',
            items: staff
                ? [
                      { title: 'Courses', url: '/admin/courses', icon: GraduationCap },
                      { title: 'Insights', url: '/admin/insights', icon: ChartNoAxesColumn },
                      { title: 'Handbook', url: '/handbook/admin', icon: BookOpen },
                  ]
                : [],
        },
        {
            label: 'Administration',
            items:
                role === 'admin'
                    ? [
                          { title: 'Orders', url: '/admin/orders', icon: Receipt },
                          { title: 'Coupons', url: '/admin/coupons', icon: Ticket },
                          { title: 'Categories', url: '/admin/categories', icon: Tag },
                          { title: 'People', url: '/admin/users', icon: Users },
                      ]
                    : [],
        },
        {
            label: staff ? 'Your own learning' : 'Learning',
            items: [
                { title: 'My courses', url: '/dashboard', icon: LayoutGrid },
                { title: 'Browse catalogue', url: '/courses', icon: Library },
                { title: 'My orders', url: '/orders', icon: Receipt },
                ...(staff ? [] : [{ title: 'Handbook', url: '/handbook', icon: BookOpen }]),
            ],
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={staff ? '/admin/courses' : '/dashboard'} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-4">
                <NavMain groups={groups} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
