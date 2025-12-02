import { StyledUserName } from '@/components/styled-user-name';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Link } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';

export default function ForumUserInfo({
    user,
    isAuthor = false,
    dateTime = null,
}: {
    user: App.Data.UserData;
    isAuthor?: boolean;
    dateTime?: string | null;
}) {
    const content = (
        <>
            <Avatar className="size-12">
                {user.avatarUrl && <AvatarImage src={user.avatarUrl} alt={user.name} />}
                <AvatarFallback>{user.name.charAt(0).toUpperCase()}</AvatarFallback>
            </Avatar>

            <div className="flex flex-col items-start md:items-center md:gap-2">
                <div className="text-left md:text-center">
                    <StyledUserName user={user} className="text-sm tracking-tight" />
                    <div className="hidden text-xs text-muted-foreground md:block">{isAuthor ? 'Author' : ''}</div>
                </div>

                {user.groups.length > 0 && (
                    <ul className="flex flex-wrap text-xs leading-snug font-light md:flex-col md:flex-nowrap md:items-center md:justify-center">
                        {user.groups.map((group) => (
                            <li
                                key={group.id}
                                className="after:mr-1 after:text-current after:content-[','] last:after:hidden md:truncate md:after:hidden"
                                style={{ color: group.color || undefined }}
                            >
                                {group.name}
                            </li>
                        ))}
                    </ul>
                )}

                {dateTime && (
                    <time className="text-xs font-normal text-muted-foreground md:hidden" itemProp="dateCreated" dateTime={dateTime || undefined}>
                        Posted {dateTime ? formatDistanceToNow(new Date(dateTime), { addSuffix: true }) : 'N/A'}
                    </time>
                )}
            </div>
        </>
    );

    if (!user.referenceId) {
        return <div className="flex flex-row items-center gap-4 md:flex-col md:items-center md:gap-2 md:px-8">{content}</div>;
    }

    return (
        <Link
            href={route('users.show', { user: user.referenceId })}
            className="flex flex-row items-center gap-4 hover:opacity-80 md:flex-col md:items-center md:gap-2 md:px-8"
        >
            {content}
        </Link>
    );
}
