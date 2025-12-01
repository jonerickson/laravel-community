import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useReport } from '@/hooks/use-report';
import { AlertTriangleIcon, FlagIcon, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

interface ReportDialogProps {
    reportableType: string;
    reportableId: number;
    trigger?: React.ReactNode;
    children?: React.ReactNode;
}

const reportReasons = [
    { value: 'spam', label: 'Spam' },
    { value: 'harassment', label: 'Harassment' },
    { value: 'inappropriate_content', label: 'Inappropriate Content' },
    { value: 'abuse', label: 'Abuse' },
    { value: 'impersonation', label: 'Impersonation' },
    { value: 'false_information', label: 'False Information' },
    { value: 'other', label: 'Other' },
];

export function ReportDialog({ reportableType, reportableId, trigger, children }: ReportDialogProps) {
    const [open, setOpen] = useState(false);
    const [reason, setReason] = useState<string>('');
    const [additionalInfo, setAdditionalInfo] = useState('');
    const { loading: isSubmitting, submitReport } = useReport();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        await submitReport({
            reportable_type: reportableType,
            reportable_id: reportableId,
            reason,
            additional_info: additionalInfo || null,
        });

        setOpen(false);
        setReason('');
        setAdditionalInfo('');
    };

    const defaultTrigger = (
        <Button variant="ghost" size="sm" className="text-muted-foreground hover:text-destructive">
            <FlagIcon className="size-4" />
            Report
        </Button>
    );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger || children || defaultTrigger}</DialogTrigger>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <div className="flex items-center gap-2">
                        <AlertTriangleIcon className="size-5 text-amber-500" />
                        <DialogTitle>Report content</DialogTitle>
                    </div>
                    <DialogDescription>
                        Help us keep our community safe by reporting inappropriate content. All reports are reviewed by our moderation team.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Select value={reason} onValueChange={setReason} required>
                            <SelectTrigger>
                                <SelectValue placeholder="Select a reason" />
                            </SelectTrigger>
                            <SelectContent>
                                {reportReasons.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Textarea
                            id="additional_info"
                            placeholder="Please provide any additional details that may help our moderation team..."
                            value={additionalInfo}
                            onChange={(e) => setAdditionalInfo(e.target.value)}
                            rows={3}
                            maxLength={1000}
                        />
                        <p className="text-xs text-muted-foreground">{additionalInfo.length}/1000 characters</p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={isSubmitting}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="destructive" disabled={isSubmitting || !reason}>
                            {isSubmitting && <LoaderCircle className="animate-spin" />}
                            {isSubmitting ? 'Submitting...' : 'Submit report'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
