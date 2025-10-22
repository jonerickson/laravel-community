import { useApiRequest } from '@/hooks/use-api-request';
import { useVisitorData } from '@fingerprintjs/fingerprintjs-pro-react';
import { useEffect } from 'react';
import { route } from 'ziggy-js';

interface UseFingerprintReturn {
    fingerprintId: string | null;
}

export function useFingerprint(): UseFingerprintReturn {
    const { execute } = useApiRequest<App.Data.FingerprintData>();
    const { isLoading, error, data } = useVisitorData({ extendedResult: true }, { immediate: true });

    useEffect(() => {
        if (isLoading || !data?.visitorId || error) {
            return;
        }

        const initializeFingerprint = async () => {
            await execute({
                url: route('api.fingerprint'),
                method: 'POST',
                data: {
                    fingerprint_id: data.visitorId,
                    request_id: data.requestId,
                },
                config: {
                    headers: {
                        'X-Fingerprint-ID': data?.visitorId,
                    },
                },
            });
        };

        initializeFingerprint();
    }, [isLoading, data?.visitorId]); // eslint-disable-line react-hooks/exhaustive-deps

    return { fingerprintId: data?.visitorId || '' };
}
