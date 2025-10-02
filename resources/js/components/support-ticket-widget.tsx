import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
// No import needed, using App.Data.SupportTicketData directly
import { formatPriority, formatStatus, getPriorityVariant, getStatusVariant } from '@/utils/support-ticket';
import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { Clock, Flag, Plus, Ticket } from 'lucide-react';

interface SupportTicketsWidgetProps {
    tickets?: App.Data.SupportTicketData[];
    className?: string;
}

export default function SupportTicketWidget({ tickets = [], className }: SupportTicketsWidgetProps) {
    if (tickets.length === 0) {
        return (
            <div className={`relative ${className}`}>
                <div className="relative overflow-hidden rounded-xl border border-sidebar-border/70 py-12 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="relative flex items-center justify-center">
                        <div className="space-y-3 text-center">
                            <HeadingSmall title="Support tickets" description="No active support tickets" />
                            <Button asChild variant="outline" size="sm">
                                <Link href={route('support.create')}>
                                    <Plus className="size-4" />
                                    Create Ticket
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className={`space-y-4 ${className}`}>
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="flex items-center gap-2 text-lg font-semibold">
                        <Ticket className="size-4 text-info" />
                        Recent support tickets
                    </h2>
                    <p className="text-sm text-muted-foreground">Your most recent active tickets</p>
                </div>
                <Link href={route('support.index')} className="text-sm font-medium text-primary hover:underline">
                    View all tickets
                </Link>
            </div>

            <div className="space-y-3">
                {tickets.slice(0, 3).map((ticket) => (
                    <Link
                        key={ticket.id}
                        href={route('support.show', ticket.id)}
                        className="group block cursor-pointer rounded-lg border border-sidebar-border/50 bg-card/30 p-3 transition-all duration-200 hover:border-accent/30 hover:bg-accent/20 hover:shadow-sm"
                    >
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <span className="line-clamp-1 text-sm font-medium">
                                    #{ticket.id} - {ticket.subject}
                                </span>
                            </div>
                            <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                <div className="flex items-center gap-1">
                                    <Clock className="size-3" />
                                    <span>{ticket.createdAt ? format(new Date(ticket.createdAt), 'MMM d') : 'N/A'}</span>
                                </div>
                                <Badge variant={getStatusVariant(ticket.status)} className="px-1.5 py-0.5 text-xs">
                                    {formatStatus(ticket.status)}
                                </Badge>
                                <Badge variant={getPriorityVariant(ticket.priority)} className="px-1.5 py-0.5 text-xs">
                                    <Flag className="size-2" />
                                    {formatPriority(ticket.priority)}
                                </Badge>
                            </div>
                        </div>
                    </Link>
                ))}

                {tickets.length > 3 && (
                    <div className="border-t border-sidebar-border/50 pt-2">
                        <Link href={route('support.index')} className="text-xs text-muted-foreground transition-colors hover:text-foreground">
                            +{tickets.length - 3} more tickets
                        </Link>
                    </div>
                )}
            </div>
        </div>
    );
}
