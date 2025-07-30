import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { User } from '@/types';
import { Head } from '@inertiajs/react';
import { AlertTriangleIcon, MailIcon, ShieldXIcon } from 'lucide-react';

interface UserFingerprint {
    id: number;
    fingerprint_id: string;
    is_banned: boolean;
    banned_at?: string;
    ban_reason?: string;
    ip_address?: string;
    user_agent?: string;
}

interface BannedProps {
    user: User;
    fingerprint?: UserFingerprint;
    banReason?: string;
    bannedAt?: string;
    bannedBy?: string;
}

export default function Banned({ user, fingerprint, banReason, bannedAt, bannedBy }: BannedProps) {
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
            <Head title="Account Suspended" />

            <div className="flex min-h-screen items-end justify-center px-4 pb-[33vh]">
                <Card className="w-full max-w-2xl">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-destructive-foreground">
                            <ShieldXIcon className="h-8 w-8 text-destructive" />
                        </div>
                        <CardTitle className="text-2xl font-bold text-destructive">Device Banned</CardTitle>
                        <CardDescription className="text-base">This device has been banned from accessing this platform.</CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <div className="rounded-lg border bg-destructive-foreground p-4">
                            <div className="flex items-start gap-3">
                                <AlertTriangleIcon className="mt-0.5 h-5 w-5 flex-shrink-0 text-destructive" />
                                <div className="space-y-2">
                                    <h3 className="font-semibold text-destructive">Ban Details</h3>
                                    <div className="space-y-1 text-sm text-destructive">
                                        {user && (
                                            <p>
                                                <strong>User:</strong> {user.name} ({user.email})
                                            </p>
                                        )}
                                        {fingerprint && (
                                            <p>
                                                <strong>Device ID:</strong> {fingerprint.fingerprint_id.substring(0, 12)}...
                                            </p>
                                        )}
                                        <p>
                                            <strong>Banned:</strong> {formatDate(bannedAt)}
                                        </p>
                                        {bannedBy && (
                                            <p>
                                                <strong>Banned by:</strong> {bannedBy}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {banReason && (
                            <div className="space-y-2">
                                <h3 className="font-semibold">Reason for Ban</h3>
                                <div className="rounded-lg border border-border bg-muted-foreground p-4">
                                    <p className="text-sm whitespace-pre-wrap text-muted-foreground">{banReason}</p>
                                </div>
                            </div>
                        )}

                        <div className="space-y-4 border-t pt-6">
                            <h3 className="font-semibold">What happens now?</h3>
                            <div className="space-y-3 text-sm text-muted-foreground">
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
                            <Button
                                variant="outline"
                                className="flex-1"
                                onClick={() => window.open('mailto:support@mountaininteractive.com', '_blank')}
                            >
                                <MailIcon className="mr-2 h-4 w-4" />
                                Contact Support
                            </Button>
                            <Button variant="outline" className="flex-1" onClick={() => window.open('/policies', '_blank')}>
                                View Guidelines
                            </Button>
                        </div>

                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div className="text-sm text-blue-700">
                                <p>
                                    <strong>Need Help?</strong>
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
