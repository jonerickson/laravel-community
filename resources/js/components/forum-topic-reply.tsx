import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useEffect } from 'react';

interface ForumTopicReplyProps {
    forumSlug: string;
    topicSlug: string;
    onCancel?: () => void;
    onSuccess?: () => void;
    quotedContent?: string;
    quotedAuthor?: string;
}

export default function ForumTopicReply({ forumSlug, topicSlug, onCancel, onSuccess, quotedContent, quotedAuthor }: ForumTopicReplyProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        content: '',
    });

    useEffect(() => {
        if (quotedContent && quotedAuthor) {
            const quotedText = `<blockquote><strong>${quotedAuthor} wrote:</strong><br>${quotedContent}</blockquote>`;
            setData('content', quotedText);
        }
    }, [quotedContent, quotedAuthor, setData]);

    const handleReply = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('forums.posts.store', { forum: forumSlug, topic: topicSlug }), {
            preserveScroll: true,
            onSuccess: () => {
                reset('content');

                if (onSuccess) {
                    onSuccess();
                }
            },
        });
    };

    return (
        <Card data-reply-form>
            <CardHeader>
                <CardTitle>Reply to topic</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleReply} className="space-y-4">
                    <div className="grid gap-2">
                        <RichTextEditor
                            content={data.content}
                            onChange={(content) => setData('content', content)}
                            placeholder="Write your reply..."
                        />
                        <InputError message={errors.content} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            {processing ? 'Posting...' : 'Post reply'}
                        </Button>
                        {onCancel && (
                            <Button type="button" variant="outline" onClick={onCancel} disabled={processing}>
                                Cancel
                            </Button>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
