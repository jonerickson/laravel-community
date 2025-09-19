import { useApiRequest } from '@/hooks/use-api-request';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { route } from 'ziggy-js';

interface UseMarkAsReadOptions {
    id: number;
    type: 'topic' | 'post' | 'forum' | 'announcement';
    isRead: boolean;
    enabled?: boolean;
}

export function useMarkAsRead({ id, type, isRead, enabled = true }: UseMarkAsReadOptions) {
    const { auth } = usePage<App.Data.SharedData>().props;
    const { execute } = useApiRequest<App.Data.ReadData>();

    useEffect(() => {
        if (!enabled || !auth?.user) {
            return;
        }

        const markAsRead = async () => {
            await execute({
                url: route('api.read'),
                method: 'POST',
                data: {
                    type,
                    id,
                },
            });
        };

        markAsRead();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id, type, isRead, enabled, auth?.user]);
}
