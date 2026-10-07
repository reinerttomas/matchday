import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    CalendarRange,
    DownloadCloud,
    Globe,
    History,
    MapPin,
    Users,
} from 'lucide-react';
import { AdminSelectionSwitcher } from '@/components/admin-selection-switcher';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import { dashboard, publicTeamPage } from '@/routes';
import { index as fixtures } from '@/routes/fixtures';
import { index as imports } from '@/routes/imports';
import type { NavItem, SidebarNavItem } from '@/types';

// Pages without a route yet stay disabled (href null) until their tickets add them.
const teamNavItems: SidebarNavItem[] = [
    { title: 'Rozpis zápasů', href: fixtures(), icon: CalendarDays },
    { title: 'Změny', href: null, icon: History },
    { title: 'Importy', href: imports(), icon: DownloadCloud },
];

const settingsNavItems: SidebarNavItem[] = [
    { title: 'Týmy', href: null, icon: Users },
    { title: 'Haly', href: null, icon: MapPin },
    { title: 'Sezony', href: null, icon: CalendarRange },
];

export function AppSidebar() {
    const { adminSelection } = usePage().props;
    const teamSeason = adminSelection?.teamSeason ?? null;

    const footerNavItems: NavItem[] =
        teamSeason === null
            ? []
            : [
                  {
                      title: 'Veřejná stránka týmu',
                      href: publicTeamPage(teamSeason.teamSlug),
                      icon: Globe,
                  },
              ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <AdminSelectionSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain title="Tým" items={teamNavItems} />
                <NavMain title="Nastavení" items={settingsNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
