import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    CalendarSync,
    CircleDollarSign,
    CircleUser,
    CreditCard,
    DollarSign,
    Folder,
    HouseIcon,
    LayoutGrid,
    LibraryBig,
    Newspaper,
    ShieldIcon,
    ShoppingCart,
    TowerControl,
} from 'lucide-react';
import AppLogo from './app-logo';

interface MainNavItem extends NavItem {
    order: number;
}

export function AppSidebar() {
    const page = usePage<App.Data.SharedData>();
    const { isAdmin } = page.props.auth;
    const { navigationPages } = page.props;

    const mainNavItems: MainNavItem[] = [
        {
            title: 'Home',
            href: () => route('home'),
            icon: HouseIcon,
            isActive: () => route().current('home'),
            order: 1,
        },
        {
            title: 'Dashboard',
            href: () => route('dashboard'),
            icon: LayoutGrid,
            isActive: () => route().current('dashboard'),
            order: 2,
        },
        {
            title: 'Blog',
            href: () => route('blog.index'),
            icon: Newspaper,
            isActive: () => route().current('blog.*'),
            order: 3,
        },
        {
            title: 'Forums',
            href: () => route('forums.index'),
            icon: LibraryBig,
            isActive: () => route().current('forums.*'),
            order: 4,
        },
    ];

    const accountNavItems: NavItem[] = [
        {
            title: 'My Account',
            href: () => route('settings'),
            icon: CircleUser,
            isActive: () => route().current('settings.profile.*'),
        },
        {
            title: 'Billing',
            href: () => route('settings.billing'),
            icon: DollarSign,
            isActive: () => route().current('settings.billing.*'),
        },
        {
            title: 'Orders',
            href: () => route('settings.orders'),
            icon: CircleDollarSign,
            isActive: () => route().current('settings.orders.*'),
        },
        {
            title: 'Payment Methods',
            href: () => route('settings.payment-methods'),
            icon: CreditCard,
            isActive: () => route().current('settings.payment-methods.*'),
        },
    ];

    const storeNavItems: NavItem[] = [
        {
            title: 'Store',
            href: () => route('store.index'),
            icon: ShoppingCart,
            isActive: () => route().current('store.*') && !route().current('store.subscriptions'),
        },
        {
            title: 'Subscriptions',
            href: () => route('store.subscriptions'),
            icon: CalendarSync,
            isActive: () => route().current('store.subscriptions'),
        },
        {
            title: 'Marketplace',
            href: '/marketplace',
            icon: ShieldIcon,
            target: '_blank',
            isActive: () => false,
        },
    ];

    const supportNavItems: NavItem[] = [
        {
            title: 'Policies',
            href: () => route('policies.index'),
            icon: Folder,
            isActive: () => route().current('policies.*'),
        },
        {
            title: 'Support',
            href: () => route('support.index'),
            icon: BookOpen,
            isActive: () => route().current('support.*'),
        },
    ];

    const adminNavItems: NavItem[] = [
        {
            title: 'Admin Panel',
            href: '/admin',
            icon: TowerControl,
            target: '_blank',
            isActive: () => false,
        },
    ];

    const footerNavItems: NavItem[] = [];

    const customNavItems =
        navigationPages?.map((navPage) => ({
            title: navPage.label,
            href: navPage.url,
            isActive: () => route().current('pages.show', { slug: navPage.slug }),
            order: navPage.order,
            isCustom: true,
        })) ?? [];

    const platformNavItems = [...mainNavItems.map((item) => ({ ...item, isCustom: false })), ...customNavItems].sort((a, b) => {
        if (a.order === b.order) {
            return a.isCustom ? -1 : 1;
        }
        return a.order - b.order;
    });

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('home')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain title="Platform" items={platformNavItems} />
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
