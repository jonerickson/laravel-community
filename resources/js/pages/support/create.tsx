import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SupportTicketCategory } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Support',
        href: route('support.index'),
    },
    {
        title: 'Create Ticket',
        href: route('support.create'),
    },
];

interface CreateSupportTicketProps {
    categories: SupportTicketCategory[];
}

export default function CreateSupportTicket({ categories }: CreateSupportTicketProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        subject: '',
        description: '',
        support_ticket_category_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('support.store'), {
            onSuccess: () => {
                reset();
                toast.success('Your support ticket has been successfully created. Please check your email for updates.');
            },
            onError: (err) => {
                console.error('Error creating ticket:', err);
                toast.error(err.message || 'Unable to create support ticket. Please try again.');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create support ticket" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <Heading
                    title="Create support ticket"
                    description="Need help? Create a support ticket and our team will get back to you as soon as possible."
                />

                <Card className="-mt-8">
                    <CardHeader>
                        <CardTitle>Support Request Details</CardTitle>
                        <CardDescription>
                            Please provide as much detail as possible to help us understand and resolve your issue quickly.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="support_ticket_category_id">Category</Label>
                                <Select
                                    value={data.support_ticket_category_id}
                                    onValueChange={(value) => setData('support_ticket_category_id', value)}
                                    required
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id.toString()}>
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.support_ticket_category_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="subject">Subject</Label>
                                <Input
                                    id="subject"
                                    type="text"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="Brief description of your issue"
                                    required
                                />
                                <InputError message={errors.subject} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <RichTextEditor
                                    content={data.description}
                                    onChange={(content) => setData('description', content)}
                                    placeholder="Please provide detailed information about your issue, including any steps to reproduce the problem, error messages you've encountered, or relevant context that might help us assist you better."
                                />
                                <InputError message={errors.description} />
                                <div className="text-xs text-muted-foreground">
                                    The more details you provide, the faster we can help resolve your issue.
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating ticket...' : 'Create support ticket'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>What happens next?</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="space-y-2 text-sm text-muted-foreground">
                            <li>• You'll receive a confirmation email with your ticket number</li>
                            <li>• Our support team will review your request and respond within 24 hours</li>
                            <li>• You can track the status of your ticket and add additional information if needed</li>
                            <li>• We'll keep you updated throughout the resolution process</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
