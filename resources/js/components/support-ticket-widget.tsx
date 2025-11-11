import { Badge } from '@/components/ui/badge';
import { formatPriority, formatStatus, getPriorityVariant, getStatusVariant } from '@/utils/support-ticket';
import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { Clock, Flag } from 'lucide-react';

interface SupportTicketsWidgetProps {
    tickets?: App.Data.SupportTicketData[];
}

export default function SupportTicketWidget({ tickets = [] }: SupportTicketsWidgetProps) {
    return (
        <div className="space-y-3">
            {tickets.slice(0, 3).map((ticket) => (
                <div className="relative bg-background">
                    <Link
                        key={ticket.id}
                        href={route('support.show', ticket.referenceId)}
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
                </div>
            ))}

            {tickets.length > 3 && (
                <div className="border-t border-sidebar-border/50 pt-2">
                    <Link href={route('support.index')} className="text-xs text-muted-foreground transition-colors hover:text-foreground">
                        +{tickets.length - 3} more tickets
                    </Link>
                </div>
            )}
        </div>
    );
}
