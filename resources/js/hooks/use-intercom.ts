import Intercom, { shutdown, update } from '@intercom/messenger-js-sdk';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

export function useIntercom(): void {
    const { intercom } = usePage<App.Data.SharedData>().props;
    const bootedRef = useRef(false);

    useEffect(() => {
        if (!intercom?.appId) {
            return;
        }

        const settings = {
            app_id: intercom.appId,
            api_base: 'https://api-iam.intercom.io',
            session_duration: 86400000,
            ...(intercom.userId && {
                name: intercom.userName ?? undefined,
                email: intercom.userEmail ?? undefined,
                user_id: String(intercom.userId),
                created_at: intercom.createdAt ?? undefined,
                ...(intercom.userJwt && { user_hash: intercom.userJwt }),
            }),
        };

        if (!bootedRef.current) {
            Intercom(settings);
            bootedRef.current = true;
        } else {
            update(settings);
        }

        return () => {
            shutdown();
        };
    }, [
        intercom?.appId,
        intercom?.userId,
        intercom?.userName,
        intercom?.userEmail,
        intercom?.createdAt,
        intercom?.userJwt,
    ]);
}
