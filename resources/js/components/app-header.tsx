import { Breadcrumbs } from '@/components/breadcrumbs';
import { GlobalSearch } from '@/components/global-search';
import { Icon } from '@/components/icon';
import { ShoppingCartIcon } from '@/components/shopping-cart-icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NavigationMenu, NavigationMenuItem, NavigationMenuList, navigationMenuTriggerStyle } from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, CalendarSync, Folder, Grid, Home, LibraryBig, Menu, Newspaper, ShoppingCart } from 'lucide-react';
import AppLogo from './app-logo';
import AppLogoIcon from './app-logo-icon';

const activeItemStyles = 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    const page = usePage<App.Data.SharedData>();
    const { auth, navigationPages } = page.props;
    const { isImpersonating } = auth;
    const getInitials = useInitials();

    const mainNavItems: NavItem[] = [
        {
            title: 'Home',
            href: '/',
            icon: Home,
            shouldShow: (auth: App.Data.AuthData): boolean => auth?.user === null,
            isActive: () => route().current('home'),
        },
        {
            title: 'Dashboard',
            href: () => route('dashboard'),
            icon: Grid,
            shouldShow: (auth: App.Data.AuthData): boolean => auth?.user !== null,
            isActive: () => route().current('dashboard'),
        },
        {
            title: 'Blog',
            href: () => route('blog.index'),
            icon: Newspaper,
            isActive: () => route().current('blog.*'),
        },
        {
            title: 'Forums',
            href: () => route('forums.index'),
            icon: LibraryBig,
            isActive: () => route().current('forums.*'),
        },
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
    ];

    const rightNavItems: NavItem[] = [
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

    return (
        <>
            <nav className="relative z-20 border-b border-sidebar-border/80 bg-background">
                <div className="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                    <div className="lg:hidden">
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="mr-2 h-[34px] w-[34px]">
                                    <Menu className="size-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="flex h-full w-64 flex-col items-stretch justify-between bg-sidebar">
                                <SheetTitle className="sr-only">Navigation Menu</SheetTitle>
                                <SheetHeader className="flex justify-start text-left">
                                    <AppLogoIcon className="fill-current text-black dark:text-white" />
                                </SheetHeader>
                                <div className="flex h-full flex-1 flex-col space-y-4 p-4">
                                    <div className="flex h-full flex-col justify-between text-sm">
                                        <div className="flex flex-col space-y-4">
                                            {mainNavItems.map((item) => {
                                                const shouldShow =
                                                    typeof item.shouldShow === 'function' ? item.shouldShow(auth) : item.shouldShow || true;

                                                return shouldShow ? (
                                                    <Link
                                                        key={item.title}
                                                        href={typeof item.href === 'function' ? item.href() : item.href}
                                                        className="flex items-center space-x-2 font-medium"
                                                    >
                                                        {item.icon && <Icon iconNode={item.icon} className="size-5" />}
                                                        <span>{item.title}</span>
                                                    </Link>
                                                ) : null;
                                            })}

                                            {navigationPages && navigationPages.length > 0 && (
                                                <>
                                                    <div className="my-2 border-t border-sidebar-border/80" />
                                                    {navigationPages
                                                        .sort((a, b) => a.order - b.order)
                                                        .map((page) => (
                                                            <Link key={page.id} href={page.url} className="flex items-center space-x-2 font-medium">
                                                                <span>{page.label}</span>
                                                            </Link>
                                                        ))}
                                                </>
                                            )}
                                        </div>

                                        <div className="flex flex-col space-y-4">
                                            {rightNavItems.map((item) => (
                                                <a
                                                    key={item.title}
                                                    href={typeof item.href === 'function' ? item.href() : item.href}
                                                    rel="noopener noreferrer"
                                                    className="flex items-center space-x-2 font-medium"
                                                >
                                                    {item.icon && <Icon iconNode={item.icon} className="size-5" />}
                                                    <span>{item.title}</span>
                                                </a>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    <Link href={route('home')} prefetch className="flex items-center space-x-2">
                        <AppLogo />
                    </Link>

                    <div className="ml-6 hidden h-full items-center space-x-6 lg:flex">
                        <NavigationMenu className="flex h-full items-stretch">
                            <NavigationMenuList className="flex h-full items-stretch space-x-2">
                                {mainNavItems.map((item, index) => {
                                    const shouldShow = typeof item.shouldShow === 'function' ? item.shouldShow(auth) : item.shouldShow || true;

                                    return shouldShow ? (
                                        <NavigationMenuItem key={index} className="relative flex h-full items-center">
                                            <Link
                                                href={typeof item.href === 'function' ? item.href() : item.href}
                                                className={cn(navigationMenuTriggerStyle(), item.isActive() && activeItemStyles, 'h-9 px-3')}
                                            >
                                                {item.icon && <Icon iconNode={item.icon} className="mr-2 size-4" />}
                                                {item.title}
                                            </Link>
                                            {item.isActive() && (
                                                <div className="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"></div>
                                            )}
                                        </NavigationMenuItem>
                                    ) : null;
                                })}

                                {navigationPages?.map((page) => {
                                    const isActive = route().current('pages.show', { slug: page.slug });

                                    return (
                                        <NavigationMenuItem key={page.id} className="relative flex h-full items-center">
                                            <Link
                                                href={page.url}
                                                className={cn(navigationMenuTriggerStyle(), isActive && activeItemStyles, 'h-9 px-3')}
                                            >
                                                {page.label}
                                            </Link>
                                            {isActive && (
                                                <div className="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"></div>
                                            )}
                                        </NavigationMenuItem>
                                    );
                                })}
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>

                    <div className="ml-auto flex items-center space-x-2">
                        <div className="relative flex items-center space-x-1">
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <GlobalSearch />
                                </TooltipTrigger>
                                <TooltipContent>Search</TooltipContent>
                            </Tooltip>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <ShoppingCartIcon />
                                </TooltipTrigger>
                                <TooltipContent>Shopping Cart</TooltipContent>
                            </Tooltip>
                            <div className="hidden lg:flex">
                                {rightNavItems.map((item, index) => (
                                    <TooltipProvider key={index} delayDuration={0}>
                                        <Tooltip>
                                            <TooltipTrigger>
                                                <a
                                                    href={typeof item.href === 'function' ? item.href() : item.href}
                                                    rel="noopener noreferrer"
                                                    className="group ml-1 inline-flex h-9 w-9 items-center justify-center rounded-md bg-transparent p-0 text-sm font-medium text-accent-foreground ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                                >
                                                    <span className="sr-only">{item.title}</span>
                                                    {item.icon && <Icon iconNode={item.icon} className="size-5 opacity-80 group-hover:opacity-100" />}
                                                </a>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{item.title}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                ))}
                            </div>
                        </div>
                        {auth?.user ? (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" className="size-10 rounded-full p-1">
                                        <Avatar
                                            className={cn(
                                                'size-8 overflow-hidden rounded-full',
                                                isImpersonating && 'mr-1 size-7 ring-2 ring-destructive ring-offset-2 ring-offset-background',
                                            )}
                                        >
                                            {auth.user.avatarUrl && <AvatarImage src={auth.user.avatarUrl} alt={auth.user.name} />}
                                            <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                                {getInitials(auth.user.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-56" align="end">
                                    <UserMenuContent user={auth.user} />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ) : (
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={route('login')}>Login</Link>
                            </Button>
                        )}
                    </div>
                </div>
            </nav>
            {breadcrumbs.length > 1 && (
                <div className="flex w-full border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
