import AnnouncementsBanner from '@/components/announcements-banner';
import type { Announcement } from '@/types';

interface AnnouncementsListProps {
    announcements: Announcement[];
    onDismiss?: (announcementId: number) => void;
}

export default function AnnouncementsList({ announcements, onDismiss }: AnnouncementsListProps) {
    if (!announcements || announcements.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3">
            {announcements.map((announcement) => (
                <AnnouncementsBanner key={announcement.id} announcement={announcement} onDismiss={onDismiss} />
            ))}
        </div>
    );
}
