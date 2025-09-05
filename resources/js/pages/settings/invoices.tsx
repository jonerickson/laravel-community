import { type BreadcrumbItem, Invoice, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

import { DataTable } from '@/components/data-table';
import HeadingSmall from '@/components/heading-small';
import InvoiceStatus from '@/components/invoice-status';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { currency, date } from '@/lib/utils';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown } from 'lucide-react';

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
        accessorKey: 'date',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    Date
                    <ArrowUpDown className="ml-2 size-3" />
                </Button>
            );
        },
        cell: ({ row }) => date(row.getValue('date')),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            return <InvoiceStatus status={row.getValue('status')} />;
        },
    },
    {
        accessorKey: 'amount',
        header: ({ column }) => {
            return (
                <div className="text-right">
                    <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                        Amount
                        <ArrowUpDown className="ml-2 size-3" />
                    </Button>
                </div>
            );
        },
        cell: ({ row }) => {
            const amount = currency(row.getValue('amount'));

            return <div className="text-right font-medium">{amount}</div>;
        },
    },
    {
        accessorKey: 'invoice_url',
        header: undefined,
        size: 50,
        cell: ({ row }) => {
            return (
                <div className="text-right">
                    <Button variant="link" asChild>
                        <Link href={row.getValue('invoice_url')}>View</Link>
                    </Button>
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
                    <DataTable columns={columns} data={invoices} />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
