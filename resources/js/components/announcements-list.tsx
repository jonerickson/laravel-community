import AnnouncementsBanner from '@/components/announcements-banner';
import { useMarkAsRead } from '@/hooks';
import { useState } from 'react';

interface AnnouncementsListProps {
    announcements: App.Data.AnnouncementData[];
    onDismiss?: (announcementId: number) => void;
}

export default function AnnouncementsList({ announcements }: AnnouncementsListProps) {
    const [dismissedAnnouncementId, setDismissedAnnouncementId] = useState<number | null>(null);

    useMarkAsRead({
        id: dismissedAnnouncementId || 0,
        type: 'announcement',
        isRead: false,
        enabled: dismissedAnnouncementId !== null,
    });

    if (!announcements || announcements.length === 0) {
        return null;
    }

    const handleAnnouncementDismiss = (announcementId: number) => {
        setDismissedAnnouncementId(announcementId);
    };

    return (
        <div className="space-y-3">
            {announcements.map((announcement) => (
                <AnnouncementsBanner key={announcement.id} announcement={announcement} onDismiss={handleAnnouncementDismiss} />
            ))}
        </div>
    );
}
