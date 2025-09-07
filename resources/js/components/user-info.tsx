import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { type User } from '@/types';

export function UserInfo({ user, showEmail = false, showGroups = false }: { user: User; showEmail?: boolean; showGroups?: boolean }) {
    const getInitials = useInitials();

    if (!user) {
        return null;
    }

    return (
        <div className="flex flex-row items-center gap-2">
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage src={user.avatar} alt={user.name} />
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
                    <div className="flex text-xs font-medium">
                        {user.groups.map((group) => (
                            <div key={group.id}>
                                <span
                                    style={{
                                        color: group.color || undefined,
                                    }}
                                >
                                    {group.name}
                                </span>
                                <span
                                    className="mr-1 last:hidden"
                                    style={{
                                        color: group.color || "var('--text-primary')",
                                    }}
                                >
                                    ,
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
