import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { Bell, BellOff } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface FollowButtonProps {
    type: 'forum' | 'topic';
    id: number;
    isFollowing: boolean;
    followersCount?: number;
    variant?: 'default' | 'outline' | 'ghost' | 'secondary';
    size?: 'default' | 'sm' | 'lg' | 'icon';
}

export function FollowButton({ type, id, isFollowing, followersCount, variant = 'outline', size = 'default' }: FollowButtonProps) {
    const [loading, setLoading] = useState(false);

    const handleToggleFollow = () => {
        setLoading(true);

        const routeName = isFollowing ? 'forums.unfollow' : 'forums.follow';

        router.visit(route(routeName, { type, id }), {
            method: isFollowing ? 'delete' : 'post',
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                setLoading(false);
            },
        });
    };

    return (
        <Button variant={variant} size={size} onClick={handleToggleFollow} disabled={loading}>
            {isFollowing ? <BellOff className="h-4 w-4" /> : <Bell className="h-4 w-4" />}
            {size !== 'icon' && (
                <span>
                    {isFollowing ? 'Unfollow' : 'Follow'}
                    {followersCount !== undefined && followersCount > 0 && ` (${followersCount})`}
                </span>
            )}
        </Button>
    );
}
