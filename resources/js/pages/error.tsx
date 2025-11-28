import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { AlertCircle, AlertTriangle, Construction, Home, LockKeyhole, RefreshCw, SearchX } from 'lucide-react';

interface ErrorProps {
    message: string;
    status: string;
}

function getErrorDetails(status: string) {
    switch (status) {
        case '404':
            return {
                title: 'Page not found',
                description: 'The page you are looking for could not be found.',
                showRefresh: false,
                showHome: true,
                icon: SearchX,
            };
        case '403':
            return {
                title: 'Access denied',
                description: 'You do not have permission to access this resource.',
                showRefresh: false,
                showHome: true,
                icon: LockKeyhole,
            };
        case '500':
            return {
                title: 'Internal server error',
                description: 'Something went wrong on our end. Please try again later.',
                showRefresh: true,
                showHome: true,
                icon: AlertCircle,
            };
        case '503':
            return {
                title: 'Pardon the dust',
                description: 'We are down for scheduled maintenance. Please check back at a later time.',
                showRefresh: false,
                showHome: false,
                icon: Construction,
            };
        default:
            return {
                title: 'Error',
                description: 'An unexpected error occurred.',
                showRefresh: true,
                showHome: true,
                icon: AlertTriangle,
            };
    }
}

export default function Error({ status = '500' }: ErrorProps) {
    const errorDetails = getErrorDetails(status);
    const Icon: LucideIcon = errorDetails.icon;

    const handleRefresh = () => {
        window.location.reload();
    };

    const handleGoHome = () => {
        router.visit('/');
    };

    return (
        <AppLayout>
            <Head title="Error" />
            <div className="flex items-center justify-center px-4 py-24">
                <Card className="w-full max-w-3xl">
                    <CardContent className="p-8 text-center">
                        <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-destructive/10">
                            <Icon className="size-10 text-destructive" />
                        </div>

                        <div className="mb-2">
                            <h1 className="text-3xl font-bold text-foreground">{status}</h1>
                        </div>

                        <div className="mb-6 space-y-2">
                            <h2 className="text-lg font-semibold text-foreground">{errorDetails.title}</h2>
                            <p className="text-sm text-muted-foreground">{errorDetails.description}</p>
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
                            {errorDetails.showHome && (
                                <Button onClick={handleGoHome} variant="default">
                                    <Home />
                                    Go Home
                                </Button>
                            )}
                            {errorDetails.showRefresh && (
                                <Button onClick={handleRefresh} variant="outline">
                                    <RefreshCw />
                                    Try Again
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
