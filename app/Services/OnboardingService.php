<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Session;

class OnboardingService
{
    private const string SESSION_KEY = 'onboarding';

    public function isInProgress(): bool
    {
        return Session::get(self::SESSION_KEY.'.in_progress', false);
    }

    public function startOnboarding(): void
    {
        Session::put(self::SESSION_KEY, [
            'in_progress' => true,
            'current_step' => 0,
            'completed_steps' => [],
        ]);
    }

    public function getCurrentStep(): int
    {
        return Session::get(self::SESSION_KEY.'.current_step', 0);
    }

    public function setCurrentStep(int $step): void
    {
        Session::put(self::SESSION_KEY.'.current_step', $step);
    }

    public function markStepCompleted(int $step): void
    {
        $completedSteps = $this->getCompletedSteps();

        if (! in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
            Session::put(self::SESSION_KEY.'.completed_steps', $completedSteps);
        }
    }

    public function getCompletedSteps(): array
    {
        return Session::get(self::SESSION_KEY.'.completed_steps', []);
    }

    public function completeOnboarding(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function determineInitialStep(?User $user): int
    {
        if (! $user instanceof User) {
            $this->startOnboarding();

            return 0;
        }

        if ($this->isInProgress()) {
            $currentStep = $this->getCurrentStep();

            if ($currentStep >= 1) {
                return $currentStep;
            }
        }

        $step = 1;

        if ($user->hasVerifiedEmail()) {
            $step = 2;
        }

        $hasIntegrations = $user->integrations()->exists();
        if ($hasIntegrations) {
            $step = 3;
        }

        $this->startOnboarding();
        $this->setCurrentStep($step);

        return $step;
    }

    public function advanceToStep(int $step): void
    {
        $currentStep = $this->getCurrentStep();

        $this->markStepCompleted($currentStep);
        $this->setCurrentStep($step);
    }
}
