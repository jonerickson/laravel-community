import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import AppLayout from '@/layouts/app-layout';
import { buildForumBreadcrumbs } from '@/utils/breadcrumbs';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { route } from 'ziggy-js';

interface CreateTopicProps {
    forum: App.Data.ForumData;
}

export default function ForumTopicCreate({ forum }: CreateTopicProps) {
    const { siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        content: '',
    });

    const breadcrumbs = [
        ...buildForumBreadcrumbs(forum),
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
            <Head title={`Create topic - ${forum.name} - Forums`}>
                <meta name="description" content={`Create topic in ${forum.name}`} />
                <meta property="og:title" content={`Create Topic - ${forum.name} - Forums - ${siteName}`} />
                <meta property="og:description" content={`Create topic in ${forum.name}`} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <Heading title="Create new topic" description={`Start a new discussion in ${forum.name}`} />

                <Card className="-mt-6">
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
                                    {processing && <LoaderCircle className="animate-spin" />}
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
