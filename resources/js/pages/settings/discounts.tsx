import { type BreadcrumbItem } from '@/types';
import { Deferred, Head } from '@inertiajs/react';

import { DataTable } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { currency, date } from '@/lib/utils';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, Copy, Gift, Ticket } from 'lucide-react';
import { toast } from 'sonner';
import { route } from 'ziggy-js';

interface DiscountsProps {
    discounts: App.Data.DiscountData[];
}

export default function Discounts({ discounts }: DiscountsProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Settings',
            href: route('settings'),
        },
        {
            title: 'Discounts',
            href: route('settings.discounts'),
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

    const getTypeBadge = (type: string) => {
        const config = {
            gift_card: { label: 'Gift Card', icon: Gift, variant: 'default' as const },
            promo_code: { label: 'Promo Code', icon: Ticket, variant: 'secondary' as const },
            manual: { label: 'Manual', icon: undefined, variant: 'outline' as const },
        };

        const typeConfig = config[type as keyof typeof config] || config.manual;
        const Icon = typeConfig.icon;

        return (
            <Badge variant={typeConfig.variant} className="gap-1.5">
                {Icon && <Icon className="size-3" />}
                {typeConfig.label}
            </Badge>
        );
    };

    const columns: ColumnDef<App.Data.DiscountData>[] = [
        {
            accessorKey: 'code',
            header: 'Code',
            cell: ({ row }) => {
                const code = row.getValue('code') as string;
                return (
                    <button
                        onClick={() => copyToClipboard(code, 'Discount code')}
                        className="group flex items-center gap-2 font-mono text-sm hover:text-primary focus:text-primary focus:outline-none"
                        title="Click to copy"
                    >
                        {code}
                        <Copy className="size-3 opacity-0 transition-opacity group-hover:opacity-100" />
                    </button>
                );
            },
        },
        {
            accessorKey: 'type',
            header: 'Type',
            cell: ({ row }) => getTypeBadge(row.getValue('type')),
        },
        {
            id: 'value',
            header: 'Value',
            cell: ({ row }) => {
                const discount = row.original;
                if (discount.discountType === 'percentage') {
                    return `${discount.value}%`;
                }
                return currency(discount.value, false);
            },
        },
        {
            id: 'balance',
            header: 'Balance',
            cell: ({ row }) => {
                const discount = row.original;
                if (discount.type !== 'gift_card' || discount.currentBalance === null) {
                    return <span className="text-muted-foreground">—</span>;
                }
                return <span className="font-medium">{currency(discount.currentBalance, false)}</span>;
            },
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const discount = row.original;

                if (discount.isExpired) {
                    return <Badge variant="destructive">Expired</Badge>;
                }

                if (!discount.hasBalance) {
                    return <Badge variant="secondary">Depleted</Badge>;
                }

                if (discount.maxUses && discount.timesUsed >= discount.maxUses) {
                    return <Badge variant="secondary">Max Uses Reached</Badge>;
                }

                if (discount.isValid) {
                    return <Badge variant="success">Active</Badge>;
                }

                return <Badge variant="outline">Inactive</Badge>;
            },
        },
        {
            accessorKey: 'timesUsed',
            header: ({ column }) => {
                return (
                    <div className="text-center">
                        <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                            Uses
                            <ArrowUpDown className="ml-2 size-3" />
                        </Button>
                    </div>
                );
            },
            cell: ({ row }) => {
                const discount = row.original;
                const used = discount.timesUsed;
                const max = discount.maxUses;

                return (
                    <div className="text-center font-mono text-sm">
                        {used}
                        {max && ` / ${max}`}
                    </div>
                );
            },
        },
        {
            accessorKey: 'createdAt',
            header: ({ column }) => {
                return (
                    <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                        Created
                        <ArrowUpDown className="ml-2 size-3" />
                    </Button>
                );
            },
            cell: ({ row }) => date(row.getValue('createdAt') as string),
        },
        {
            accessorKey: 'expiresAt',
            header: 'Expires',
            cell: ({ row }) => {
                const expiresAt = row.getValue('expiresAt') as string | null;
                if (!expiresAt) {
                    return <span className="text-muted-foreground">Never</span>;
                }
                return date(expiresAt);
            },
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Discounts" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Discounts" description="View and manage your gift cards and discount codes" />

                    <div className="-mt-4 w-full max-w-full overflow-x-hidden">
                        <Deferred fallback={<DataTable columns={columns} data={[]} loading={true} />} data={['discounts']}>
                            {discounts && discounts.length > 0 ? (
                                <DataTable columns={columns} data={discounts} />
                            ) : (
                                <div className="mt-4">
                                    <EmptyState
                                        icon={<Gift />}
                                        title="No discounts found"
                                        description="You don't have any gift cards or discount codes yet. They will appear here when you receive or purchase them."
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
