import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

export type CustomField = {
    name: string;
    label: string;
    type: 'text' | 'email' | 'number' | 'textarea' | 'select';
    placeholder?: string;
    required?: boolean;
    options?: { value: string; label: string }[];
    description?: string;
};

type CustomFieldStepProps = {
    fields: CustomField[];
    data: Record<string, string>;
    errors: Record<string, string>;
    processing: boolean;
    onChange: (field: string, value: string) => void;
    onNext: () => void;
    onPrevious: () => void;
    title?: string;
    description?: string;
};

export function CustomFieldStep({ fields, data, errors, processing, onChange, onNext, onPrevious, title, description }: CustomFieldStepProps) {
    const renderField = (field: CustomField) => {
        const commonProps = {
            id: field.name,
            required: field.required,
            disabled: processing,
            value: data[field.name] || '',
        };

        switch (field.type) {
            case 'textarea':
                return (
                    <Textarea
                        {...commonProps}
                        placeholder={field.placeholder}
                        onChange={(e) => onChange(field.name, e.target.value)}
                        className="min-h-[120px] resize-none bg-background"
                        rows={5}
                    />
                );

            case 'select':
                return (
                    <Select value={data[field.name] || ''} onValueChange={(value) => onChange(field.name, value)} disabled={processing}>
                        <SelectTrigger className="bg-background">
                            <SelectValue placeholder={field.placeholder || 'Select an option'} />
                        </SelectTrigger>
                        <SelectContent>
                            {field.options?.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                );

            default:
                return (
                    <Input
                        {...commonProps}
                        type={field.type}
                        placeholder={field.placeholder}
                        onChange={(e) => onChange(field.name, e.target.value)}
                        className="bg-background"
                    />
                );
        }
    };

    return (
        <div className="flex flex-col gap-6">
            {(title || description) && (
                <div className="flex flex-col gap-2">
                    {title && <h3 className="text-lg font-semibold">{title}</h3>}
                    {description && <p className="text-sm text-muted-foreground">{description}</p>}
                </div>
            )}

            <div className="grid gap-6">
                {fields.map((field) => (
                    <div key={field.name} className="grid gap-2">
                        <Label htmlFor={field.name}>
                            {field.label}
                            {field.required && <span className="text-destructive">*</span>}
                        </Label>
                        {field.description && <p className="text-xs text-muted-foreground">{field.description}</p>}
                        {renderField(field)}
                        <InputError message={errors[field.name]} />
                    </div>
                ))}
            </div>

            <div className="flex flex-col gap-3 sm:flex-row">
                <Button type="button" variant="outline" onClick={onPrevious} className="flex-1">
                    Back
                </Button>
                <Button type="button" onClick={onNext} disabled={processing} className="flex-1">
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Continue
                </Button>
            </div>
        </div>
    );
}
