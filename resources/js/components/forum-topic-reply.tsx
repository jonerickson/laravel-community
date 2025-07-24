import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { useForm } from '@inertiajs/react';

interface ForumTopicReplyProps {
    forumSlug: string;
    topicSlug: string;
    onCancel: () => void;
    onSuccess?: () => void;
}

export default function ForumTopicReply({ forumSlug, topicSlug, onCancel, onSuccess }: ForumTopicReplyProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        content: '',
    });

    const handleReply = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/forums/${forumSlug}/${topicSlug}/reply`, {
            onSuccess: () => {
                reset();
                onSuccess?.();
            },
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Reply to Topic</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleReply} className="space-y-4">
                    <div>
                        <RichTextEditor
                            content={data.content}
                            onChange={(content) => setData('content', content)}
                            placeholder="Write your reply..."
                        />
                        {errors.content && <InputError message={errors.content} />}
                    </div>

                    <div className="flex items-center gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Posting...' : 'Post Reply'}
                        </Button>
                        <Button type="button" variant="outline" onClick={onCancel}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
