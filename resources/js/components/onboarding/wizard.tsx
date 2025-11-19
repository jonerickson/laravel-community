import { Check } from 'lucide-react';
import { createContext, useContext, useState } from 'react';

import Heading from '@/components/heading';
import { cn } from '@/lib/utils';

type WizardContextType = {
    currentStep: number;
    totalSteps: number;
    goToStep: (step: number) => void;
    nextStep: () => void;
    previousStep: () => void;
    isFirstStep: boolean;
    isLastStep: boolean;
};

const WizardContext = createContext<WizardContextType | undefined>(undefined);

export function useWizard() {
    const context = useContext(WizardContext);
    if (!context) {
        throw new Error('useWizard must be used within a Wizard');
    }
    return context;
}

type WizardProps = {
    children: React.ReactNode;
    initialStep?: number;
    onStepChange?: (step: number) => void;
};

export function Wizard({ children, initialStep = 0, onStepChange }: WizardProps) {
    const [currentStep, setCurrentStep] = useState(initialStep);

    const totalSteps = Array.isArray(children) ? children.length : 1;

    const goToStep = (step: number) => {
        if (step >= 0 && step < totalSteps) {
            setCurrentStep(step);
            onStepChange?.(step);
        }
    };

    const nextStep = () => goToStep(currentStep + 1);
    const previousStep = () => goToStep(currentStep - 1);

    const isFirstStep = currentStep === 0;
    const isLastStep = currentStep === totalSteps - 1;

    return (
        <WizardContext.Provider
            value={{
                currentStep,
                totalSteps,
                goToStep,
                nextStep,
                previousStep,
                isFirstStep,
                isLastStep,
            }}
        >
            <div className="flex w-full flex-col gap-8">{children}</div>
        </WizardContext.Provider>
    );
}

type WizardStepsProps = {
    steps: {
        title: string;
        description?: string;
    }[];
};

export function WizardSteps({ steps }: WizardStepsProps) {
    const { currentStep } = useWizard();

    return (
        <nav aria-label="Progress" className="w-full">
            <ol role="list" className="flex items-start justify-between gap-1 sm:gap-2">
                {steps.map((step, index) => {
                    const isComplete = index < currentStep;
                    const isCurrent = index === currentStep;

                    return (
                        <li key={step.title} className="relative flex flex-1 flex-col items-center">
                            <div className="group flex w-full flex-col items-center">
                                <div className="flex items-center justify-center py-2">
                                    <span
                                        className={cn(
                                            'flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-sm transition-all duration-300 sm:size-10',
                                            {
                                                'border-primary bg-primary text-primary-foreground': isComplete,
                                                'border-primary bg-background text-primary ring-4 ring-primary/10': isCurrent,
                                                'border-muted-foreground/25 bg-background text-muted-foreground': !isComplete && !isCurrent,
                                            },
                                        )}
                                    >
                                        {isComplete ? <Check className="size-4 sm:size-5" /> : <span>{index + 1}</span>}
                                    </span>
                                </div>
                                <span className="mt-2 flex min-w-0 flex-col items-center text-center">
                                    <span
                                        className={cn('text-xs font-medium transition-colors sm:text-sm', {
                                            'text-primary': isCurrent,
                                            'text-foreground': isComplete,
                                            'text-muted-foreground': !isComplete && !isCurrent,
                                        })}
                                    >
                                        {step.title}
                                    </span>
                                    {step.description && (
                                        <span
                                            className={cn('hidden text-xs transition-colors md:block', {
                                                'text-muted-foreground': isCurrent || isComplete,
                                                'text-muted-foreground/60': !isComplete && !isCurrent,
                                            })}
                                        >
                                            {step.description}
                                        </span>
                                    )}
                                </span>
                            </div>
                            {index < steps.length - 1 && (
                                <div
                                    aria-hidden="true"
                                    className={cn(
                                        'absolute top-[1.5rem] left-[calc(50%+1.7rem)] h-0.5 w-[calc(100%-3rem)] transition-colors sm:top-[1.7rem] sm:left-[calc(50%+2.2rem)] sm:w-[calc(100%-4rem)]',
                                        {
                                            'bg-primary': isComplete,
                                            'bg-muted': !isComplete,
                                        },
                                    )}
                                />
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}

type WizardContentProps = {
    children: React.ReactNode;
    className?: string;
};

export function WizardContent({ children, className }: WizardContentProps) {
    const { currentStep } = useWizard();
    const steps = Array.isArray(children) ? children : [children];

    return (
        <div className={cn('flex-1', className)}>
            <div className="relative">
                {steps.map((step, index) => (
                    <div
                        key={index}
                        className={cn('transition-opacity duration-300', {
                            'block animate-in fade-in-0 slide-in-from-right-4': index === currentStep,
                            hidden: index !== currentStep,
                        })}
                        role="tabpanel"
                        aria-hidden={index !== currentStep}
                    >
                        {step}
                    </div>
                ))}
            </div>
        </div>
    );
}

type WizardStepProps = {
    children: React.ReactNode;
    title: string;
    description?: string;
};

export function WizardStep({ children, title, description }: WizardStepProps) {
    return (
        <div>
            <Heading title={title} description={description} />
            {children}
        </div>
    );
}
