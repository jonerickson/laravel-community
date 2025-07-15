import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
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
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'News',
        href: '/news',
        icon: Newspaper,
    },
    {
        title: 'Forums',
        href: '/dashboard',
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
        href: '/settings/billing',
        icon: DollarSign,
    },
    {
        title: 'Orders',
        href: '/settings/orders',
        icon: CircleDollarSign,
    },
    {
        title: 'Payment Methods',
        href: '/settings/payment-methods',
        icon: CreditCard,
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

const footerNavItems: NavItem[] = [
    {
        title: 'Legal',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Support',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
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
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
