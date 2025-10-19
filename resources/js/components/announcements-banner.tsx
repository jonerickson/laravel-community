import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePage } from '@inertiajs/react';
import { CheckCircle, Info, TriangleAlert, XCircle, XIcon } from 'lucide-react';
import { useState } from 'react';

interface AnnouncementBannerProps {
    announcement: App.Data.AnnouncementData;
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
    const { auth } = usePage<App.Data.SharedData>().props;
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
            <div className="flex items-center justify-between">
                <div>
                    <AlertTitle>{announcement.title}</AlertTitle>
                    <AlertDescription>
                        <p className="-mb-2" dangerouslySetInnerHTML={{ __html: announcement.content }} />
                    </AlertDescription>
                </div>

                {announcement.isDismissible && auth && auth.user && (
                    <Button variant="ghost" size="sm" onClick={handleDismiss} aria-label="Dismiss announcement">
                        <XIcon className="size-4" />
                    </Button>
                )}
            </div>
        </Alert>
    );
}
