import { ReportDialog } from '@/components/report-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { SharedData, User } from '@/types';
import { usePage } from '@inertiajs/react';

export default function ForumUserInfo({ user, isAuthor = false }: { user: User; isAuthor?: boolean }) {
    const { auth } = usePage<SharedData>().props;
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

            {auth.user && auth.user.id !== user.id && (
                <div className="mt-2 hidden md:block">
                    <ReportDialog reportableType="App\Models\User" reportableId={user.id} />
                </div>
            )}
        </div>
    );
}
