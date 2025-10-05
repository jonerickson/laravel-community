import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { App } from '@/types';
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
        if (points >= 50) return 'destructive';
        if (points >= 25) return 'destructive';
        if (points >= 10) return 'default';
        return 'default';
    };

    const getIcon = () => {
        if (consequenceType === 'ban') return XCircle;
        if (consequenceType === 'post_restriction') return ShieldAlert;
        return AlertTriangle;
    };

    const getTitle = () => {
        if (consequenceType === 'ban') return 'Account Banned';
        if (consequenceType === 'post_restriction') return 'Posting Restricted';
        if (consequenceType === 'moderate_content') return 'Content Under Moderation';
        return 'Warning Points Active';
    };

    const getDescription = () => {
        if (consequenceType === 'ban') return 'Your account has been banned from accessing the website.';
        if (consequenceType === 'post_restriction') return 'You cannot create posts or topics due to accumulated warning points.';
        if (consequenceType === 'moderate_content') return 'Your posts require approval before being published due to accumulated warning points.';
        return `You currently have ${points} warning point${points === 1 ? '' : 's'}. Warning points may restrict your ability to use certain features.`;
    };

    const Icon = getIcon();

    return (
        <Alert variant={getVariant()} className="mb-4">
            <Icon className="h-4 w-4" />
            <AlertTitle>{getTitle()}</AlertTitle>
            <AlertDescription>{getDescription()}</AlertDescription>
        </Alert>
    );
}
