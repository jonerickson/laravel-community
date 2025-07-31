import type { FingerprintTrackingResponse } from '@/types';
import { apiRequest } from '@/utils/api';
import FingerprintJS from '@fingerprintjs/fingerprintjs';
import axios from 'axios';

class FingerprintService {
    private static instance: FingerprintService;
    private fp: any = null;
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
        } catch (error) {
            console.error('Failed to initialize FingerprintJS:', error);
        }
    }

    public async getFingerprint(): Promise<{ visitorId: string; components: any } | null> {
        if (!this.fp) {
            await this.initialize();
        }

        if (!this.fp) return null;

        try {
            const result = await this.fp.get();
            this.fingerprintId = result.visitorId;
            return result;
        } catch (error) {
            console.error('Failed to get fingerprint:', error);
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
            document.cookie = `fingerprint_id=${fingerprint.visitorId}; path=/; max-age=31536000; samesite=strict`;

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
        } catch (error: any) {
            console.error('Failed to track fingerprint:', error);

            if (error?.response?.status === 403) {
                window.location.href = '/banned';
            }
        }
    }
}

export default FingerprintService;
