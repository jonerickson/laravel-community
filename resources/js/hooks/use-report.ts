import { useApiRequest } from '@/hooks/use-api-request';

interface ReportData {
    reportable_type: string;
    reportable_id: number;
    reason: string;
    additional_info?: string | null;
}

export function useReport() {
    const { loading, execute } = useApiRequest();

    const submitReport = async (data: ReportData) => {
        await execute({
            url: route('api.reports.store'),
            method: 'POST',
            data,
        });
    };

    return {
        loading,
        submitReport,
    };
}
