import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Link } from '@inertiajs/react';

export default function ForumUserInfo({ user, isAuthor = false }: { user: App.Data.UserData; isAuthor?: boolean }) {
    return (
        <Link href={route('users.show', user.id)} className="flex flex-row items-center gap-4 hover:opacity-80 md:flex-col md:gap-2 md:px-8">
            <Avatar className="size-12">
                {user.avatarUrl && <AvatarImage src={user.avatarUrl} alt={user.name} />}
                <AvatarFallback>{user.name.charAt(0).toUpperCase()}</AvatarFallback>
            </Avatar>
            <div className="flex flex-col">
                <div className="text-left md:text-center">
                    <div className="text-sm font-medium">{user.name}</div>
                    <div className="text-xs text-muted-foreground">{isAuthor ? 'Author' : ''}</div>
                </div>

                {user.groups.length > 0 && (
                    <ul className="flex flex-row text-xs font-medium md:mt-4 md:block md:flex-col md:text-center">
                        {user.groups.map((group) => (
                            <li
                                className="after:mr-1 after:text-muted-foreground after:content-[','] last:after:hidden md:after:hidden"
                                key={group.id}
                                style={{
                                    color: group.color || undefined,
                                }}
                            >
                                {group.name}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </Link>
    );
}
