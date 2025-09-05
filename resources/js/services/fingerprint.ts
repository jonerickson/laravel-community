import type { FingerprintTrackingResponse } from '@/types';
import { apiRequest } from '@/utils/api';
import FingerprintJS from '@fingerprintjs/fingerprintjs';
import axios from 'axios';

class FingerprintService {
    private static instance: FingerprintService;
    private fp: Awaited<ReturnType<typeof FingerprintJS.load>> | null = null;
    private fingerprintId: string | null = null;

    private constructor() {}

    public static getInstance(): FingerprintService {
        if (!FingerprintService.instance) {
            FingerprintService.instance = new FingerprintService();
        }
        return FingerprintService.instance;
    }

    public async initialize(): Promise<void> {
        if (this.fp) return;

        try {
            this.fp = await FingerprintJS.load();
        } catch (err) {
            console.error('Error initializing FingerprintJS:', err);
        }
    }

    public async getFingerprint(): Promise<{ visitorId: string; components: Record<string, unknown> } | null> {
        if (!this.fp) {
            await this.initialize();
        }

        if (!this.fp) return null;

        try {
            const result = await this.fp.get();
            this.fingerprintId = result.visitorId;
            return result;
        } catch (err) {
            console.error('Error getting fingerprint:', err);
            return null;
        }
    }

    public getFingerprintId(): string | null {
        return this.fingerprintId;
    }

    public async trackFingerprint(): Promise<void> {
        const fingerprint = await this.getFingerprint();
        if (!fingerprint) return;

        try {
            await apiRequest<FingerprintTrackingResponse>(
                axios.post(
                    route('api.fingerprint'),
                    {
                        fingerprint_id: fingerprint.visitorId,
                        fingerprint_data: fingerprint.components,
                    },
                    {
                        headers: {
                            'X-Fingerprint-ID': fingerprint.visitorId,
                        },
                    },
                ),
            );
        } catch (err: unknown) {
            console.error('Failed to track fingerprint:', err);
        }
    }
}

export default FingerprintService;
