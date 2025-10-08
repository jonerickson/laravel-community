import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';

interface CreateTopicProps {
    forum: App.Data.ForumData;
}

export default function ForumTopicCreate({ forum }: CreateTopicProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        content: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Forums',
            href: route('forums.index'),
        },
        {
            title: forum.category.name,
            href: route('forums.categories.show', { category: forum.category.slug }),
        },
        {
            title: forum.name,
            href: route('forums.show', { forum: forum.slug }),
        },
        {
            title: 'Create Topic',
            href: route('forums.topics.create', { forum: forum.slug }),
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('forums.topics.store', { forum: forum.slug }));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Forums - ${forum.name} - Create topic`}>
                <meta name="description" content={`Create topic in ${forum.name}`} />
                <meta property="og:title" content={`Forums - ${forum.name} - Create Topic`} />
                <meta property="og:description" content={`Create topic in ${forum.name}`} />
                <meta property="og:type" content="website" />
            </Head>
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <Heading title="Create new topic" description={`Start a new discussion in ${forum.name}`} />

                <Card className="-mt-8">
                    <CardHeader>
                        <CardTitle>Topic details</CardTitle>
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
                                <Input
                                    id="description"
                                    type="text"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Description"
                                    required
                                />
                                <InputError message={errors.description} />
                                <div className="text-xs text-muted-foreground">An optional brief description of the topic.</div>
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

                            <div className="flex items-start gap-4">
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

                <Card>
                    <CardHeader>
                        <CardTitle>Community guidelines</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="space-y-1 text-sm text-muted-foreground">
                            <li>• Be respectful and civil in all discussions</li>
                            <li>• Search for existing topics before creating a new one</li>
                            <li>• Use clear, descriptive titles</li>
                            <li>• Follow all community rules and guidelines</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
