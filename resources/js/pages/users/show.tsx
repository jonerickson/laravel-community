import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Calendar, User as UserIcon } from 'lucide-react';

interface UserProfilePageProps {
    user: App.Data.UserData;
}

export default function Show({ user }: UserProfilePageProps) {
    const getInitials = useInitials();

    return (
        <AppLayout>
            <Head title={`${user.name} - Profile`} />

            <div className="mx-auto w-full max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <div className="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                            <Avatar className="h-24 w-24">
                                {user.avatarUrl && <AvatarImage src={user.avatarUrl} alt={user.name} />}
                                <AvatarFallback className="text-2xl">{getInitials(user.name)}</AvatarFallback>
                            </Avatar>

                            <div className="flex-1 space-y-4 text-center sm:text-left">
                                <div>
                                    <h1 className="text-3xl font-bold">{user.name}</h1>
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

                {user.signature && (
                    <Card>
                        <CardHeader>
                            <Heading title="Signature" />
                        </CardHeader>
                        <CardContent>
                            <div className="prose prose-sm dark:prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: user.signature }} />
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <Heading title="Profile information" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-sm text-muted-foreground">No content at this time.</div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
