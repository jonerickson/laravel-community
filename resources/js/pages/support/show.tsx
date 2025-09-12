import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import SupportTicketCommentForm from '@/components/support-ticket-comment-form';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useApiRequest } from '@/hooks/use-api-request';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Comment, SupportTicket } from '@/types';
import { formatPriority, formatStatus, getPriorityVariant, getStatusVariant } from '@/utils/support-ticket';
import { Head, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { Calendar, CheckCircle, Clock, FileText, Flag, Lock, LockOpen, MessageCircle, Tag, Ticket, User } from 'lucide-react';
import { useState } from 'react';

interface SupportTicketShowProps {
    ticket: SupportTicket;
}

export default function SupportTicketShow({ ticket }: SupportTicketShowProps) {
    const [showCommentForm, setShowCommentForm] = useState(false);
    const { execute: updateTicket, loading: ticketUpdating } = useApiRequest();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Support',
            href: route('support.index'),
        },
        {
            title: `Ticket #${ticket.id}`,
            href: route('support.show', ticket.id),
        },
    ];

    const createdAt = new Date(ticket.created_at);
    const updatedAt = new Date(ticket.updated_at);

    const handleCommentSuccess = () => {
        setShowCommentForm(false);
        router.reload({ only: ['ticket'] });
    };

    const handleTicketAction = async (action: string) => {
        if (!window.confirm(`Are you sure you want to ${action} this ticket?`)) {
            return;
        }

        await updateTicket(
            {
                url: route('api.support'),
                method: 'POST',
                data: {
                    ticket_id: ticket.id,
                    action: action,
                },
            },
            {
                onSuccess: () => {
                    router.reload({ only: ['ticket'] });
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Support Ticket #${ticket.id} - ${ticket.subject}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center sm:gap-0">
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary text-white">
                            <Ticket className="h-6 w-6" />
                        </div>
                        <div className="-mb-6">
                            <Heading title={ticket.subject} description={`Created ${format(createdAt, 'PPP')} at ${format(createdAt, 'p')}`} />
                        </div>
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

                <div className="grid gap-4 lg:grid-cols-4">
                    <div className="lg:col-span-3">
                        <div className="flex flex-col space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">Support Ticket #{ticket.id}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <RichEditorContent content={ticket.description} />
                                </CardContent>
                            </Card>

                            {ticket.comments && ticket.comments.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <MessageCircle className="size-4" />
                                            Comments ({ticket.comments.length})
                                        </CardTitle>
                                        <CardDescription>Conversation history and updates</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {ticket.comments.map((comment: Comment, index: number) => (
                                            <div key={comment.id}>
                                                <div className="flex items-start gap-3">
                                                    <Avatar className="size-8">
                                                        <AvatarImage src={comment.author?.avatar} alt={comment.author?.name} />
                                                        <AvatarFallback>{comment.author?.name?.charAt(0)?.toUpperCase()}</AvatarFallback>
                                                    </Avatar>
                                                    <div className="flex-1 space-y-2">
                                                        <div className="flex items-center gap-2 text-sm">
                                                            <span className="font-medium">{comment.author?.name}</span>
                                                            <span className="text-muted-foreground">
                                                                {format(new Date(comment.created_at), 'PPp')}
                                                            </span>
                                                        </div>
                                                        <RichEditorContent content={comment.content} />
                                                    </div>
                                                </div>
                                                {index < ticket.comments!.length - 1 && <Separator className="my-4" />}
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>
                            )}

                            {showCommentForm && (
                                <SupportTicketCommentForm
                                    ticket={ticket}
                                    onCancel={() => setShowCommentForm(false)}
                                    onSuccess={handleCommentSuccess}
                                />
                            )}
                        </div>
                    </div>

                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Ticket Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2 text-sm">
                                        <Tag className="size-4 text-muted-foreground" />
                                        <span className="text-muted-foreground">Category:</span>
                                        <span className="font-medium">{ticket.category?.name}</span>
                                    </div>

                                    <div className="flex items-center gap-2 text-sm">
                                        <User className="size-4 text-muted-foreground" />
                                        <span className="text-muted-foreground">Created by:</span>
                                        <span className="font-medium">{ticket.author?.name}</span>
                                    </div>

                                    {ticket.assignedTo && (
                                        <div className="flex items-center gap-2 text-sm">
                                            <User className="size-4 text-muted-foreground" />
                                            <span className="text-muted-foreground">Assigned to:</span>
                                            <span className="font-medium">{ticket.assignedTo.name}</span>
                                        </div>
                                    )}

                                    <div className="flex items-center gap-2 text-sm">
                                        <Calendar className="size-4 text-muted-foreground" />
                                        <span className="text-muted-foreground">Created:</span>
                                        <span className="font-medium">{format(createdAt, 'PPP')}</span>
                                    </div>

                                    <div className="flex items-center gap-2 text-sm">
                                        <Calendar className="size-4 text-muted-foreground" />
                                        <span className="text-muted-foreground">Updated:</span>
                                        <span className="font-medium">{format(updatedAt, 'PPP')}</span>
                                    </div>

                                    {ticket.resolved_at && (
                                        <div className="flex items-center gap-2 text-sm">
                                            <Calendar className="size-4 text-muted-foreground" />
                                            <span className="text-muted-foreground">Resolved:</span>
                                            <span className="font-medium">{format(new Date(ticket.resolved_at), 'PPP')}</span>
                                        </div>
                                    )}

                                    {ticket.closed_at && (
                                        <div className="flex items-center gap-2 text-sm">
                                            <Calendar className="size-4 text-muted-foreground" />
                                            <span className="text-muted-foreground">Closed:</span>
                                            <span className="font-medium">{format(new Date(ticket.closed_at), 'PPP')}</span>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {ticket.files && ticket.files.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Attachments</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {ticket.files.map((file) => (
                                            <div key={file.id} className="flex items-center gap-2 text-sm">
                                                <FileText className="size-4 text-muted-foreground" />
                                                <span>{file.name}</span>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {ticket.is_active ? (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="w-full justify-start"
                                            onClick={() => setShowCommentForm(!showCommentForm)}
                                        >
                                            <MessageCircle className="size-4" />
                                            {showCommentForm ? 'Cancel comment' : 'Add comment'}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="w-full justify-start"
                                            onClick={() => handleTicketAction('close')}
                                        >
                                            <Lock className="size-4" />
                                            {ticketUpdating ? 'Closing...' : 'Close ticket'}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="w-full justify-start"
                                            onClick={() => handleTicketAction('resolve')}
                                        >
                                            <CheckCircle className="size-4" />
                                            {ticketUpdating ? 'Resolving...' : 'Resolve ticket'}
                                        </Button>
                                    </>
                                ) : (
                                    <Button variant="outline" size="sm" className="w-full justify-start" onClick={() => handleTicketAction('open')}>
                                        <LockOpen className="size-4" />
                                        {ticketUpdating ? 'Opening...' : 'Re-open ticket'}
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
