import { LoaderCircle } from 'lucide-react';

import { CustomFieldInput } from '@/components/custom-field-input';
import { Button } from '@/components/ui/button';

type CustomFieldStepProps = {
    fields: App.Data.FieldData[];
    data: Record<number, string>;
    errors: Record<string, string>;
    processing: boolean;
    onChange: (fieldId: number, value: string) => void;
    onNext: () => void;
    onPrevious: () => void;
    title?: string;
    description?: string;
};

export function CustomFieldStep({ fields, data, errors, processing, onChange, onNext, onPrevious, title, description }: CustomFieldStepProps) {
    return (
        <div className="flex flex-col gap-6">
            {(title || description) && (
                <div className="flex flex-col gap-2">
                    {title && <h3 className="text-lg font-semibold">{title}</h3>}
                    {description && <p className="text-sm text-muted-foreground">{description}</p>}
                </div>
            )}

            {fields && fields.length > 0 ? (
                <div className="grid gap-6">
                    {fields.map((field) => (
                        <CustomFieldInput
                            key={field.id}
                            field={field}
                            value={data[field.id] || field.value || ''}
                            onChange={(value) => onChange(field.id, value)}
                            error={errors[`fields.${field.id}`]}
                        />
                    ))}
                </div>
            ) : (
                <div className="rounded-lg border bg-card p-6 text-left">
                    <p className="text-sm text-muted-foreground">
                        <strong className="font-medium text-foreground">No profile fields</strong>
                        <br />
                        There are no custom profile fields that need your attention at this time.
                    </p>
                </div>
            )}

            <div className="flex flex-col gap-3 sm:flex-row">
                <Button type="button" variant="outline" onClick={onPrevious} className="flex-1">
                    Back
                </Button>
                <Button type="button" onClick={onNext} disabled={processing} className="flex-1">
                    {processing && <LoaderCircle className="animate-spin" />}
                    Continue
                </Button>
            </div>
        </div>
    );
}
