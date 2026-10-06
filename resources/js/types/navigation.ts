import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

/**
 * A sidebar link whose page may not exist yet; it stays visible but disabled until its route lands.
 */
export type SidebarNavItem = Omit<NavItem, 'href'> & {
    href: NavItem['href'] | null;
};
