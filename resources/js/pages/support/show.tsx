import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { StyledUserName } from '@/components/styled-user-name';
import SupportTicketAttachmentForm from '@/components/support-ticket-attachment-form';
import SupportTicketCommentForm from '@/components/support-ticket-comment-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { formatPriority, formatStatus, getPriorityVariant, getStatusVariant } from '@/utils/support-ticket';
import { Head, router, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { Calendar, CheckCircle, Clock, FileText, Flag, Lock, LockOpen, MessageCircle, Paperclip, Tag, Ticket, Trash2, User } from 'lucide-react';
import { useState } from 'react';

interface SupportTicketShowProps {
    ticket: App.Data.SupportTicketData;
}

export default function SupportTicketShow({ ticket }: SupportTicketShowProps) {
    const [showCommentForm, setShowCommentForm] = useState(false);
    const [showAttachmentForm, setShowAttachmentForm] = useState(false);
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Support',
            href: route('support.index'),
        },
        {
            title: `Ticket #${ticket.id}`,
            href: route('support.show', ticket.referenceId),
        },
    ];

    const updateForm = useForm({
        action: '',
    });

    const deleteAttachmentForm = useForm({});

    const createdAt = ticket.createdAt ? new Date(ticket.createdAt) : new Date();
    const updatedAt = ticket.updatedAt ? new Date(ticket.updatedAt) : new Date();

    const handleCommentSuccess = () => {
        setShowCommentForm(false);
        router.reload({ only: ['ticket'] });
    };

    const handleAttachmentSuccess = () => {
        setShowAttachmentForm(false);
        router.reload({ only: ['ticket'] });
    };

    const handleTicketAction = (action: string) => {
        if (!window.confirm(`Are you sure you want to ${action} this ticket?`)) {
            return;
        }

        updateForm.transform(() => ({
            action: action,
        }));

        updateForm.patch(route('support.update', ticket.referenceId), {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['ticket'] });
            },
        });
    };

    const handleDeleteAttachment = (fileReferenceId: string, fileName: string) => {
        if (!window.confirm(`Are you sure you want to delete "${fileName}"?`)) {
            return;
        }

        deleteAttachmentForm.delete(route('support.attachments.destroy', [ticket.referenceId, fileReferenceId]), {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['ticket'] });
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Support ticket - #${ticket.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center sm:gap-0">
                    <div className="flex items-start gap-4">
                        <div className="flex size-12 items-center justify-center rounded-lg bg-primary text-white">
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

                <div className="grid gap-6 lg:grid-cols-4">
                    <div className="lg:col-span-3">
                        <div className="flex flex-col space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">Support ticket #{ticket.id}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <RichEditorContent content={ticket.description} />
                                </CardContent>
                            </Card>

                            {ticket.comments && ticket.comments.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">Conversation history ({ticket.comments.length})</CardTitle>
                                        <CardDescription>Comment history and updates</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {ticket.comments.map((comment: App.Data.CommentData, index: number) => (
                                            <div key={comment.id}>
                                                <div className="flex items-start gap-3">
                                                    <div className="flex-1 space-y-2">
                                                        <div className="flex items-center gap-2 text-sm">
                                                            {comment.author && <StyledUserName user={comment.author} />}
                                                            <span className="text-muted-foreground">
                                                                {comment.createdAt ? format(new Date(comment.createdAt), 'PPp') : 'N/A'}
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

                            {showAttachmentForm && (
                                <SupportTicketAttachmentForm
                                    ticket={ticket}
                                    onCancel={() => setShowAttachmentForm(false)}
                                    onSuccess={handleAttachmentSuccess}
                                />
                            )}
                        </div>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Details</CardTitle>
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

                                    {ticket.assignedToUser && (
                                        <div className="flex items-center gap-2 text-sm">
                                            <User className="size-4 text-muted-foreground" />
                                            <span className="text-muted-foreground">Assigned to:</span>
                                            <span className="font-medium">{ticket.assignedToUser.name}</span>
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

                                    {ticket.resolvedAt && (
                                        <div className="flex items-center gap-2 text-sm">
                                            <Calendar className="size-4 text-muted-foreground" />
                                            <span className="text-muted-foreground">Resolved:</span>
                                            <span className="font-medium">{format(new Date(ticket.resolvedAt), 'PPP')}</span>
                                        </div>
                                    )}

                                    {ticket.closedAt && (
                                        <div className="flex items-center gap-2 text-sm">
                                            <Calendar className="size-4 text-muted-foreground" />
                                            <span className="text-muted-foreground">Closed:</span>
                                            <span className="font-medium">{format(new Date(ticket.closedAt), 'PPP')}</span>
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
                                            <div key={file.id} className="flex items-center justify-between rounded-lg border p-2">
                                                <div className="flex min-w-0 flex-1 items-center gap-2">
                                                    <FileText className="size-4 shrink-0 text-muted-foreground" />
                                                    <a
                                                        href={file.url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="truncate text-sm font-medium text-primary hover:underline"
                                                        title={file.name}
                                                    >
                                                        {file.name}
                                                    </a>
                                                </div>
                                                {ticket.isActive && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDeleteAttachment(file.referenceId, file.name)}
                                                        className="h-auto shrink-0 p-1 text-destructive hover:text-destructive"
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                )}
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
                                {ticket.isActive ? (
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
                                            onClick={() => setShowAttachmentForm(!showAttachmentForm)}
                                        >
                                            <Paperclip className="size-4" />
                                            {showAttachmentForm ? 'Cancel attachment' : 'Add attachment'}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="w-full justify-start"
                                            onClick={() => handleTicketAction('resolve')}
                                        >
                                            <CheckCircle className="size-4" />
                                            {updateForm.processing ? 'Resolving...' : 'Resolve ticket'}
                                        </Button>
                                    </>
                                ) : ticket.status === 'resolved' ? (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="w-full justify-start"
                                            onClick={() => handleTicketAction('open')}
                                        >
                                            <LockOpen className="size-4" />
                                            {updateForm.processing ? 'Opening...' : 'Re-open ticket'}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="w-full justify-start"
                                            onClick={() => handleTicketAction('close')}
                                        >
                                            <Lock className="size-4" />
                                            {updateForm.processing ? 'Closing...' : 'Close ticket'}
                                        </Button>
                                    </>
                                ) : ticket.status === 'closed' ? (
                                    <p className="text-sm text-muted-foreground">This ticket is closed and cannot be re-opened.</p>
                                ) : null}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
