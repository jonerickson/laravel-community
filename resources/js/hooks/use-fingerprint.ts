import { useApiRequest } from '@/hooks/use-api-request';
import FingerprintService from '@/services/fingerprint';
import { useEffect, useState } from 'react';
import { route } from 'ziggy-js';

interface UseFingerprintReturn {
    fingerprintId: string | null;
    isLoading: boolean;
    error: string | null;
}

export function useFingerprint(): UseFingerprintReturn {
    const [fingerprintId, setFingerprintId] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const { execute } = useApiRequest<App.Data.FingerprintData>();

    useEffect(() => {
        const initializeFingerprint = async () => {
            try {
                const service = FingerprintService.getInstance();
                const fingerprint = await service.getFingerprint();

                if (fingerprint) {
                    setFingerprintId(fingerprint.visitorId);

                    await execute({
                        url: route('api.fingerprint'),
                        method: 'POST',
                        data: {
                            fingerprint_id: fingerprint.visitorId,
                            fingerprint_data: fingerprint.components,
                        },
                        config: {
                            headers: {
                                'X-Fingerprint-ID': fingerprint.visitorId,
                            },
                        },
                    });
                } else {
                    setError('Failed to generate fingerprint');
                }
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Unknown error occurred');
            } finally {
                setIsLoading(false);
            }
        };

        initializeFingerprint();
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    return { fingerprintId, isLoading, error };
}
