import { Link, usePage } from '@inertiajs/react';
import { Activity, BarChart3, Calendar, FileText, LayoutGrid, Users } from 'lucide-react';
import { NavMain } from '@/components/nav-main';
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
import { analytics, dashboard as dashboardRoute, health } from '@/routes';
import { index as calendarsIndex } from '@/routes/calendars';
import { index as notesIndex } from '@/routes/notes';
import { index as usersIndex } from '@/routes/users';
import type { NavItem, SharedData } from '@/types';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    const isAdmin = user?.role === 'admin' || user?.role === 'mango';
    const isMango = user?.role === 'mango';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardRoute(),
            icon: LayoutGrid,
        },
        {
            title: 'Calendarios',
            href: calendarsIndex(),
            icon: Calendar,
        },
        {
            title: 'Notas',
            href: notesIndex(),
            icon: FileText,
        },
        ...(isAdmin
            ? [
                  {
                      title: 'Usuarios',
                      href: usersIndex(),
                      icon: Users,
                  },
                  {
                      title: 'Analíticas',
                      href: analytics(),
                      icon: BarChart3,
                  },
              ]
            : []),
        ...(isMango
            ? [
                  {
                      title: 'Salud',
                      href: health(),
                      icon: Activity,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardRoute()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
