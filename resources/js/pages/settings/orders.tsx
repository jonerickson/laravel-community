import { type BreadcrumbItem } from '@/types';
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
import { ArrowUpDown, Copy, CreditCard, ExternalLink, FileText, Repeat } from 'lucide-react';
import { toast } from 'sonner';

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

export default function Orders() {
    const { orders } = usePage<App.Data.SharedData>().props as unknown as { orders: App.Data.OrderData[] };

    const copyToClipboard = async (text: string, label: string) => {
        try {
            await navigator.clipboard.writeText(text);
            toast.success(`${label} copied to clipboard.`);
        } catch {
            toast.error('Unable to copy to clipboard.');
        }
    };

    const columns: ColumnDef<App.Data.OrderData>[] = [
        {
            accessorKey: 'createdAt',
            header: ({ column }) => {
                return (
                    <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                        Date
                        <ArrowUpDown className="ml-2 size-3" />
                    </Button>
                );
            },
            cell: ({ row }) => date(row.getValue('createdAt') as string),
        },
        {
            accessorKey: 'referenceId',
            header: 'Order Number',
            cell: ({ row }) => {
                const orderNumber = row.getValue('referenceId') as string;
                if (!orderNumber || orderNumber === 'N/A') {
                    return <div className="font-mono text-sm">N/A</div>;
                }
                return (
                    <button
                        onClick={() => copyToClipboard(orderNumber, 'Order number')}
                        className="group flex items-center gap-2 font-mono text-sm hover:text-primary focus:text-primary focus:outline-none"
                        title="Click to copy"
                    >
                        {orderNumber}
                        <Copy className="size-3 opacity-0 transition-opacity group-hover:opacity-100" />
                    </button>
                );
            },
        },
        {
            accessorKey: 'invoiceNumber',
            header: 'Invoice Number',
            cell: ({ row }) => {
                const invoiceNumber = row.getValue('invoiceNumber') as string;
                if (!invoiceNumber || invoiceNumber === 'N/A') {
                    return <div className="font-mono text-sm">N/A</div>;
                }
                return (
                    <button
                        onClick={() => copyToClipboard(invoiceNumber, 'Invoice number')}
                        className="group flex items-center gap-2 font-mono text-sm hover:text-primary focus:text-primary focus:outline-none"
                        title="Click to copy"
                    >
                        {invoiceNumber}
                        <Copy className="size-3 opacity-0 transition-opacity group-hover:opacity-100" />
                    </button>
                );
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
                    <div className="flex max-w-[200px] items-center gap-2">
                        <div className="truncate" title={productNames}>
                            {productNames}
                        </div>
                        {order.isRecurring && (
                            <div title="Recurring order" className="flex-shrink-0">
                                <Repeat className="size-3 text-info" />
                            </div>
                        )}
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
                        {order.status === 'pending' && order.checkoutUrl && (
                            <Button variant="ghost" size="sm" asChild>
                                <a href={order.checkoutUrl} target="_blank" rel="noopener noreferrer">
                                    <CreditCard className="mr-1 size-4" />
                                    Checkout
                                </a>
                            </Button>
                        )}
                        {order.invoiceUrl && (
                            <Button variant="ghost" size="sm" asChild>
                                <a href={order.invoiceUrl} target="_blank" rel="noopener noreferrer">
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
