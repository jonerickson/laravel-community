import { Badge } from '@/components/ui/badge';
import { ucFirst } from '@/lib/utils';

const statusMap: Record<App.Enums.OrderStatus, string> = {
    canceled: 'bg-red-500 text-white dark:bg-red-600',
    expired: 'bg-red-500 text-white dark:bg-red-600',
    processing: 'bg-blue-500 text-white dark:bg-blue-600',
    requires_action: 'bg-yellow-500 text-white dark:bg-yellow-600',
    requires_capture: 'bg-orange-500 text-white dark:bg-orange-600',
    requires_confirmation: 'bg-purple-500 text-white dark:bg-purple-600',
    requires_payment_method: 'bg-pink-500 text-white dark:bg-pink-600',
    succeeded: 'bg-green-500 text-white dark:bg-green-600',
    pending: 'bg-yellow-500 text-white dark:bg-yellow-600',
    refunded: 'bg-blue-500 text-white dark:bg-blue-600',
};

export default function OrderStatus({ status }: { status: App.Enums.OrderStatus }) {
    return (
        <Badge variant="secondary" className={statusMap[status] ?? ''}>
            {ucFirst(status.replace('_', ' '))}
        </Badge>
    );
}
