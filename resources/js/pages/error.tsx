import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { AlertCircle, Home, RefreshCw } from 'lucide-react';

interface ErrorProps {
    message: string;
    status: string;
}

function getErrorDetails(status: string) {
    switch (status) {
        case '404':
            return {
                title: 'Page Not Found',
                description: 'The page you are looking for could not be found.',
                showRefresh: false,
            };
        case '403':
            return {
                title: 'Forbidden',
                description: 'You do not have permission to access this resource.',
                showRefresh: false,
            };
        case '500':
            return {
                title: 'Internal Server Error',
                description: 'Something went wrong on our end. Please try again later.',
                showRefresh: true,
            };
        case '503':
            return {
                title: 'Service Unavailable',
                description: 'The service is temporarily unavailable. Please try again later.',
                showRefresh: true,
            };
        default:
            return {
                title: 'Error',
                description: 'An unexpected error occurred.',
                showRefresh: true,
            };
    }
}

export default function Error({ message = 'Oops, an unknown error occurred.', status = '500' }: ErrorProps) {
    const errorDetails = getErrorDetails(status);

    const handleRefresh = () => {
        window.location.reload();
    };

    const handleGoHome = () => {
        router.visit('/');
    };

    return (
        <div className="flex min-h-screen items-center justify-center px-4">
            <Card className="w-full max-w-3xl">
                <CardContent className="p-8 text-center">
                    <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-destructive/10">
                        <AlertCircle className="size-10 text-destructive" />
                    </div>

                    <div className="mb-2">
                        <h1 className="text-3xl font-bold text-foreground">{status}</h1>
                    </div>

                    <div className="mb-6 space-y-2">
                        <h2 className="text-lg font-semibold text-foreground">{errorDetails.title}</h2>
                        <p className="text-sm text-muted-foreground">{message || errorDetails.description}</p>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <Button onClick={handleGoHome} variant="default">
                            <Home className="mr-2 size-4" />
                            Go Home
                        </Button>
                        {errorDetails.showRefresh && (
                            <Button onClick={handleRefresh} variant="outline">
                                <RefreshCw className="mr-2 size-4" />
                                Try Again
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
