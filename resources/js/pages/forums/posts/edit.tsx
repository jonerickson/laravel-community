import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import AppLayout from '@/layouts/app-layout';
import { buildTopicBreadcrumbs } from '@/utils/breadcrumbs';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { route } from 'ziggy-js';

interface EditPostProps {
    forum: App.Data.ForumData;
    topic: App.Data.TopicData;
    post: App.Data.PostData;
}

export default function ForumPostEdit({ forum, topic, post }: EditPostProps) {
    const { logoUrl, siteName } = usePage<App.Data.SharedData>().props;
    const { data, setData, patch, processing, errors } = useForm({
        content: post.content,
    });

    const breadcrumbs = [
        ...buildTopicBreadcrumbs(forum, topic),
        {
            title: 'Edit Post',
            href: route('forums.posts.update', { forum: forum.slug, topic: topic.slug, post: post.slug }),
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        patch(
            route('forums.posts.update', {
                forum: forum.slug,
                topic: topic.slug,
                post: post.slug,
            }),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit post - ${topic.title} - Forums`}>
                <meta name="description" content={`Edit post in ${topic.title}`} />
                <meta property="og:title" content={`Edit Post - ${topic.title} - Forums - ${siteName}`} />
                <meta property="og:description" content={`Edit post in ${topic.title}`} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <Heading title="Edit post" description={`Editing your post in "${topic.title}"`} />

                <Card className="-mt-6">
                    <CardHeader>
                        <CardTitle>Post content</CardTitle>
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
                                    {processing && <LoaderCircle className="animate-spin" />}
                                    {processing ? 'Saving...' : 'Save changes'}
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

                <Card>
                    <CardHeader>
                        <CardTitle>Editing guidelines</CardTitle>
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
