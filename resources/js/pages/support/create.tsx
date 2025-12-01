import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn, currency } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Check, ChevronsUpDown, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface CreateSupportTicketProps {
    categories: App.Data.SupportTicketCategoryData[];
    orders: App.Data.OrderData[];
}

export default function CreateSupportTicket({ categories, orders }: CreateSupportTicketProps) {
    const [orderSearchOpen, setOrderSearchOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        subject: '',
        description: '',
        support_ticket_category_id: '',
        order_id: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Support',
            href: route('support.index'),
        },
        {
            title: 'Create Ticket',
            href: route('support.create'),
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (data.order_id === 'none') {
            setData('order_id', '');
        }

        post(route('support.store'), {
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create support ticket" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <Heading
                    title="Create support ticket"
                    description="Need help? Create a support ticket and our team will get back to you as soon as possible."
                />

                <Card className="-mt-6">
                    <CardHeader>
                        <CardTitle>Support request details</CardTitle>
                        <CardDescription>
                            Please provide as much detail as possible to help us understand and resolve your issue quickly.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="support_ticket_category_id">Category</Label>
                                <Select
                                    value={data.support_ticket_category_id}
                                    onValueChange={(value) => setData('support_ticket_category_id', value)}
                                    required
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id.toString()}>
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.support_ticket_category_id} />
                                <div className="text-xs text-muted-foreground">Choose the category that best outlines the nature of your ticket.</div>
                            </div>

                            {orders && orders.length > 0 && (
                                <div className="grid gap-2">
                                    <Label htmlFor="order_id">Related Order</Label>
                                    <Popover open={orderSearchOpen} onOpenChange={setOrderSearchOpen}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                variant="outline"
                                                role="combobox"
                                                aria-expanded={orderSearchOpen}
                                                className={cn('w-full justify-between', !data.order_id && 'text-muted-foreground')}
                                            >
                                                {data.order_id && data.order_id !== 'none'
                                                    ? (() => {
                                                          const order = orders.find((o) => o.id.toString() === data.order_id);
                                                          return order
                                                              ? `#${order.referenceId} - ${
                                                                    order.items
                                                                        ?.map((item) => item.product?.name || item.name)
                                                                        .filter(Boolean)
                                                                        .join(', ') || 'N/A'
                                                                } - ${currency(order.amount || 0)}`
                                                              : 'Select an order';
                                                      })()
                                                    : data.order_id === 'none'
                                                      ? 'No related order'
                                                      : 'Select an order if this is related to a purchase'}
                                                <ChevronsUpDown className="ml-2 size-4 shrink-0 opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-full p-0" align="start">
                                            <Command>
                                                <CommandInput placeholder="Search orders..." />
                                                <CommandList>
                                                    <CommandEmpty>No orders found.</CommandEmpty>
                                                    <CommandGroup>
                                                        <CommandItem
                                                            value="none"
                                                            onSelect={() => {
                                                                setData('order_id', 'none');
                                                                setOrderSearchOpen(false);
                                                            }}
                                                        >
                                                            <Check className={cn(data.order_id === 'none' ? 'opacity-100' : 'opacity-0')} />
                                                            No related order
                                                        </CommandItem>
                                                        {orders.map((order) => {
                                                            const orderLabel = `#${order.referenceId} - ${
                                                                order.items
                                                                    ?.map((item) => item.product?.name || item.name)
                                                                    .filter(Boolean)
                                                                    .join(', ') || 'N/A'
                                                            } - ${currency(order.amount || 0)}`;
                                                            return (
                                                                <CommandItem
                                                                    key={order.id}
                                                                    value={orderLabel}
                                                                    onSelect={() => {
                                                                        setData('order_id', order.id.toString());
                                                                        setOrderSearchOpen(false);
                                                                    }}
                                                                >
                                                                    <Check
                                                                        className={cn(
                                                                            data.order_id === order.id.toString() ? 'opacity-100' : 'opacity-0',
                                                                        )}
                                                                    />
                                                                    {orderLabel}
                                                                </CommandItem>
                                                            );
                                                        })}
                                                    </CommandGroup>
                                                </CommandList>
                                            </Command>
                                        </PopoverContent>
                                    </Popover>
                                    <InputError message={errors.order_id} />
                                    <div className="text-xs text-muted-foreground">
                                        (Optional) Attaching a related order helps our support team provide better assistance.
                                    </div>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="subject">Subject</Label>
                                <Input
                                    id="subject"
                                    type="text"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="Brief description of your issue"
                                    required
                                />
                                <InputError message={errors.subject} />
                                <div className="text-xs text-muted-foreground">The main subject of your support ticket.</div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <RichTextEditor
                                    content={data.description}
                                    onChange={(content) => setData('description', content)}
                                    placeholder="Please provide detailed information about your issue, including any steps to reproduce the problem, error messages you've encountered, or relevant context that might help us assist you better."
                                />
                                <InputError message={errors.description} />
                                <div className="text-xs text-muted-foreground">
                                    The more details you provide, the faster we can help resolve your issue.
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    {processing && <LoaderCircle className="animate-spin" />}
                                    {processing ? 'Creating ticket...' : 'Create support ticket'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>What happens next?</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="space-y-1 text-sm text-muted-foreground">
                            <li>• You'll receive a confirmation email with your ticket number</li>
                            <li>• Our support team will review your request and respond within 24 hours</li>
                            <li>• You can track the status of your ticket and add additional information if needed</li>
                            <li>• We'll keep you updated throughout the resolution process</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
