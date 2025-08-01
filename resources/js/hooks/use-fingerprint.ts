import FingerprintService from '@/services/fingerprint';
import { useEffect, useState } from 'react';

interface UseFingerprintReturn {
    fingerprintId: string | null;
    isLoading: boolean;
    error: string | null;
}

export function useFingerprint(): UseFingerprintReturn {
    const [fingerprintId, setFingerprintId] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const initializeFingerprint = async () => {
            try {
                const service = FingerprintService.getInstance();
                const fingerprint = await service.getFingerprint();

                if (fingerprint) {
                    setFingerprintId(fingerprint.visitorId);
                    await service.trackFingerprint();
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
    }, []);

    return { fingerprintId, isLoading, error };
}
