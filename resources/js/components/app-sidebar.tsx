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
        href: () => route('dashboard'),
        icon: LayoutGrid,
    },
    {
        title: 'Blog',
        href: () => route('blog.index'),
        icon: Newspaper,
    },
    {
        title: 'Forums',
        href: () => route('forums.index'),
        icon: LibraryBig,
    },
];

const accountNavItems: NavItem[] = [
    {
        title: 'My Account',
        href: () => route('settings'),
        icon: CircleUser,
    },
    {
        title: 'Billing',
        href: () => route('settings.billing'),
        icon: DollarSign,
    },
    {
        title: 'Orders',
        href: () => route('settings.invoices'),
        icon: CircleDollarSign,
    },
    {
        title: 'Payment Methods',
        href: () => route('settings.payment-methods'),
        icon: CreditCard,
    },
];

const storeNavItems: NavItem[] = [
    {
        title: 'Store',
        href: () => route('store.index'),
        icon: ShoppingCart,
    },
    {
        title: 'Subscriptions',
        href: () => route('store.subscriptions'),
        icon: CalendarSync,
    },
    {
        title: 'Marketplace',
        href: '/marketplace',
        icon: ShieldIcon,
        target: '_blank',
    },
];

const supportNavItems: NavItem[] = [
    {
        title: 'Policies',
        href: () => route('policies.index'),
        icon: Folder,
    },
    {
        title: 'Support',
        href: () => route('support.index'),
        icon: BookOpen,
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

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { isAdmin } = usePage<SharedData>().props.auth;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('dashboard')} prefetch>
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
                <NavMain title="Support" items={supportNavItems} />
                {isAdmin && <NavMain title="Administration" items={adminNavItems} />}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
