import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { StyledUserName } from '@/components/styled-user-name';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { stripCharacters } from '@/utils/truncate';
import { Head } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Calendar, User as UserIcon } from 'lucide-react';
import { route } from 'ziggy-js';

interface UserProfilePageProps {
    user: App.Data.UserData;
}

export default function Show({ user }: UserProfilePageProps) {
    const getInitials = useInitials();
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: route('dashboard'),
        },
        {
            title: user.name,
            href: route('users.show', { user: user.referenceId }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${user.name} - Profile`} />

            <div className="mx-auto w-full max-w-4xl space-y-6">
                <Card>
                    <CardHeader>
                        <div className="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                            <Avatar className="h-24 w-24">
                                {user.avatarUrl && <AvatarImage src={user.avatarUrl} alt={user.name} />}
                                <AvatarFallback className="text-2xl">{getInitials(user.name)}</AvatarFallback>
                            </Avatar>

                            <div className="flex-1 space-y-4 text-center sm:text-left">
                                <div>
                                    <h1 className="text-3xl font-bold">
                                        <StyledUserName user={user} size="xl" />
                                    </h1>
                                    {user.groups.length > 0 && (
                                        <div className="mt-2 flex flex-wrap justify-center gap-2 sm:justify-start">
                                            {user.groups.map((group) => (
                                                <Badge
                                                    key={group.id}
                                                    variant="secondary"
                                                    style={{
                                                        backgroundColor: group.color ? `${group.color}20` : undefined,
                                                        borderColor: group.color || undefined,
                                                        color: group.color || undefined,
                                                    }}
                                                    className="border"
                                                >
                                                    {group.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                <div className="grid gap-3 text-sm text-muted-foreground">
                                    <div className="flex items-center justify-center gap-2 sm:justify-start">
                                        <UserIcon className="size-4" />
                                        <span>
                                            Member since {user.createdAt ? formatDistanceToNow(new Date(user.createdAt), { addSuffix: true }) : 'N/A'}
                                        </span>
                                    </div>
                                    {user.createdAt && (
                                        <div className="flex items-center justify-center gap-2 sm:justify-start">
                                            <Calendar className="size-4" />
                                            <span>
                                                Joined{' '}
                                                {new Date(user.createdAt).toLocaleDateString('en-US', {
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric',
                                                })}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {user.signature && stripCharacters(user.signature || '').length > 0 && (
                    <Card>
                        <CardHeader>
                            <div className="-mb-6">
                                <Heading title="Signature" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <RichEditorContent content={user.signature} />
                        </CardContent>
                    </Card>
                )}

                {user.fields && user.fields.length > 0 && (
                    <Card>
                        <CardHeader>
                            <div className="-mb-6">
                                <Heading title="Profile information" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid grid-cols-1 gap-4">
                                {user.fields.map((field) => (
                                    <div key={field.id}>
                                        <dt className="text-sm font-medium text-muted-foreground">{field.label}</dt>
                                        <dd className="mt-1 text-sm">
                                            {field.value || <span className="text-muted-foreground italic">Not specified</span>}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
