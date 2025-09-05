import { useApiRequest } from '@/hooks/use-api-request';
import { toast } from 'sonner';

interface ReportData {
    reportable_type: string;
    reportable_id: number;
    reason: string;
    additional_info?: string | null;
}

export function useReport() {
    const { loading, execute } = useApiRequest();

    const submitReport = async (data: ReportData) => {
        await execute(
            {
                url: '/api/reports',
                method: 'POST',
                data,
            },
            {
                onSuccess: () => toast.success('Report submitted successfully. Thank you for helping keep our community safe.'),
                onError: (err) => {
                    console.error('Error submitting report:', err);
                    toast.error(err.message || 'Unable to submit report. Please try again.');
                },
            },
        );
    };

    return {
        loading,
        submitReport,
    };
}
