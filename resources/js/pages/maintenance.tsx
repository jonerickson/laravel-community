import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Construction } from 'lucide-react';

export default function Maintenance() {
    return (
        <AppLayout>
            <Head title="Maintenance" />
            <div className="flex items-center justify-center px-4 py-24">
                <Card className="w-full max-w-3xl">
                    <CardContent className="p-8 text-center">
                        <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-info/10">
                            <Construction className="size-10 text-info" />
                        </div>

                        <div className="mb-2">
                            <h1 className="text-3xl font-bold text-foreground">Pardon the dust!</h1>
                        </div>

                        <div className="mb-6 space-y-2">
                            <h2 className="text-lg font-semibold text-foreground">We are down for scheduled maintenance.</h2>
                            <p className="text-sm text-muted-foreground">Please check back at a later time.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
