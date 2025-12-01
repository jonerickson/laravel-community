import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Shield, UserCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AuthLayout from '@/layouts/auth-layout';

interface OAuthClient {
    id: string;
    name: string;
    redirect: string;
    personal_access_client: boolean;
    password_client: boolean;
    revoked: boolean;
}

interface AuthorizeProps {
    request: {
        client_id: string;
        redirect_uri: string;
        response_type: string;
        scope?: string;
        state?: string;
    };
    authToken: string;
    client: OAuthClient;
    user: App.Data.UserData;
    scopes: Array<{
        id: string;
        description: string;
    }>;
}

export default function OAuthAuthorize({ request, authToken, client, user, scopes }: AuthorizeProps) {
    const { post: approve, processing: submitProcessing } = useForm({
        state: request.state,
        client_id: request.client_id,
        auth_token: authToken,
    });

    const { delete: deny, processing: cancelProcessing } = useForm({
        state: request.state,
        client_id: request.client_id,
        auth_token: authToken,
    });

    const handleApprove: FormEventHandler = (e) => {
        e.preventDefault();

        approve(route('passport.authorizations.approve'));
    };

    const handleDeny: FormEventHandler = (e) => {
        e.preventDefault();

        deny(route('passport.authorizations.deny'));
    };

    return (
        <AuthLayout title="Authorize Application" description={`${client.name} is requesting access to your account`}>
            <Head title={`Authorize ${client.name}`} />

            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                        <Shield className="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle className="text-xl">Authorize Application</CardTitle>
                    <CardDescription>
                        <strong>{client.name}</strong> is requesting access to your account
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-6">
                    <div className="flex items-center gap-3 rounded-lg bg-muted p-3">
                        <UserCheck className="size-5 text-muted-foreground" />
                        <div className="flex-1">
                            <p className="text-sm font-medium">{user.name}</p>
                            <p className="text-xs text-muted-foreground">{user.email}</p>
                        </div>
                    </div>

                    {scopes.length > 0 && (
                        <>
                            <Separator />
                            <div className="space-y-3">
                                <h4 className="text-sm font-medium">This application will be able to:</h4>
                                <ul className="space-y-2">
                                    {scopes.map((scope) => (
                                        <li key={scope.id} className="flex items-start gap-2 text-sm text-muted-foreground">
                                            <div className="mt-1.5 h-1.5 w-1.5 rounded-full bg-muted-foreground/50" />
                                            {scope.description}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <Separator />
                        </>
                    )}

                    <div className="space-y-3">
                        <Button onClick={handleApprove} className="w-full" disabled={submitProcessing}>
                            {submitProcessing && <LoaderCircle className="animate-spin" />}
                            Authorize
                        </Button>

                        <Button onClick={handleDeny} variant="outline" className="w-full" disabled={cancelProcessing}>
                            {cancelProcessing && <LoaderCircle className="animate-spin" />}
                            Cancel
                        </Button>
                    </div>

                    <div className="text-center">
                        <p className="text-xs text-muted-foreground">
                            By authorizing, you allow <strong>{client.name}</strong> to access your account according to their terms of service and
                            privacy policy.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
