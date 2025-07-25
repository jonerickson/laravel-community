import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    CalendarSync,
    CircleDollarSign,
    CircleUser,
    CreditCard,
    DollarSign,
    Folder,
    LayoutGrid,
    LibraryBig,
    Newspaper,
    ShieldIcon,
    ShoppingCart,
    TowerControl,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Blog',
        href: '/blog',
        icon: Newspaper,
    },
    {
        title: 'Forums',
        href: '/forums',
        icon: LibraryBig,
    },
];

const accountNavItems: NavItem[] = [
    {
        title: 'My Account',
        href: '/settings/account',
        icon: CircleUser,
    },
    {
        title: 'Billing',
        href: '/billing',
        icon: DollarSign,
        target: '_blank',
    },
    {
        title: 'Orders',
        href: '/settings/orders',
        icon: CircleDollarSign,
    },
    {
        title: 'Payment Methods',
        href: '/billing',
        icon: CreditCard,
        target: '_blank',
    },
];

const storeNavItems: NavItem[] = [
    {
        title: 'Store',
        href: '/store',
        icon: ShoppingCart,
    },
    {
        title: 'Subscriptions',
        href: '/subscriptions',
        icon: CalendarSync,
    },
    {
        title: 'Marketplace',
        href: '/marketplace',
        icon: ShieldIcon,
        target: '_blank',
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Admin Panel',
        href: '/admin',
        icon: TowerControl,
        target: '_blank',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Policies',
        href: '/policies',
        icon: Folder,
    },
    {
        title: 'Support',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { isAdmin } = usePage<SharedData>().props.auth;

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
                <NavMain title="Platform" items={mainNavItems} />
                <NavMain title="Account" items={accountNavItems} />
                <NavMain title="Store" items={storeNavItems} />
                {isAdmin && <NavMain title="Administration" items={adminNavItems} />}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
