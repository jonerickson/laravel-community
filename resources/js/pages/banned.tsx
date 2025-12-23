import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangleIcon, ShieldXIcon } from 'lucide-react';
import SharedData = App.Data.SharedData;

interface UserFingerprint {
    id: number;
    fingerprint_id: string;
    ip_address?: string;
    user_agent?: string;
}

interface BannedProps {
    user: App.Data.UserData | null;
    fingerprint?: UserFingerprint | null;
    banReason?: string | null;
    bannedAt?: string | null;
    bannedBy?: App.Data.UserData | null;
}

export default function Banned({ user, fingerprint, banReason, bannedAt, bannedBy }: BannedProps) {
    const { email } = usePage<SharedData>().props;

    const formatDate = (dateString?: string) => {
        if (!dateString) return 'Unknown';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout>
            <Head title="Account suspended" />

            <div className="flex h-full min-h-[60vh] items-center justify-center">
                <Card className="w-full max-w-2xl">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-destructive-foreground">
                            <ShieldXIcon className="size-8 text-destructive" />
                        </div>
                        <CardTitle>Account banned</CardTitle>
                        <CardDescription>This account has been banned from accessing this platform.</CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <div className="rounded-lg border border-destructive/10 bg-destructive-foreground p-4">
                            <div className="flex items-start gap-3">
                                <AlertTriangleIcon className="mt-0.5 size-5 flex-shrink-0 text-destructive" />
                                <div className="space-y-2">
                                    <div className="space-y-1 text-sm text-destructive">
                                        {user && user.id && (
                                            <p>
                                                <strong>User:</strong> {user.name} ({user.email})
                                            </p>
                                        )}
                                        {fingerprint && fingerprint.fingerprint_id && (
                                            <p>
                                                <strong>Device ID:</strong> {fingerprint.fingerprint_id}
                                            </p>
                                        )}
                                        {bannedAt && (
                                            <p>
                                                <strong>Banned:</strong> {formatDate(bannedAt)}
                                            </p>
                                        )}
                                        {bannedBy && bannedBy.id && (
                                            <p>
                                                <strong>Banned by:</strong> {bannedBy.name}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {banReason && (
                            <div className="space-y-2">
                                <h3 className="font-semibold">Reason for ban:</h3>
                                <div className="rounded-lg border border-muted-foreground/10 bg-muted p-4">
                                    <p className="text-sm whitespace-pre-wrap text-muted-foreground">{banReason}</p>
                                </div>
                            </div>
                        )}

                        <div className="space-y-4 border-t pt-6">
                            <h3 className="font-semibold">What happens now?</h3>
                            <div className="space-y-2 text-sm text-muted-foreground">
                                <div className="flex items-start gap-3">
                                    <div className="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-muted-foreground" />
                                    <p>Your access to most platform features has been restricted</p>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-muted-foreground" />
                                    <p>You can still view this page and contact support</p>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-muted-foreground" />
                                    <p>Review our community guidelines and terms of service</p>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col gap-3 pt-4 sm:flex-row">
                            {email && (
                                <Button variant="outline" className="flex-1" onClick={() => window.open(email, '_blank')}>
                                    Contact support
                                </Button>
                            )}
                            <Button variant="outline" className="flex-1" asChild>
                                <Link href={route('policies.index')}>View policies</Link>
                            </Button>
                        </div>

                        <div className="rounded-lg border border-info/10 bg-info-foreground p-4">
                            <div className="text-sm text-info">
                                <p>
                                    <strong>Need help?</strong>
                                </p>
                                <p className="mt-1">
                                    If you believe this suspension was made in error or would like to appeal, please contact our support team with
                                    your account details.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
