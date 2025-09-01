import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum, Post, Topic } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import usePermissions from '../../../hooks/use-permissions';

interface EditPostProps {
    forum: Forum;
    topic: Topic;
    post: Post;
}

export default function Edit({ forum, topic, post }: EditPostProps) {
    const { can, cannot } = usePermissions();
    const { data, setData, patch, processing, errors } = useForm({
        content: post.content,
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
            title: topic.title,
            href: `/forums/${forum.slug}/topics/${topic.slug}`,
        },
        {
            title: 'Edit Post',
            href: `/forums/${forum.slug}/topics/${topic.slug}/posts/${post.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        if (cannot('update_posts')) {
            return;
        }

        e.preventDefault();
        patch(
            route('forums.posts.update', {
                forum: forum.slug,
                topic: topic.slug,
                post: post.id,
            }),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Post - ${topic.title} - Forums`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <Heading title="Edit Post" description={`Editing your post in "${topic.title}"`} />

                {can('update_posts') && (
                    <Card className="-mt-8">
                        <CardHeader>
                            <CardTitle>Post Content</CardTitle>
                            <CardDescription>Make your changes to the post content below.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="space-y-2">
                                    <RichTextEditor
                                        content={data.content}
                                        onChange={(content) => setData('content', content)}
                                        placeholder="Edit your post content..."
                                    />
                                    <InputError message={errors.content} />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Saving...' : 'Save Changes'}
                                    </Button>
                                    <Button variant="outline" type="button" disabled={processing}>
                                        <Link
                                            href={route('forums.topics.show', {
                                                forum: forum.slug,
                                                topic: topic.slug,
                                            })}
                                        >
                                            Cancel
                                        </Link>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Editing Guidelines</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="space-y-1 text-sm text-muted-foreground">
                            <li>• You can only edit posts that you created</li>
                            <li>• Keep your edits relevant to the original topic</li>
                            <li>• Consider adding an edit note if making significant changes</li>
                            <li>• Be respectful and follow community guidelines</li>
                            <li>• Major changes may reset the post's interaction history</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
