import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { Link, router, usePage } from '@inertiajs/react';
import { BookOpen, CircleDollarSign, CircleUser, CreditCard, DollarSign, EyeOff, LogOut, Settings, ShieldIcon, TowerControl } from 'lucide-react';

interface UserMenuContentProps {
    user: App.Data.UserData;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const { isAdmin, isImpersonating } = usePage<App.Data.SharedData>().props.auth;
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            {isImpersonating && (
                <>
                    <DropdownMenuSeparator />
                    <DropdownMenuGroup>
                        <DropdownMenuItem asChild>
                            <Link className="block w-full" href={route('impersonate.leave')} as="button">
                                <EyeOff className="mr-2" />
                                Stop Impersonating
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                </>
            )}
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link className="block w-full" href={route('settings.profile.edit')} as="button" prefetch onClick={cleanup}>
                        <CircleUser className="mr-2" />
                        My Account
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link className="block w-full" href={route('settings.billing')} as="button" prefetch onClick={cleanup}>
                        <DollarSign className="mr-2" />
                        Billing
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link className="block w-full" href={route('settings.orders')} as="button" prefetch onClick={cleanup}>
                        <CircleDollarSign className="mr-2" />
                        Orders
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link className="block w-full" href={route('settings.payment-methods')} as="button" prefetch onClick={cleanup}>
                        <CreditCard className="mr-2" />
                        Payment Methods
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                {isAdmin && (
                    <DropdownMenuItem asChild>
                        <a className="block w-full" href="/admin" target="__blank">
                            <TowerControl className="mr-2" />
                            Administration
                        </a>
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem asChild>
                    <a className="block w-full" href="/marketplace" target="__blank">
                        <ShieldIcon className="mr-2" />
                        Marketplace
                    </a>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link className="block w-full" href={route('settings.profile.edit')} as="button" prefetch onClick={cleanup}>
                        <Settings className="mr-2" />
                        Settings
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link className="block w-full" href={route('support.index')} as="button" prefetch onClick={cleanup}>
                        <BookOpen className="mr-2" />
                        Support
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link className="block w-full" method="post" href={route('logout')} as="button" onClick={handleLogout}>
                    <LogOut className="mr-2" />
                    Log out
                </Link>
            </DropdownMenuItem>
        </>
    );
}
