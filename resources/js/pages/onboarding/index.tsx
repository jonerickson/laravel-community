import { CustomFieldStep } from '@/components/onboarding/steps/custom-field-step';
import { EmailConfirmationStep } from '@/components/onboarding/steps/email-confirmation-step';
import { DiscordIcon, IntegrationStep, RobloxIcon } from '@/components/onboarding/steps/integration-step';
import { RegistrationStep } from '@/components/onboarding/steps/registration-step';
import { SubscriptionsStep } from '@/components/onboarding/steps/subscriptions-step';
import { Wizard, WizardContent, WizardStep, WizardSteps } from '@/components/onboarding/wizard';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type OnboardingProps = {
    customFields?: App.Data.FieldData[];
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
    subscriptions: App.Data.ProductData[];
    hasSubscription: boolean;
    emailVerified: boolean;
};

type OnboardingFormData = Record<string, string> & {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

const wizardSteps = [
    { title: 'Account', description: 'Create your account' },
    { title: 'Email', description: 'Verify your email' },
    { title: 'Integrations', description: 'Link your accounts' },
    { title: 'Profile', description: 'Complete your profile' },
    { title: 'Subscriptions', description: 'Start a subscription' },
];

export default function Onboarding({
    customFields = [],
    initialStep = 0,
    isAuthenticated,
    integrations,
    subscriptions,
    hasSubscription,
    emailVerified,
}: OnboardingProps) {
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
        post: saveProfile,
        processing: profileProcessing,
        errors: profileErrors,
    } = useForm({
        ...Object.fromEntries(customFields.map((field) => [field.name, ''])),
    });

    const {
        post: subscribe,
        processing: subscribeProcessing,
        transform: transformSubscribe,
    } = useForm({
        price_id: '',
        product_id: '',
        quantity: 1,
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
        router.put(route('onboarding.update'), { step: step });
    };

    const handleProfileFinish = () => {
        saveProfile(route('onboarding.profile'));
    };

    const handleSubscribe = (productId: number, priceId: number) => {
        transformSubscribe(() => ({
            price_id: priceId,
            product_id: productId,
            quantity: 1,
        }));

        subscribe(route('onboarding.subscribe'));
    };

    const handleComplete = () => {
        router.post(route('onboarding.store'));
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

                    <WizardStep title="Setup integrations" description="Link your social accounts for a better experience.">
                        <IntegrationStep
                            integrations={availableIntegrations}
                            onConnect={handleConnectIntegration}
                            onNext={() => handleSetStep(3)}
                            onPrevious={() => handleSetStep(1)}
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
                            onNext={handleProfileFinish}
                            onPrevious={() => handleSetStep(2)}
                        />
                    </WizardStep>

                    <WizardStep title="Start your subscription" description="Start a subscription to get the most out of your experience.">
                        <SubscriptionsStep
                            subscriptions={subscriptions}
                            hasSubscription={hasSubscription}
                            processing={subscribeProcessing}
                            onStartSubscription={handleSubscribe}
                            onNext={handleComplete}
                            onPrevious={() => handleSetStep(3)}
                        />
                    </WizardStep>
                </WizardContent>
            </Wizard>
        </OnboardingLayout>
    );
}
