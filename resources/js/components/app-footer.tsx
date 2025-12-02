import HeadingSmall from '@/components/heading-small';
import { Icon } from '@/components/icon';
import { useLayout } from '@/hooks';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    CalendarSync,
    CircleUser,
    Folder,
    Grid,
    HelpCircle,
    Home,
    LibraryBig,
    Mail,
    MapPin,
    Newspaper,
    Phone,
    Search,
    ShoppingCart,
} from 'lucide-react';
import AppLogo from './app-logo';

export function AppFooter() {
    const page = usePage<App.Data.SharedData>();
    const { auth, name, phone, email, address, slogan } = page.props;
    const { layout } = useLayout();

    const mainNavItems: NavItem[] = [
        {
            title: 'Home',
            href: '/',
            icon: Home,
            shouldShow: (auth: App.Data.AuthData): boolean => auth?.user === null,
            isActive: () => true,
        },
        {
            title: 'Dashboard',
            href: () => route('dashboard'),
            icon: Grid,
            shouldShow: (auth: App.Data.AuthData): boolean => auth?.user !== null,
            isActive: () => true,
        },
        {
            title: 'Blog',
            href: () => route('blog.index'),
            icon: Newspaper,
            isActive: () => true,
        },
        {
            title: 'Forums',
            href: () => route('forums.index'),
            icon: LibraryBig,
            isActive: () => true,
        },
        {
            title: 'Store',
            href: () => route('store.index'),
            icon: ShoppingCart,
            isActive: () => true,
        },
        {
            title: 'Subscriptions',
            href: () => route('store.subscriptions'),
            icon: CalendarSync,
            isActive: () => true,
        },
    ];

    const supportNavItems: NavItem[] = [
        {
            title: 'Knowledge Base',
            href: () => route('knowledge-base.index'),
            icon: HelpCircle,
            isActive: () => true,
        },
        {
            title: 'Policies',
            href: () => route('policies.index'),
            icon: Folder,
            isActive: () => true,
        },
        {
            title: 'Support',
            href: () => route('support.index'),
            icon: BookOpen,
            isActive: () => true,
        },
    ];

    return (
        <footer className="border-t border-sidebar-border/80 bg-sidebar/30">
            <div className={`mx-auto max-w-7xl pt-12 pb-8 ${layout === 'header' ? 'px-6 lg:px-4' : 'px-6 lg:px-8'}`}>
                <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-4">
                        <Link href={route('dashboard')} className="flex items-center space-x-2">
                            <AppLogo />
                        </Link>
                        <p className="text-sm text-muted-foreground">{slogan}</p>
                        <div className="space-y-2">
                            {email && (
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Mail className="size-4" />
                                    <span>{email}</span>
                                </div>
                            )}
                            {phone && (
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Phone className="size-4" />
                                    <span>{phone}</span>
                                </div>
                            )}
                            {address && (
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <MapPin className="size-4" />
                                    <span>{address}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-4">
                        <HeadingSmall title="Navigation" />
                        <div className="space-y-2">
                            {mainNavItems.map((item) => {
                                const shouldShow = typeof item.shouldShow === 'function' ? item.shouldShow(auth) : item.shouldShow !== false;

                                return shouldShow ? (
                                    <Link
                                        key={item.title}
                                        href={typeof item.href === 'function' ? item.href() : item.href}
                                        className="flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                                    >
                                        {item.icon && <Icon iconNode={item.icon} className="size-4" />}
                                        {item.title}
                                    </Link>
                                ) : null;
                            })}
                        </div>
                    </div>

                    <div className="space-y-4">
                        <HeadingSmall title="Support & Resources" />
                        <div className="space-y-2">
                            {supportNavItems.map((item) => (
                                <Link
                                    key={item.title}
                                    href={typeof item.href === 'function' ? item.href() : item.href}
                                    className="flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    {item.icon && <Icon iconNode={item.icon} className="size-4" />}
                                    {item.title}
                                </Link>
                            ))}
                        </div>
                    </div>

                    <div className="space-y-4">
                        <HeadingSmall title="Other" />
                        <div className="space-y-2">
                            <Link
                                href={route('settings.profile.edit')}
                                className="flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                            >
                                <CircleUser className="size-4" />
                                My Account
                            </Link>
                            <Link
                                href={route('search')}
                                className="flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                            >
                                <Search className="size-4" />
                                Search
                            </Link>
                            <Link
                                href={route('store.cart.index')}
                                className="flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                            >
                                <ShoppingCart className="size-4" />
                                Shopping Cart
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mt-8 border-t border-sidebar-border/50 pt-8">
                    <div className="flex flex-col items-center justify-between gap-4 text-center md:flex-row">
                        <div className="flex flex-col items-center gap-4 md:flex-row">
                            <p className="text-sm text-muted-foreground">
                                &copy; {new Date().getFullYear()} {name}. All rights reserved.
                            </p>
                        </div>
                        <div className="flex items-center space-x-4">
                            <div className="text-xs text-muted-foreground">
                                Made with ❤️ by{' '}
                                <a href="https://deschutesdesigngroup.com" target="__blank">
                                    Deschutes Design Group LLC
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    );
}
