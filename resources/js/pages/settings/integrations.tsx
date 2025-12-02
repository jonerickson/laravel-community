import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useApiRequest } from '@/hooks';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { Link2, LoaderCircle, Plus, RefreshCcw, User } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface Integration {
    provider: string;
    name: string;
    description: string;
    color: string;
}

const availableIntegrations: Integration[] = [
    {
        provider: 'discord',
        name: 'Discord',
        description: 'Connect your Discord account for authentication and community features',
        color: 'bg-[#5865F2]',
    },
    {
        provider: 'roblox',
        name: 'Roblox',
        description: 'Link your Roblox account for game-related features',
        color: 'bg-[#00A2FF]',
    },
];

interface ConnectedAccountsProps {
    connectedAccounts: App.Data.UserIntegrationData[];
}

export default function Integrations({ connectedAccounts }: ConnectedAccountsProps) {
    const [showAddDialog, setShowAddDialog] = useState(false);
    const { execute: syncAccounts, loading } = useApiRequest();
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Settings',
            href: route('settings'),
        },
        {
            title: 'Integrations',
            href: route('settings.integrations.index'),
        },
    ];

    const handleConnectIntegration = (provider: string) => {
        window.location.href = route('oauth.redirect', {
            provider: provider,
            redirect: route('settings.integrations.index', {}, false),
        });
    };

    const handleSyncAccounts = async () => {
        await syncAccounts(
            {
                url: route('api.profile.sync'),
                method: 'POST',
            },
            {
                onSuccess: () => {
                    router.reload({ only: ['connectedAccounts'] });
                },
            },
        );
    };

    const getProviderDisplayName = (provider: string) => {
        const providers: Record<string, string> = {
            discord: 'Discord',
            roblox: 'Roblox',
            github: 'GitHub',
            google: 'Google',
            twitter: 'Twitter',
            facebook: 'Facebook',
        };
        return providers[provider] || provider.charAt(0).toUpperCase() + provider.slice(1);
    };

    const getProviderColor = (provider: string) => {
        const colors: Record<string, string> = {
            discord: 'bg-[#5865F2]',
            roblox: 'bg-[#00A2FF]',
            github: 'bg-[#24292e]',
            google: 'bg-[#4285f4]',
            twitter: 'bg-[#1DA1F2]',
            facebook: 'bg-[#1877F2]',
        };
        return colors[provider] || 'bg-primary';
    };

    const connectedProviders = connectedAccounts.map((account) => account.provider);
    const availableToConnect = availableIntegrations.filter((integration) => !connectedProviders.includes(integration.provider));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Integrations" />
            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <HeadingSmall title="Integrations" description="Connect your accounts for enhanced features and authentication" />
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            {availableToConnect.length > 0 && (
                                <Button variant="outline" onClick={() => setShowAddDialog(true)}>
                                    <Plus />
                                    Add Integration
                                </Button>
                            )}
                            {connectedAccounts.length > 0 && (
                                <Button variant="secondary" onClick={handleSyncAccounts} disabled={loading}>
                                    {loading ? <LoaderCircle className="animate-spin" /> : <RefreshCcw />}
                                    {loading ? 'Syncing...' : 'Sync Accounts'}
                                </Button>
                            )}
                        </div>
                    </div>

                    {connectedAccounts.length > 0 ? (
                        <div className="space-y-4">
                            <div className="space-y-4">
                                {connectedAccounts.map((account) => (
                                    <div key={account.id} className="flex items-center justify-between rounded-lg border p-4">
                                        <div className="flex items-center gap-4">
                                            <div
                                                className={`flex h-10 w-10 items-center justify-center rounded-full text-white ${getProviderColor(account.provider)}`}
                                            >
                                                {account.providerAvatar ? (
                                                    <Avatar className="h-10 w-10">
                                                        <AvatarImage src={account.providerAvatar} alt={account.providerName || undefined} />
                                                        <AvatarFallback>
                                                            {(account.providerName || account.provider).charAt(0).toUpperCase()}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                ) : (
                                                    <User className="h-5 w-5" />
                                                )}
                                            </div>
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <h3 className="font-semibold">{getProviderDisplayName(account.provider)}</h3>
                                                </div>
                                                <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                                                    {account.providerName && <span>{account.providerName}</span>}
                                                    {account.providerEmail && <span>{account.providerEmail}</span>}
                                                    <span>
                                                        Connected {account.createdAt ? format(new Date(account.createdAt), 'PPP') : 'Unknown'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : (
                        <EmptyState
                            icon={<Link2 />}
                            title="No integrations connected"
                            description="Connect your accounts to unlock enhanced features and seamless authentication."
                            buttonText="Add Your First Integration"
                            onButtonClick={() => setShowAddDialog(true)}
                        />
                    )}

                    <Dialog open={showAddDialog} onOpenChange={setShowAddDialog}>
                        <DialogContent className="max-w-md">
                            <DialogHeader>
                                <DialogTitle>Add integration</DialogTitle>
                                <DialogDescription>Choose a service to connect to your account</DialogDescription>
                            </DialogHeader>
                            <div className="space-y-3">
                                {availableToConnect.map((integration) => (
                                    <Button
                                        key={integration.provider}
                                        variant="outline"
                                        className="h-auto w-full justify-start p-4"
                                        onClick={() => {
                                            setShowAddDialog(false);
                                            handleConnectIntegration(integration.provider);
                                        }}
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className={`flex h-8 w-8 items-center justify-center rounded-full text-white ${integration.color}`}>
                                                <User className="h-4 w-4" />
                                            </div>
                                            <div className="flex min-w-0 flex-1 flex-col items-start">
                                                <span className="font-medium">{integration.name}</span>
                                                <span className="text-left text-xs text-wrap break-words text-muted-foreground">
                                                    {integration.description}
                                                </span>
                                            </div>
                                        </div>
                                    </Button>
                                ))}
                                {availableToConnect.length === 0 && (
                                    <p className="py-4 text-center text-sm text-muted-foreground">
                                        All available integrations are already connected.
                                    </p>
                                )}
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
