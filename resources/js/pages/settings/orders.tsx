import { type BreadcrumbItem, Order, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

import { DataTable } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import OrderStatus from '@/components/order-status';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { currency, date } from '@/lib/utils';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, ExternalLink, FileText } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: route('settings'),
    },
    {
        title: 'Orders',
        href: route('settings.orders'),
    },
];

export const columns: ColumnDef<Order>[] = [
    {
        accessorKey: 'created_at',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    Date
                    <ArrowUpDown className="ml-2 size-3" />
                </Button>
            );
        },
        cell: ({ row }) => date(row.getValue('created_at') as string),
    },
    {
        accessorKey: 'order_number',
        header: 'Order Number',
        cell: ({ row }) => {
            const orderNumber = row.getValue('order_number') as number;
            return <div className="font-mono text-sm">{orderNumber || `N/A`}</div>;
        },
    },
    {
        id: 'products',
        header: 'Products',
        cell: ({ row }) => {
            const order = row.original;
            const productNames =
                order.items
                    ?.map((item) => item.product?.name)
                    .filter(Boolean)
                    .join(', ') || 'N/A';

            return (
                <div className="max-w-[200px] truncate" title={productNames}>
                    {productNames}
                </div>
            );
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            return <OrderStatus status={row.getValue('status')} />;
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
            const amount = row.getValue('amount') as number | null;
            const formattedAmount = amount ? currency((amount / 100).toString()) : 'N/A';

            return <div className="text-right font-medium">{formattedAmount}</div>;
        },
    },
    {
        id: 'actions',
        header: undefined,
        size: 120,
        cell: ({ row }) => {
            const order = row.original;

            return (
                <div className="flex justify-end gap-2">
                    {order.invoice_url && (
                        <Button variant="outline" size="sm" asChild>
                            <a href={order.invoice_url} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="mr-1 size-4" />
                                View
                            </a>
                        </Button>
                    )}
                </div>
            );
        },
    },
];

export default function Orders() {
    const { orders } = usePage<SharedData>().props as unknown as { orders: Order[] };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Order information" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Order information" description="View your current and past order information" />

                    {orders && orders.length > 0 ? (
                        <DataTable columns={columns} data={orders} />
                    ) : (
                        <EmptyState
                            icon={<FileText />}
                            title="No orders found"
                            description="You don't have any orders yet. Orders will appear here when you make purchases or subscriptions."
                        />
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
