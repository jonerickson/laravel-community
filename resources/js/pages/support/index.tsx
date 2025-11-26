import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { formatPriority, formatStatus, getPriorityVariant, getStatusVariant } from '@/utils/support-ticket';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { Calendar, Clock, Flag, HelpCircle, Plus, Tag, Ticket, User } from 'lucide-react';
import { route } from 'ziggy-js';

interface SupportTicketsIndexProps {
    tickets: App.Data.PaginatedData<App.Data.SupportTicketData>;
}

export default function SupportTicketsIndex({ tickets }: SupportTicketsIndexProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Support',
            href: route('support.index'),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Support tickets" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center sm:gap-0">
                    <div className="flex items-start gap-4">
                        <div className="flex size-12 items-center justify-center rounded-lg bg-primary text-white">
                            <HelpCircle className="h-6 w-6" />
                        </div>
                        <div className="-mb-6">
                            <Heading title="Support tickets" description="View and manage your support tickets" />
                        </div>
                    </div>

                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:shrink-0 sm:flex-row sm:items-center">
                        <Button variant="outline" asChild>
                            <a href={route('knowledge-base.index')} target="_blank">
                                <HelpCircle className="size-4" />
                                Knowledge Base
                            </a>
                        </Button>
                        <Button asChild>
                            <Link href={route('support.create')}>
                                <Plus className="size-4" />
                                Create New Ticket
                            </Link>
                        </Button>
                    </div>
                </div>

                <Pagination pagination={tickets} baseUrl={route('support.index')} entityLabel="ticket" />

                <div>
                    {tickets.data.length > 0 ? (
                        <div className="grid gap-6">
                            {tickets.data.map((ticket) => (
                                <Card key={ticket.id} className="transition-shadow hover:shadow-md">
                                    <CardHeader>
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex-1 space-y-2">
                                                <div className="flex items-center gap-2">
                                                    <CardTitle className="flex items-center gap-2">
                                                        <Ticket className="size-4" />
                                                        <Link href={route('support.show', ticket.referenceId)} className="hover:underline">
                                                            #{ticket.id} - {ticket.subject}
                                                        </Link>
                                                    </CardTitle>
                                                    {ticket.category?.name && (
                                                        <Badge variant="outline" className="shrink-0">
                                                            <Tag className="size-3" />
                                                            {ticket.category.name}
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                                    <span className="hidden items-center gap-1.5 sm:flex">
                                                        <User className="size-3.5" />
                                                        {ticket.author?.name}
                                                    </span>
                                                    <span className="hidden items-center gap-1.5 sm:flex">
                                                        <Calendar className="size-3.5" />
                                                        {ticket.createdAt ? format(new Date(ticket.createdAt), 'MMM d, yyyy') : 'N/A'}
                                                    </span>
                                                    {ticket.updatedAt && (
                                                        <span className="flex items-center gap-1.5">
                                                            <Clock className="size-3.5" />
                                                            Updated {format(new Date(ticket.updatedAt), 'MMM d, yyyy')}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="hidden shrink-0 items-center gap-2 sm:flex">
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
                                    {ticket.latestComment && (
                                        <CardContent className="pt-0">
                                            <div className="rounded-lg border border-sidebar-border/50 bg-muted/30 p-3">
                                                <div className="mb-1.5 flex items-center gap-2 text-xs text-muted-foreground">
                                                    <User className="size-3" />
                                                    <span className="font-medium">{ticket.latestComment.author?.name}</span>
                                                    <span>•</span>
                                                    <span>
                                                        {ticket.latestComment.createdAt
                                                            ? format(new Date(ticket.latestComment.createdAt), 'MMM d, yyyy')
                                                            : 'N/A'}
                                                    </span>
                                                </div>
                                                <div className="line-clamp-2 text-sm text-muted-foreground">
                                                    <RichEditorContent content={ticket.latestComment.content} />
                                                </div>
                                            </div>
                                        </CardContent>
                                    )}
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <EmptyState
                            icon={<Ticket />}
                            title="No support tickets found"
                            description="You haven't created any support tickets yet. Create one to get help from our support team."
                            buttonText="Create Your First Ticket"
                            onButtonClick={() => router.get(route('support.create'))}
                        />
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Need help?</CardTitle>
                        <CardDescription>Here are some resources that might help you find answers quickly.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-2">
                                <h4 className="text-sm font-medium">Before creating a ticket:</h4>
                                <ul className="space-y-1 text-sm text-muted-foreground">
                                    <li>• Check our forums for common questions</li>
                                    <li>• Search existing tickets for similar issues</li>
                                    <li>• Try basic troubleshooting steps</li>
                                </ul>
                            </div>
                            <div className="space-y-2">
                                <h4 className="text-sm font-medium">What to include:</h4>
                                <ul className="space-y-1 text-sm text-muted-foreground">
                                    <li>• Detailed description of the problem</li>
                                    <li>• Steps to reproduce the issue</li>
                                    <li>• Screenshots or error messages</li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
