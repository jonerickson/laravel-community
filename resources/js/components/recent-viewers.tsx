import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';

interface RecentViewersProps {
    viewers: App.Data.RecentViewerData[];
}

export default function RecentViewers({ viewers }: RecentViewersProps) {
    if (!viewers || !Array.isArray(viewers) || viewers.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardContent className="pt-0">
                <CardTitle className="flex items-center gap-2 text-base">Recently viewed</CardTitle>
                <div className="mt-4 flex flex-wrap gap-2">
                    {viewers &&
                        viewers.map((viewer) => (
                            <Link
                                href={route('users.show', viewer.user.referenceId)}
                                key={viewer.user.id}
                                className="flex items-center gap-2 rounded-md bg-muted/50 p-2 text-sm hover:opacity-80"
                            >
                                <Avatar className="h-6 w-6">
                                    {viewer.user.avatarUrl && <AvatarImage src={viewer.user.avatarUrl} alt={viewer.user.name} />}
                                    <AvatarFallback className="text-xs">{viewer.user.name.charAt(0).toUpperCase()}</AvatarFallback>
                                </Avatar>
                                <div className="flex flex-col">
                                    <span className="leading-none font-medium">{viewer.user.name}</span>
                                    <span className="text-xs text-muted-foreground">
                                        {formatDistanceToNow(new Date(viewer.viewedAt), { addSuffix: true })}
                                    </span>
                                </div>
                            </Link>
                        ))}
                </div>
                {viewers.length > 8 && (
                    <div className="mt-2 text-xs text-muted-foreground">And {viewers.length - 8} more recently viewed this topic</div>
                )}
            </CardContent>
        </Card>
    );
}
