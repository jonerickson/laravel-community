import { Button } from '@/components/ui/button';
import { useApiRequest } from '@/hooks';
import { usePage } from '@inertiajs/react';
import { Bell, BellOff, LoaderCircle } from 'lucide-react';
import { route } from 'ziggy-js';

interface FollowButtonProps {
    type: 'forum' | 'topic';
    id: number;
    isFollowing: boolean;
    followersCount?: number;
    variant?: 'default' | 'outline' | 'ghost' | 'secondary';
    size?: 'default' | 'sm' | 'lg' | 'icon';
    onSuccess?: () => void;
}

export function FollowButton({ type, id, isFollowing, followersCount, variant = 'outline', size = 'default', onSuccess }: FollowButtonProps) {
    const { auth } = usePage<App.Data.SharedData>().props;
    const { execute, loading } = useApiRequest();

    if (!auth || !auth.user) {
        return null;
    }

    const handleToggleFollow = async () => {
        const url = isFollowing ? route('api.follow.destroy') : route('api.follow.store');
        const method = isFollowing ? 'DELETE' : 'POST';

        await execute(
            {
                url,
                method,
                data: {
                    type: type,
                    id: id,
                },
            },
            {
                onSuccess: () => {
                    if (onSuccess) onSuccess();
                },
            },
        );
    };

    return (
        <Button variant={variant} size={size} onClick={handleToggleFollow} disabled={loading}>
            {loading ? <LoaderCircle className="animate-spin" /> : isFollowing ? <BellOff className="h-4 w-4" /> : <Bell className="h-4 w-4" />}
            {size !== 'icon' && (
                <span>
                    {isFollowing ? 'Unfollow' : 'Follow'}
                    {followersCount !== undefined && followersCount > 0 && ` (${followersCount})`}
                </span>
            )}
        </Button>
    );
}
