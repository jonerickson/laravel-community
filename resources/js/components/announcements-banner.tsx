import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import type { Announcement } from '@/types';
import { CheckCircle, Info, TriangleAlert, X, XCircle } from 'lucide-react';
import { useState } from 'react';

interface AnnouncementBannerProps {
    announcement: Announcement;
    onDismiss?: (announcementId: number) => void;
}

const typeConfig: Record<App.Enums.AnnouncementType, { icon: React.ElementType; variant: 'default' | 'success' | 'warning' | 'destructive' }> = {
    info: {
        icon: Info,
        variant: 'default' as const,
    },
    success: {
        icon: CheckCircle,
        variant: 'success' as const,
    },
    warning: {
        icon: TriangleAlert,
        variant: 'warning' as const,
    },
    error: {
        icon: XCircle,
        variant: 'destructive' as const,
    },
};

export default function AnnouncementsBanner({ announcement, onDismiss }: AnnouncementBannerProps) {
    const [isDismissed, setIsDismissed] = useState(false);
    const config = typeConfig[announcement.type as App.Enums.AnnouncementType];
    const IconComponent = config.icon;

    if (isDismissed) {
        return null;
    }

    const handleDismiss = () => {
        setIsDismissed(true);
        onDismiss?.(announcement.id);
    };

    return (
        <Alert variant={config.variant}>
            <IconComponent className="size-4" />
            <div className="flex-1">
                <AlertTitle>{announcement.title}</AlertTitle>
                <AlertDescription>
                    <p dangerouslySetInnerHTML={{ __html: announcement.content }} />
                </AlertDescription>
            </div>

            {announcement.is_dismissible && (
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={handleDismiss}
                    className="absolute top-2 right-2 h-6 w-6 p-0 hover:bg-black/10 dark:hover:bg-white/10"
                    aria-label="Dismiss announcement"
                >
                    <X className="size-4" />
                </Button>
            )}
        </Alert>
    );
}
