import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { SupportTicket } from '@/types';
import { useForm } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { toast } from 'sonner';

interface SupportTicketCommentFormProps {
    ticket: SupportTicket;
    onCancel?: () => void;
    onSuccess?: () => void;
}

export default function SupportTicketCommentForm({ ticket, onCancel, onSuccess }: SupportTicketCommentFormProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        content: '',
        parent_id: null as number | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.content.trim()) {
            toast.error('Please enter a comment.');
            return;
        }

        post(route('support.comments.store', ticket.id), {
            onSuccess: () => {
                reset();
                onSuccess?.();
                toast.success('Comment added successfully!');
            },
            onError: () => {
                toast.error('Failed to add comment. Please try again.');
            },
        });
    };

    const handleCancel = () => {
        reset();
        onCancel?.();
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <MessageCircle className="size-4" />
                    Add Comment
                </CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="content">Comment</Label>
                        <Textarea
                            id="content"
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            placeholder="Add your comment or update here..."
                            rows={4}
                            required
                        />
                        {errors.content && <p className="text-sm text-destructive">{errors.content}</p>}
                        <div className="text-xs text-muted-foreground">
                            Your comment will be visible to support staff and will help track the progress of this ticket.
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button type="submit" disabled={processing || !data.content.trim()}>
                            {processing ? 'Adding comment...' : 'Add comment'}
                        </Button>
                        <Button type="button" variant="outline" onClick={handleCancel}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
