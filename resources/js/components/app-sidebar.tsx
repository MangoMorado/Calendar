import { Link, usePage } from '@inertiajs/react';
import { Calendar, LayoutGrid, Users } from 'lucide-react';
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
import { dashboard as dashboardRoute } from '@/routes';
import { index as calendarsIndex } from '@/routes/calendars';
import type { NavItem, SharedData } from '@/types';
import AppLogo from './app-logo';
import { index as usersIndex } from '@/routes/users';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    // Verificar si el usuario tiene rol Admin o Mango
    const isAdmin = user?.role === 'admin' || user?.role === 'mango';

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
        ...(isAdmin
            ? [
                  {
                      title: 'Usuarios',
                      href: usersIndex(),
                      icon: Users,
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
