import { DiscordIcon } from '@/components/onboarding/steps/integration-step';
import { abbreviateNumber, cn } from '@/lib/utils';
import { usePage } from '@inertiajs/react';

interface DiscordOnlineCountProps {
    className?: string;
}

export function DiscordOnlineCount({ className }: DiscordOnlineCountProps) {
    const { discordCount } = usePage<App.Data.SharedData>().props;

    if (!discordCount) {
        return null;
    }

    return (
        <div className={cn('flex items-center gap-1.5 text-sm text-muted-foreground', className)}>
            <div className="relative">
                <DiscordIcon className="size-4 text-[#5865F2]" />
                <span className="absolute -top-1 -right-0.5 size-2 rounded-full bg-success ring-2 ring-background" />
            </div>
            <span className="font-medium tabular-nums">{abbreviateNumber(discordCount)}</span>
            <span className="hidden sm:inline-flex">online</span>
        </div>
    );
}
