import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { PlusIcon } from 'lucide-react';
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
        <Empty className="border border-dashed">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    {cloneElement(icon as ReactElement<SVGProps<SVGSVGElement>>, {
                        className: iconProps.className,
                    })}
                </EmptyMedia>
                <EmptyTitle>{title}</EmptyTitle>
                <EmptyDescription>{description}</EmptyDescription>
            </EmptyHeader>
            {onButtonClick && buttonText && (
                <EmptyContent>
                    <Button onClick={onButtonClick} variant="outline" size="sm">
                        <PlusIcon />
                        {buttonText}
                    </Button>
                </EmptyContent>
            )}
        </Empty>
    );
}
