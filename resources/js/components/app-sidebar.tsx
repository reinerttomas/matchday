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
import { index as changes } from '@/routes/changes';
import { index as fixtures } from '@/routes/fixtures';
import { index as imports } from '@/routes/imports';
import { index as seasons } from '@/routes/seasons';
import { index as teams } from '@/routes/teams';
import { index as venues } from '@/routes/venues';
import type { NavItem, SidebarNavItem } from '@/types';

const settingsNavItems: SidebarNavItem[] = [
    { title: 'Týmy', href: teams(), icon: Users },
    { title: 'Haly', href: venues(), icon: MapPin },
    { title: 'Sezony', href: seasons(), icon: CalendarRange },
];

export function AppSidebar() {
    const { adminSelection, unsentChangeSummaryCount } = usePage().props;
    const teamSeason = adminSelection?.teamSeason ?? null;

    const teamNavItems: SidebarNavItem[] = [
        { title: 'Rozpis zápasů', href: fixtures(), icon: CalendarDays },
        {
            title: 'Změny',
            href: changes(),
            icon: History,
            badge: unsentChangeSummaryCount,
        },
        { title: 'Importy', href: imports(), icon: DownloadCloud },
    ];

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
