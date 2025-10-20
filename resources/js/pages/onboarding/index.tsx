import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { CustomField, CustomFieldStep } from '@/components/onboarding/steps/custom-field-step';
import { EmailConfirmationStep } from '@/components/onboarding/steps/email-confirmation-step';
import { DiscordIcon, IntegrationStep, RobloxIcon } from '@/components/onboarding/steps/integration-step';
import { RegistrationStep } from '@/components/onboarding/steps/registration-step';
import { Wizard, WizardContent, WizardStep, WizardSteps } from '@/components/onboarding/wizard';
import OnboardingLayout from '@/layouts/onboarding-layout';

type OnboardingProps = {
    customFields?: CustomField[];
    initialStep?: number;
    isAuthenticated: boolean;
    integrations: {
        discord: {
            enabled: boolean;
            connected: boolean;
        };
        roblox: {
            enabled: boolean;
            connected: boolean;
        };
    };
    emailVerified: boolean;
};

type OnboardingFormData = Record<string, string> & {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

const wizardSteps = [
    { title: 'Create account', description: 'Your basic information' },
    { title: 'Verify email', description: 'Confirm your email address' },
    { title: 'Connect accounts', description: 'Link your integrations' },
    { title: 'Finish setup', description: 'Complete your profile' },
];

export default function Onboarding({ customFields = [], initialStep = 0, isAuthenticated, integrations, emailVerified }: OnboardingProps) {
    const [currentStep, setCurrentStep] = useState(initialStep);

    useEffect(() => {
        setCurrentStep(initialStep);
    }, [initialStep]);

    const {
        data: registerData,
        setData: setRegisterData,
        post: register,
        processing: registerProcessing,
        errors: registerErrors,
    } = useForm<OnboardingFormData>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const {
        data: profileData,
        setData: setProfileData,
        post: complete,
        processing: profileProcessing,
        errors: profileErrors,
    } = useForm({
        ...Object.fromEntries(customFields.map((field) => [field.name, ''])),
    });

    const updateRegisterField = (field: string, value: string) => {
        setRegisterData(field as keyof OnboardingFormData, value);
    };

    const updateProfileField = (field: string, value: string) => {
        setProfileData(field, value);
    };

    const handleRegistration = () => {
        register(route('onboarding.register'));
    };

    const handleResendEmail = () => {
        router.post(route('verification.send'));
    };

    const handleConnectIntegration = (provider: string) => {
        window.location.href = route('oauth.redirect', {
            provider: provider,
            redirect: route('onboarding', {}, false),
        });
    };

    const handleSetStep = (step: number) => {
        router.put(route('onboarding.update', { step: step }), {}, {
            onSuccess: () => router.reload({ only: ['initialStep']} )
        });
    };

    const handleComplete = () => {
        complete(route('onboarding.store'));
    };

    const availableIntegrations = [
        {
            id: 'discord',
            name: 'Discord',
            description: 'Connect your Discord account',
            icon: <DiscordIcon className="size-6 text-[#5865F2]" />,
            connected: integrations.discord.connected,
            enabled: integrations.discord.enabled,
        },
        {
            id: 'roblox',
            name: 'Roblox',
            description: 'Link your Roblox profile',
            icon: <RobloxIcon className="size-6" />,
            connected: integrations.roblox.connected,
            enabled: integrations.roblox.enabled,
        },
    ];

    return (
        <OnboardingLayout title="Welcome" description="Let's get your account set up in just a few steps.">
            <Head title="Onboarding" />

            <Wizard initialStep={currentStep} onStepChange={setCurrentStep}>
                <WizardSteps steps={wizardSteps} />

                <WizardContent>
                    <WizardStep title="Create your account" description="Enter your details to get started.">
                        <RegistrationStep
                            data={registerData}
                            errors={registerErrors}
                            processing={registerProcessing}
                            onChange={updateRegisterField}
                            onNext={handleRegistration}
                        />
                    </WizardStep>

                    <WizardStep title="Verify your email" description="Check your inbox for our verification email.">
                        <EmailConfirmationStep
                            email={registerData.email}
                            verified={emailVerified}
                            processing={registerProcessing}
                            onResend={handleResendEmail}
                            onNext={() => handleSetStep(2)}
                            onPrevious={isAuthenticated ? undefined : () => setCurrentStep(0)}
                        />
                    </WizardStep>

                    <WizardStep title="Connect your accounts" description="Link your social accounts for a better experience.">
                        <IntegrationStep
                            integrations={availableIntegrations}
                            onConnect={handleConnectIntegration}
                            onNext={() => handleSetStep(3)}
                            onSkip={() => handleSetStep(3)}
                        />
                    </WizardStep>

                    <WizardStep title="Complete your profile" description="Tell us a bit more about yourself.">
                        <CustomFieldStep
                            fields={customFields}
                            data={profileData}
                            errors={profileErrors}
                            processing={profileProcessing}
                            onChange={updateProfileField}
                            onNext={handleComplete}
                            onPrevious={() => handleSetStep(2)}
                        />
                    </WizardStep>
                </WizardContent>
            </Wizard>
        </OnboardingLayout>
    );
}
