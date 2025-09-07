import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { Plus } from 'lucide-react';
import { cloneElement, ReactElement, SVGProps } from 'react';

interface EmptyStateProps {
    icon: ReactElement;
    title: string;
    description: string;
    buttonText?: string;
    onButtonClick?: () => void;
}

export function EmptyState({ icon, title, description, buttonText, onButtonClick }: EmptyStateProps) {
    const iconProps = icon.props as { className?: string };

    return (
        <Card>
            <CardContent className="p-12 text-center">
                {cloneElement(icon as ReactElement<SVGProps<SVGSVGElement>>, {
                    className: cn('mx-auto mb-4 size-10 text-muted-foreground/50', iconProps.className),
                })}
                <HeadingSmall title={title} description={description} />
                {onButtonClick && buttonText && (
                    <div className="mt-6">
                        <Button onClick={onButtonClick}>
                            <Plus className="mr-2 h-4 w-4" />
                            {buttonText}
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
