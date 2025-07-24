import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

interface CreateTopicProps {
    forum: Forum;
}

export default function CreateTopic({ forum }: CreateTopicProps) {
    const { data, setData, post, processing, errors } = useForm({
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
        e.preventDefault();
        post(`/forums/${forum.slug}/topics`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Create Topic - ${forum.name} - Forums`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Link href={`/forums/${forum.slug}`}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back to {forum.name}
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold">Create New Topic</h1>
                        <p className="text-muted-foreground">Start a new discussion in {forum.name}</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Topic Details</CardTitle>
                        <CardDescription>
                            Provide a clear title and description for your topic to help others understand what you're discussing.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="title">Topic Title *</Label>
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Enter a descriptive title for your topic"
                                    required
                                />
                                {errors.title && <div className="text-sm text-red-600">{errors.title}</div>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Topic Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Briefly describe what this topic is about (optional)"
                                    rows={2}
                                />
                                {errors.description && <div className="text-sm text-red-600">{errors.description}</div>}
                                <div className="text-xs text-muted-foreground">This will appear below the title in topic listings</div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="content">Initial Post Content *</Label>
                                <Textarea
                                    id="content"
                                    value={data.content}
                                    onChange={(e) => setData('content', e.target.value)}
                                    placeholder="Write the first post for your topic. Be detailed and clear to encourage discussion."
                                    rows={12}
                                    required
                                />
                                {errors.content && <div className="text-sm text-red-600">{errors.content}</div>}
                                <div className="text-xs text-muted-foreground">This will be the first post in your topic thread</div>
                            </div>

                            <div className="flex items-center gap-4 pt-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating Topic...' : 'Create Topic'}
                                </Button>
                                <Link href={`/forums/${forum.slug}`}>
                                    <Button variant="outline" type="button">
                                        Cancel
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>

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
