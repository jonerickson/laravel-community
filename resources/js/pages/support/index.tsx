import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, SupportTicket } from '@/types';
import { formatPriority, formatStatus, getPriorityVariant, getStatusVariant } from '@/utils/support-ticket';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { Calendar, Clock, Flag, HelpCircle, Plus, Tag, Ticket, User } from 'lucide-react';

interface SupportTicketsIndexProps {
    tickets: SupportTicket[];
    ticketsPagination: PaginatedData;
}

export default function SupportTicketsIndex({ tickets, ticketsPagination }: SupportTicketsIndexProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Support',
            href: route('support.index'),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Support Tickets" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center sm:gap-0">
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary text-white">
                            <HelpCircle className="h-6 w-6" />
                        </div>
                        <div className="-mb-6">
                            <Heading title="Support Tickets" description="View and manage your support tickets" />
                        </div>
                    </div>

                    <div className="flex shrink-0 items-center gap-2">
                        <Button asChild>
                            <Link href={route('support.create')}>
                                <Plus className="size-4" />
                                Create New Ticket
                            </Link>
                        </Button>
                    </div>
                </div>

                {tickets.length === 0 ? (
                    <EmptyState
                        icon={<Ticket className="h-12 w-12" />}
                        title="No support tickets found"
                        description="You haven't created any support tickets yet. Create one to get help from our support team."
                        buttonText="Create Your First Ticket"
                        onButtonClick={() => router.get(route('support.create'))}
                    />
                ) : (
                    <>
                        <div className="grid gap-4">
                            {tickets.map((ticket) => (
                                <Card key={ticket.id} className="transition-shadow hover:shadow-md">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="space-y-1">
                                                <CardTitle className="flex items-center gap-2">
                                                    <Ticket className="size-4" />
                                                    <Link href={route('support.show', ticket.id)} className="hover:underline">
                                                        #{ticket.id} - {ticket.subject}
                                                    </Link>
                                                </CardTitle>
                                                <CardDescription className="line-clamp-2">{ticket.description}</CardDescription>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-2">
                                                <Badge variant={getStatusVariant(ticket.status)}>
                                                    <Clock className="size-3" />
                                                    {formatStatus(ticket.status)}
                                                </Badge>
                                                <Badge variant={getPriorityVariant(ticket.priority)}>
                                                    <Flag className="size-3" />
                                                    {formatPriority(ticket.priority)}
                                                </Badge>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="pt-0">
                                        <div className="grid grid-cols-2 gap-4 text-sm text-muted-foreground lg:grid-cols-4">
                                            <div className="flex items-center gap-2">
                                                <Tag className="size-4" />
                                                <span>{ticket.category?.name}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <User className="size-4" />
                                                <span>{ticket.author?.name}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Calendar className="size-4" />
                                                <span>{format(new Date(ticket.created_at), 'MMM d, yyyy')}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Clock className="size-4" />
                                                <span>Updated {format(new Date(ticket.updated_at), 'MMM d, yyyy')}</span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <Pagination pagination={ticketsPagination} baseUrl={''} entityLabel={'ticket'} />
                    </>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Need Help?</CardTitle>
                        <CardDescription>Here are some resources that might help you find answers quickly.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-2">
                                <h4 className="text-sm font-medium">Before Creating a Ticket</h4>
                                <ul className="space-y-1 text-sm text-muted-foreground">
                                    <li>• Check our FAQ for common questions</li>
                                    <li>• Search existing tickets for similar issues</li>
                                    <li>• Try basic troubleshooting steps</li>
                                </ul>
                            </div>
                            <div className="space-y-2">
                                <h4 className="text-sm font-medium">What to Include</h4>
                                <ul className="space-y-1 text-sm text-muted-foreground">
                                    <li>• Detailed description of the problem</li>
                                    <li>• Steps to reproduce the issue</li>
                                    <li>• Screenshots or error messages</li>
                                    <li>• Your browser and device information</li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
