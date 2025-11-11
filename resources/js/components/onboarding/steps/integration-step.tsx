import { Check, Link as LinkIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type Integration = {
    id: string;
    name: string;
    description: string;
    icon: React.ReactNode;
    connected: boolean;
    enabled: boolean;
};

type IntegrationStepProps = {
    integrations: Integration[];
    onConnect: (integrationId: string) => void;
    onNext: () => void;
    onPrevious: () => void;
    onSkip?: () => void;
};

export function IntegrationStep({ integrations, onConnect, onNext, onPrevious, onSkip }: IntegrationStepProps) {
    const hasConnectedIntegration = integrations.some((integration) => integration.connected);

    return (
        <div className="flex flex-col gap-6">
            <div className="rounded-lg border bg-card p-6 text-left">
                <p className="text-sm text-muted-foreground">
                    <strong className="font-medium text-foreground">More account providers coming soon</strong>
                    <br />
                    Connect your accounts to enhance your experience. You can skip this step and connect them later from your settings.
                </p>
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                {integrations.map((integration) => (
                    <Card key={integration.id} className={integration.connected ? 'border-border bg-primary/5' : 'hover:border-border/50'}>
                        <CardHeader>
                            <div className="flex items-start justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10">{integration.icon}</div>
                                    <div>
                                        <CardTitle className="text-base">{integration.name}</CardTitle>
                                        <CardDescription className="text-xs">{integration.description}</CardDescription>
                                    </div>
                                </div>
                                {integration.connected && (
                                    <div className="flex size-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                        <Check className="size-4" />
                                    </div>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Button
                                type="button"
                                variant={integration.connected ? 'outline' : 'default'}
                                size="sm"
                                className="w-full"
                                onClick={() => onConnect(integration.id)}
                                disabled={!integration.enabled || integration.connected}
                            >
                                {integration.connected ? (
                                    <>
                                        <Check className="size-4" />
                                        Connected
                                    </>
                                ) : (
                                    <>
                                        <LinkIcon className="size-4" />
                                        Connect {integration.name}
                                    </>
                                )}
                            </Button>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="flex flex-col gap-3 sm:flex-row">
                <Button type="button" variant="outline" onClick={onPrevious} className="flex-1">
                    Back
                </Button>
                {!hasConnectedIntegration ? (
                    onSkip && (
                        <Button type="button" variant="outline" onClick={onSkip} className="flex-1">
                            Skip for now
                        </Button>
                    )
                ) : (
                    <Button type="button" onClick={onNext} className="flex-1">
                        Continue
                    </Button>
                )}
            </div>
        </div>
    );
}

export function DiscordIcon({ className }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="currentColor">
            <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418z" />
        </svg>
    );
}

export function RobloxIcon({ className }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="currentColor">
            <path d="M18.926 23.998L0 18.892 5.075.002 24 5.108ZM15.348 9.156l-5.528-1.529-1.539 5.54 5.527 1.53Z" />
        </svg>
    );
}
