import type { SharedData } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect } from 'react';

interface UseMarkAsReadOptions {
    id: number;
    type: 'topic' | 'post' | 'forum';
    isRead: boolean;
    enabled?: boolean;
}

export function useMarkAsRead({ id, type, isRead, enabled = true }: UseMarkAsReadOptions) {
    const { auth } = usePage<SharedData>().props;

    useEffect(() => {
        if (!enabled || !auth?.user) {
            return;
        }

        const markAsRead = async () => {
            try {
                await apiRequest(
                    axios.post(route('api.read'), {
                        type,
                        id,
                    }),
                );
            } catch (error) {
                console.error(`Error marking ${type} as read:`, error);
                const apiError = error as ApiError;
                console.error('API Error:', apiError.message);
            }
        };

        markAsRead();
    }, [id, type, isRead, enabled, auth?.user]);
}
