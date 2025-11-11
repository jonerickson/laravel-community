import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface CustomFieldInputProps {
    field: App.Data.FieldData;
    value: string;
    onChange: (value: string) => void;
    error?: string;
}

export function CustomFieldInput({ field, value, onChange, error }: CustomFieldInputProps) {
    const renderInput = () => {
        switch (field.type) {
            case 'text':
                return (
                    <Input
                        id={`field-${field.id}`}
                        type="text"
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        required={field.isRequired}
                        placeholder={field.description || undefined}
                    />
                );

            case 'textarea':
                return (
                    <Textarea
                        id={`field-${field.id}`}
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        required={field.isRequired}
                        placeholder={field.description || undefined}
                        rows={4}
                    />
                );

            case 'number':
                return (
                    <Input
                        id={`field-${field.id}`}
                        type="number"
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        required={field.isRequired}
                        placeholder={field.description || undefined}
                    />
                );

            case 'date':
                return (
                    <Input
                        id={`field-${field.id}`}
                        type="date"
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        required={field.isRequired}
                    />
                );

            case 'select':
                return (
                    <Select value={value} onValueChange={onChange} required={field.isRequired}>
                        <SelectTrigger id={`field-${field.id}`}>
                            <SelectValue placeholder={field.description || 'Select an option'} />
                        </SelectTrigger>
                        <SelectContent>
                            {field.options &&
                                field.options.map((option: { value: string; label: string }) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                        </SelectContent>
                    </Select>
                );

            case 'checkbox':
                return (
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id={`field-${field.id}`}
                            checked={value === '1' || value === 'true'}
                            onCheckedChange={(checked) => onChange(checked ? '1' : '0')}
                        />
                        <Label htmlFor={`field-${field.id}`} className="text-sm font-normal">
                            {field.description || field.label}
                        </Label>
                    </div>
                );

            default:
                return (
                    <Input
                        id={`field-${field.id}`}
                        type="text"
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        required={field.isRequired}
                        placeholder={field.description || undefined}
                    />
                );
        }
    };

    return (
        <div className="grid gap-2">
            {field.type !== 'checkbox' && (
                <Label htmlFor={`field-${field.id}`}>
                    {field.label}
                    {field.isRequired && <span className="ml-1 text-destructive">*</span>}
                </Label>
            )}
            {renderInput()}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
