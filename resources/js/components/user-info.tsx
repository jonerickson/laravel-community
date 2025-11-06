import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { Link } from '@inertiajs/react';

export function UserInfo({ user, showEmail = false, showGroups = false }: { user: App.Data.UserData; showEmail?: boolean; showGroups?: boolean }) {
    const getInitials = useInitials();

    if (!user) {
        return null;
    }

    return (
        <Link href={route('users.show', user.id)} className="flex flex-row items-center gap-2 transition-opacity hover:opacity-80">
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                {user.avatarUrl && <AvatarImage src={user.avatarUrl} alt={user.name} />}
                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="flex flex-col">
                <div className="grid flex-1 text-left text-sm leading-tight">
                    <span className="truncate font-medium">{user.name}</span>
                    {showEmail && <span className="truncate text-xs text-muted-foreground">{user.email}</span>}
                </div>
                {showGroups && user.groups.length > 0 && (
                    <ul className="flex text-xs font-medium">
                        {user.groups.map((group) => (
                            <li key={group.id} className="after:mr-1 after:text-muted-foreground after:content-[','] last:after:hidden">
                                <span
                                    style={{
                                        color: group.color || undefined,
                                    }}
                                >
                                    {group.name}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </Link>
    );
}
