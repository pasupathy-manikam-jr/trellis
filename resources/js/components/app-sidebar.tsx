import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChartNoAxesColumn, GraduationCap, LayoutGrid, Library, Receipt, Tag, Ticket, Users } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const navItems: NavItem[] = [
        ...mainNavItems,
        { title: 'Browse courses', url: '/courses', icon: Library },
        { title: 'My orders', url: '/orders', icon: Receipt },
        // Instructors get the course workspace; the rest stays with admins.
        ...(auth.user?.role === 'admin' || auth.user?.role === 'instructor'
            ? [
                  { title: 'Manage courses', url: '/admin/courses', icon: GraduationCap },
                  { title: 'Insights', url: '/admin/insights', icon: ChartNoAxesColumn },
              ]
            : []),
        ...(auth.user?.role === 'admin'
            ? [
                  { title: 'All orders', url: '/admin/orders', icon: Receipt },
                  { title: 'Categories', url: '/admin/categories', icon: Tag },
                  { title: 'Coupons', url: '/admin/coupons', icon: Ticket },
                  { title: 'People', url: '/admin/users', icon: Users },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
