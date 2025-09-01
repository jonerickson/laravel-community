import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import usePermissions from '../../../hooks/use-permissions';

interface CreateTopicProps {
    forum: Forum;
}

export default function CreateTopic({ forum }: CreateTopicProps) {
    const { can, cannot } = usePermissions();
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        content: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Forums',
            href: '/forums',
        },
        {
            title: forum.name,
            href: `/forums/${forum.slug}`,
        },
        {
            title: 'Create Topic',
            href: `/forums/${forum.slug}/create`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        if (cannot('create_topics')) {
            return;
        }

        e.preventDefault();
        post(route('forums.topics.store', { forum: forum.slug }), {
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Create Topic - ${forum.name} - Forums`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <Heading title="Create New Topic" description={`Start a new discussion in ${forum.name}`} />

                {can('create_topics') && (
                    <Card className="-mt-8">
                        <CardHeader>
                            <CardTitle>Topic Details</CardTitle>
                            <CardDescription>
                                Provide a clear title and description for your topic to help others understand what you're discussing.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="space-y-2">
                                    <Input
                                        id="title"
                                        type="text"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="Title"
                                        required
                                    />
                                    <InputError message={errors.title} />
                                </div>

                                <div className="space-y-2">
                                    <RichTextEditor
                                        content={data.content}
                                        onChange={(content) => setData('content', content)}
                                        placeholder="Write the first post for your topic. Be detailed and clear to encourage discussion."
                                    />
                                    <InputError message={errors.content} />
                                    <div className="text-xs text-muted-foreground">This will be the first post in your topic thread.</div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Creating topic...' : 'Create topic'}
                                    </Button>
                                    <Button variant="outline" type="button" disabled={processing}>
                                        <Link href={route('forums.show', { forum: forum.slug })}>Cancel</Link>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Community Guidelines</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="space-y-1 text-sm text-muted-foreground">
                            <li>• Be respectful and civil in all discussions</li>
                            <li>• Search for existing topics before creating a new one</li>
                            <li>• Use clear, descriptive titles</li>
                            <li>• Stay on topic and provide constructive feedback</li>
                            <li>• Follow all community rules and guidelines</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
