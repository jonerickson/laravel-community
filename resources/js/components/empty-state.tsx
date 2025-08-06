import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Plus } from 'lucide-react';
import { ReactNode } from 'react';

interface EmptyStateProps {
    icon: ReactNode;
    title: string;
    description: string;
    buttonText?: string;
    onButtonClick?: () => void;
}

export function EmptyState({ icon, title, description, buttonText, onButtonClick }: EmptyStateProps) {
    return (
        <Card>
            <CardContent className="p-12 text-center">
                <div className="mx-auto mb-4 h-12 w-12 text-muted-foreground">{icon}</div>
                <HeadingSmall title={title} description={description} />
                {onButtonClick && buttonText && (
                    <div className="mt-6">
                        <Button onClick={onButtonClick} className="cursor-pointer">
                            <Plus className="mr-2 h-4 w-4" />
                            {buttonText}
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
