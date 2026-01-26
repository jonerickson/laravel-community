import { CustomFieldStep } from '@/components/onboarding/steps/custom-field-step';
import { EmailConfirmationStep } from '@/components/onboarding/steps/email-confirmation-step';
import { DiscordIcon, IntegrationStep, RobloxIcon } from '@/components/onboarding/steps/integration-step';
import { RegistrationStep } from '@/components/onboarding/steps/registration-step';
import { SubscriptionsStep } from '@/components/onboarding/steps/subscriptions-step';
import { Wizard, WizardContent, WizardStep, WizardSteps } from '@/components/onboarding/wizard';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
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
    policies: App.Data.PolicyData[];
};

type OnboardingFormData = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    policy: Record<number, boolean>;
} & Record<string, string | Record<number, boolean>>;

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
    policies,
}: OnboardingProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
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
        policy: {},
    });

    const {
        data: profileData,
        setData: setProfileData,
        post: saveProfile,
        processing: profileProcessing,
        errors: profileErrors,
    } = useForm({
        fields: Object.fromEntries(customFields.map((field) => [field.id, field.value || ''])),
    });

    const {
        post: subscribe,
        processing: subscribeProcessing,
        transform: transformSubscribe,
    } = useForm({
        price_id: '',
    });

    const updateRegisterField = (field: string, value: string | Record<number, boolean>) => {
        setRegisterData((prev) => ({ ...prev, [field]: value }));
    };

    const updateProfileField = (fieldId: number, value: string) => {
        setProfileData(`fields.${fieldId}`, value);
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

    const handleSubscribe = (priceId: number) => {
        transformSubscribe(() => ({
            price_id: priceId,
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
            <Head title="Onboarding">
                <meta name="description" content="Let's get your account set up in just a few steps." />
                <meta property="og:title" content={`Onboarding - ${siteName}`} />
                <meta property="og:description" content="Let's get your account set up in just a few steps." />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

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
                            policies={policies}
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
                            data={profileData.fields}
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
