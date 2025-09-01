export function getStatusVariant(status: string) {
    switch (status) {
        case 'new':
            return 'default';
        case 'open':
        case 'in_progress':
            return 'secondary';
        case 'resolved':
            return 'success';
        case 'closed':
            return 'destructive';
        default:
            return 'secondary';
    }
}

export function getPriorityVariant(priority: string) {
    switch (priority) {
        case 'low':
            return 'outline';
        case 'medium':
            return 'secondary';
        case 'high':
            return 'default';
        case 'critical':
            return 'destructive';
        default:
            return 'secondary';
    }
}

export function formatStatus(status: string) {
    switch (status) {
        case 'new':
            return 'New';
        case 'open':
            return 'Open';
        case 'in_progress':
            return 'In Progress';
        case 'resolved':
            return 'Resolved';
        case 'closed':
            return 'Closed';
        default:
            return status.charAt(0).toUpperCase() + status.slice(1);
    }
}

export function formatPriority(priority: string) {
    switch (priority) {
        case 'low':
            return 'Low';
        case 'medium':
            return 'Medium';
        case 'high':
            return 'High';
        case 'critical':
            return 'Critical';
        default:
            return priority.charAt(0).toUpperCase() + priority.slice(1);
    }
}
