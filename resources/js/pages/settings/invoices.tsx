import { type BreadcrumbItem, Invoice, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

import { DataTable } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import InvoiceStatus from '@/components/invoice-status';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { currency, date } from '@/lib/utils';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, ExternalLink, FileText } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/settings',
    },
    {
        title: 'Orders',
        href: '/settings/orders',
    },
];

export const columns: ColumnDef<Invoice>[] = [
    {
        accessorKey: 'created',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    Date
                    <ArrowUpDown className="ml-2 size-3" />
                </Button>
            );
        },
        cell: ({ row }) => date(new Date((row.getValue('created') as number) * 1000).toISOString()),
    },
    {
        accessorKey: 'id',
        header: 'Invoice ID',
        cell: ({ row }) => {
            const id = row.getValue('id') as string;
            return <div className="font-mono text-sm">{id.substring(0, 8)}...</div>;
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            return <InvoiceStatus status={row.getValue('status')} />;
        },
    },
    {
        accessorKey: 'amount_due',
        header: ({ column }) => {
            return (
                <div className="text-right">
                    <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                        Amount Due
                        <ArrowUpDown className="ml-2 size-3" />
                    </Button>
                </div>
            );
        },
        cell: ({ row }) => {
            const amount = currency(row.getValue('amount_due')?.toString());

            return <div className="text-right font-medium">{amount}</div>;
        },
    },
    {
        id: 'actions',
        header: undefined,
        size: 120,
        cell: ({ row }) => {
            const invoice = row.original;

            return (
                <div className="flex justify-end gap-2">
                    {invoice.hosted_invoice_url && (
                        <Button variant="outline" size="sm" asChild>
                            <a href={invoice.hosted_invoice_url} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="mr-1 h-4 w-4" />
                                View
                            </a>
                        </Button>
                    )}
                    {invoice.invoice_pdf && (
                        <Button variant="outline" size="sm" asChild>
                            <a href={invoice.invoice_pdf} target="_blank" rel="noopener noreferrer">
                                <FileText className="mr-1 h-4 w-4" />
                                PDF
                            </a>
                        </Button>
                    )}
                </div>
            );
        },
    },
];

export default function Invoices() {
    const { invoices } = usePage<SharedData>().props as unknown as { invoices: Invoice[] };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Order information" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Order information" description="View your current and past order information" />

                    {invoices && invoices.length > 0 ? (
                        <DataTable columns={columns} data={invoices} />
                    ) : (
                        <EmptyState
                            icon={<FileText />}
                            title="No invoices found"
                            description="You don't have any invoices yet. Invoices will appear here when you make purchases or subscriptions."
                        />
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
