import { Badge } from '@/components/ui/badge';
import { ucFirst } from '@/lib/utils';
import type { InvoiceStatus } from '@/types';

const statusMap: Record<InvoiceStatus, string> = {
    paid: 'bg-green-500 text-white dark:bg-green-60',
    open: 'bg-blue-500 text-white dark:bg-blue-60',
    draft: 'bg-gray-500 text-white dark:bg-gray-60',
    uncollectible: 'bg-red-500 text-white dark:bg-red-60',
    void: 'bg-red-500 text-white dark:bg-red-60',
};

export default function InvoiceStatus({ status }: { status: InvoiceStatus }) {
    return (
        <Badge variant="secondary" className={statusMap[status] ?? ''}>
            {ucFirst(status)}
        </Badge>
    );
}
