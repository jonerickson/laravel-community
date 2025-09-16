import FingerprintJS from '@fingerprintjs/fingerprintjs';

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
}

export default FingerprintService;
