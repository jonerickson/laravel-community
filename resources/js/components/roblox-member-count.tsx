import { RobloxIcon } from '@/components/onboarding/steps/integration-step';
import { abbreviateNumber, cn } from '@/lib/utils';
import { usePage } from '@inertiajs/react';

interface RobloxMemberCountProps {
    className?: string;
}

export function RobloxMemberCount({ className }: RobloxMemberCountProps) {
    const { robloxCount } = usePage<App.Data.SharedData>().props;

    if (!robloxCount) {
        return null;
    }

    return (
        <div className={cn('flex items-center gap-1.5 text-sm text-muted-foreground', className)}>
            <RobloxIcon className="size-4 text-primary dark:text-white" />
            <span className="font-medium tabular-nums">{abbreviateNumber(robloxCount)}</span>
            <span className="hidden sm:inline-flex">members</span>
        </div>
    );
}
