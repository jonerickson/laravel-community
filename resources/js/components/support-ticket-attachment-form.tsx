import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
// No import needed, using App.Data.SupportTicketData directly
import { useForm } from '@inertiajs/react';
import { LoaderCircle, X } from 'lucide-react';

interface SupportTicketAttachmentFormProps {
    ticket: App.Data.SupportTicketData;
    onCancel?: () => void;
    onSuccess?: () => void;
}

export default function SupportTicketAttachmentForm({ ticket, onCancel, onSuccess }: SupportTicketAttachmentFormProps) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        attachment: File | null;
    }>({
        attachment: null,
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = e.target.files?.[0];
        if (selectedFile) {
            setData('attachment', selectedFile);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('support.attachments.store', ticket.referenceId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onSuccess?.();
            },
        });
    };

    const handleCancel = () => {
        reset();
        onCancel?.();
    };

    const removeFile = () => {
        setData('attachment', null);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">Add attachment</CardTitle>
                <CardDescription>Upload files to help explain your issue. Maximum file size: 10MB.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-4">
                    {!data.attachment ? (
                        <div className="grid gap-2">
                            <Input
                                type="file"
                                onChange={handleFileChange}
                                accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg,.gif,.heif"
                                disabled={processing}
                            />
                            <div className="text-xs text-muted-foreground">Supported formats: PDF, DOC, DOCX, TXT, PNG, JPG, JPEG, GIF, HEIF</div>
                            {errors.attachment && <p className="text-sm text-destructive">{errors.attachment}</p>}
                        </div>
                    ) : (
                        <div className="flex items-center justify-between rounded-md border p-3">
                            <div className="flex items-center gap-2">
                                <div className="text-sm font-medium">{data.attachment.name}</div>
                                <div className="text-xs text-muted-foreground">({Math.round(data.attachment.size / 1024)} KB)</div>
                            </div>
                            <Button type="button" variant="ghost" size="sm" onClick={removeFile} disabled={processing} className="h-auto p-1">
                                <X className="h-4 w-4" />
                            </Button>
                        </div>
                    )}

                    <div className="flex items-center gap-2">
                        <Button type="submit" disabled={processing || !data.attachment}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            {processing ? 'Uploading...' : 'Upload attachment'}
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
