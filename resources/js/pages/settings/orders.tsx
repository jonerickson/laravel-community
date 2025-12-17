import { type BreadcrumbItem } from '@/types';
import { Deferred, Head } from '@inertiajs/react';

import { DataTable } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import OrderStatus from '@/components/order-status';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { currency, date } from '@/lib/utils';
import { truncate } from '@/utils/truncate';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, Copy, CreditCard, ExternalLink, FileText, Repeat } from 'lucide-react';
import { toast } from 'sonner';
import { route } from 'ziggy-js';

interface OrdersProps {
    orders: App.Data.OrderData[];
}

export default function Orders({ orders }: OrdersProps) {
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
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button
                                onClick={() => copyToClipboard(orderNumber, 'Order number')}
                                className="group flex items-center gap-2 font-mono text-sm hover:text-primary focus:text-primary focus:outline-none"
                                title="Click to copy"
                            >
                                {truncate(orderNumber, 20)}
                                <Copy className="size-3 opacity-0 transition-opacity group-hover:opacity-100" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent>{orderNumber}</TooltipContent>
                    </Tooltip>
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
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button
                                onClick={() => copyToClipboard(invoiceNumber, 'Invoice number')}
                                className="group flex items-center gap-2 font-mono text-sm hover:text-primary focus:text-primary focus:outline-none"
                                title="Click to copy"
                            >
                                {truncate(invoiceNumber, 20)}
                                <Copy className="size-3 opacity-0 transition-opacity group-hover:opacity-100" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent>{invoiceNumber}</TooltipContent>
                    </Tooltip>
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
                        ?.map((item) => item.name)
                        .filter(Boolean)
                        .join(', ') || 'N/A';

                return (
                    <Tooltip>
                        <TooltipTrigger asChild>
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
                        </TooltipTrigger>
                        <TooltipContent>{productNames}</TooltipContent>
                    </Tooltip>
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
                            Total
                            <ArrowUpDown className="ml-2 size-3" />
                        </Button>
                    </div>
                );
            },
            cell: ({ row }) => {
                const order = row.original;
                const status = row.getValue('status') as string;
                const amount = status && status === 'succeeded' ? order.amountPaid : order.amountDue || order.amount;
                const formattedAmount = currency(amount);

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

                    <div className="-mt-4 w-full max-w-full overflow-x-hidden">
                        <Deferred fallback={<DataTable columns={columns} data={[]} loading={true} />} data={['orders']}>
                            {orders && orders.length > 0 ? (
                                <DataTable columns={columns} data={orders} />
                            ) : (
                                <div className="mt-4">
                                    <EmptyState
                                        icon={<FileText />}
                                        title="No orders found"
                                        description="You don't have any orders yet. Orders will appear here when you make purchases or subscriptions."
                                    />
                                </div>
                            )}
                        </Deferred>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
