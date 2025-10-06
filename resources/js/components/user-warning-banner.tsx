import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { usePage } from '@inertiajs/react';
import { AlertTriangle, ShieldAlert, XCircle } from 'lucide-react';

export function UserWarningBanner() {
    const { auth } = usePage<App.Data.SharedData>().props;

    if (!auth?.user?.warningPoints || auth.user.warningPoints === 0) {
        return null;
    }

    const consequenceType = auth.user.activeConsequenceType;
    const points = auth.user.warningPoints;

    const getVariant = () => {
        if (consequenceType === 'ban') return 'destructive';
        if (consequenceType === 'post_restriction') return 'warning';
        if (consequenceType === 'moderate_content') return 'info';
        return 'default';
    };

    const getIcon = () => {
        if (consequenceType === 'ban') return XCircle;
        if (consequenceType === 'post_restriction') return ShieldAlert;
        return AlertTriangle;
    };

    const getTitle = () => {
        if (consequenceType === 'ban') return 'Account banned';
        if (consequenceType === 'post_restriction') return 'Posting restricted';
        if (consequenceType === 'moderate_content') return 'Content under moderation';
        return 'Warning points active';
    };

    const getDescription = () => {
        if (consequenceType === 'ban') return 'Your account has been banned from accessing the website.';
        if (consequenceType === 'post_restriction') return 'You cannot create posts or topics due to accumulated warning points.';
        if (consequenceType === 'moderate_content') return 'Your posts require approval before being published due to accumulated warning points.';
        return `You currently have ${points} warning point${points === 1 ? '' : 's'}. Warning points may restrict your ability to use certain features.`;
    };

    const Icon = getIcon();

    return (
        <Alert variant={getVariant()}>
            <Icon className="size-4" />
            <AlertTitle>{getTitle()}</AlertTitle>
            <AlertDescription>{getDescription()}</AlertDescription>
        </Alert>
    );
}
