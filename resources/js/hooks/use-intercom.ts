import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

declare global {
    interface Window {
        Intercom: (command: string, ...args: unknown[]) => void;
        intercomSettings: Record<string, unknown>;
    }
}

export function useIntercom(): void {
    const { intercom } = usePage<App.Data.SharedData>().props;
    const scriptLoadedRef = useRef(false);

    useEffect(() => {
        if (!intercom?.appId) {
            return;
        }

        const appId = intercom.appId;

        window.intercomSettings = {
            api_base: 'https://api-iam.intercom.io',
            app_id: appId,
            ...(intercom.userId && {
                name: intercom.userName,
                email: intercom.userEmail,
                user_id: intercom.userId,
                created_at: intercom.createdAt,
            }),
        };

        if (!scriptLoadedRef.current) {
            const script = document.createElement('script');
            script.async = true;
            script.src = `https://widget.intercom.io/widget/${appId}`;
            script.onload = () => {
                window.Intercom('boot', window.intercomSettings);
            };
            document.body.appendChild(script);
            scriptLoadedRef.current = true;
        } else if (window.Intercom) {
            window.Intercom('update', window.intercomSettings);
        }

        return () => {
            if (window.Intercom) {
                window.Intercom('shutdown');
            }
        };
    }, [intercom?.appId, intercom?.userId, intercom?.userName, intercom?.userEmail, intercom?.createdAt]);
}
