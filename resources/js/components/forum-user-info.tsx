import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { User } from '@/types';

export default function ForumUserInfo({ user, isAuthor = false }: { user: User; isAuthor?: boolean }) {
    return (
        <div className="flex flex-row items-center gap-4 md:flex-col md:gap-2 md:px-8">
            <Avatar className="h-12 w-12">
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback>{user.name.charAt(0).toUpperCase()}</AvatarFallback>
            </Avatar>
            <div className="text-center">
                <div className="text-sm font-medium">{user.name}</div>
                <div className="text-xs text-muted-foreground">{isAuthor ? 'Author' : ''}</div>
            </div>
            {user.groups.length > 0 && (
                <div className="mt-4 hidden text-xs font-medium md:block">
                    {user.groups.map((group) => (
                        <div
                            key={group.id}
                            style={{
                                color: group.color || undefined,
                            }}
                        >
                            {group.name}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
