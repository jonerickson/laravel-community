import { StyledUserName } from '@/components/styled-user-name';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';

export function UserInfo({
    user,
    showEmail = false,
    showGroups = false,
    showAvatar = true,
}: {
    user: App.Data.UserData;
    showEmail?: boolean;
    showGroups?: boolean;
    showAvatar?: boolean;
}) {
    const getInitials = useInitials();
    const { isImpersonating } = usePage<App.Data.SharedData>().props.auth;

    if (!user) {
        return null;
    }

    const content = (
        <>
            {showAvatar && (
                <Avatar
                    className={cn(
                        'size-8 overflow-hidden rounded-full',
                        isImpersonating && 'mr-1 size-7 ring-2 ring-destructive ring-offset-2 ring-offset-background',
                    )}
                >
                    {user.avatarUrl && <AvatarImage src={user.avatarUrl} alt={user.name} />}
                    <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                        {getInitials(user.name)}
                    </AvatarFallback>
                </Avatar>
            )}
            <div className="flex flex-col">
                <div className="grid flex-1 text-left text-sm leading-tight">
                    <StyledUserName user={user} className="truncate" />
                    {showEmail && <span className="truncate text-xs text-muted-foreground">{user.email}</span>}
                </div>
                {showGroups && user.groups.length > 0 && (
                    <ul className="flex max-h-[2.5em] w-full flex-wrap gap-x-1 overflow-hidden text-xs leading-snug font-medium">
                        {user.groups.map((group) => (
                            <li key={group.id} className="after:mr-1 after:text-current after:content-[','] last:after:hidden">
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
        </>
    );

    if (!user.referenceId) {
        return <div className="flex flex-row items-center gap-2 transition-opacity">{content}</div>;
    }

    return (
        <Link href={route('users.show', user.referenceId)} className="flex flex-row items-center gap-2 transition-opacity hover:opacity-80">
            {content}
        </Link>
    );
}
