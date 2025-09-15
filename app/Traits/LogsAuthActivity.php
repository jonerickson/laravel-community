<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

trait LogsAuthActivity
{
    public function logLogin(?Request $request = null): void
    {
        $properties = [];

        if ($request instanceof Request) {
            $properties['login_method'] = 'standard';
            $properties['remember_me'] = $request->boolean('remember');
        }

        $this->logAuthActivity(
            description: 'User logged in',
            event: Login::class,
            properties: $properties,
            userId: $this->id
        );
    }

    public function logLogout(): void
    {
        $this->logAuthActivity(
            description: 'User logged out',
            event: Logout::class,
            userId: $this->id
        );
    }

    public function logFailedLogin(string $email, ?string $reason = null): void
    {
        $this->logAuthActivity(
            description: 'Failed login attempt',
            event: Failed::class,
            properties: [
                'email' => $email,
                'reason' => $reason ?? 'Invalid credentials',
            ],
            userId: $this->id
        );
    }

    public function logPasswordReset(): void
    {
        $this->logAuthActivity(
            description: 'Password reset requested',
            event: PasswordResetLinkSent::class,
            userId: $this->id
        );
    }

    public function logPasswordResetCompleted(): void
    {
        $this->logAuthActivity(
            description: 'Password reset completed',
            event: PasswordReset::class,
            userId: $this->id
        );
    }

    public function logPasswordChanged(): void
    {
        $this->logAuthActivity(
            description: 'Password changed',
            event: PasswordReset::class,
            userId: $this->id
        );
    }

    public function logEmailVerification(): void
    {
        $this->logAuthActivity(
            description: 'Email verified',
            event: Verified::class,
            userId: $this->id
        );
    }

    public function logEmailVerificationSent(): void
    {
        $this->logAuthActivity(
            description: 'Email verification sent',
            userId: $this->id
        );
    }

    public function logSocialLogin(string $provider): void
    {
        $this->logAuthActivity(
            description: 'Social login',
            properties: [
                'provider' => $provider,
                'login_method' => 'social',
            ],
            userId: $this->id
        );
    }

    public function logSocialConnected(string $provider): void
    {
        $this->logAuthActivity(
            description: 'Social account connected',
            properties: [
                'provider' => $provider,
                'login_method' => 'social',
            ],
            userId: $this->id
        );
    }

    public function logSocialDisconnected(string $provider): void
    {
        $this->logAuthActivity(
            description: 'Social account disconnected',
            properties: [
                'provider' => $provider,
                'login_method' => 'social',
            ],
            userId: $this->id
        );
    }

    public function logAccountLocked(?string $reason = null): void
    {
        $this->logAuthActivity(
            description: 'Account locked',
            properties: [
                'reason' => $reason ?? 'Security policy',
            ],
            userId: $this->id
        );
    }

    public function logAccountUnlocked(): void
    {
        $this->logAuthActivity(
            description: 'Account unlocked',
            userId: $this->id
        );
    }

    public function logTwoFactorEnabled(): void
    {
        $this->logAuthActivity(
            description: 'Two-factor authentication enabled',
            userId: $this->id
        );
    }

    public function logTwoFactorDisabled(): void
    {
        $this->logAuthActivity(
            description: 'Two-factor authentication disabled',
            userId: $this->id
        );
    }

    public function logTwoFactorLogin(): void
    {
        $this->logAuthActivity(
            description: 'Two-factor authentication login',
            userId: $this->id
        );
    }

    public function logSessionExpired(): void
    {
        $this->logAuthActivity(
            description: 'Session expired',
            userId: $this->id
        );
    }

    public function logAccountRegistration(): void
    {
        $this->logAuthActivity(
            description: 'Account registered',
            userId: $this->id
        );
    }

    public function logAccountDeactivation(): void
    {
        $this->logAuthActivity(
            description: 'Account deactivated',
            userId: $this->id
        );
    }

    public function logAccountReactivation(): void
    {
        $this->logAuthActivity(
            description: 'Account reactivated',
            userId: $this->id
        );
    }

    public function logPermissionChanged(string $permission, string $action): void
    {
        $this->logAuthActivity(
            description: 'Permission changed',
            properties: [
                'permission' => $permission,
                'action' => $action,
            ],
            userId: $this->id
        );
    }

    public function logRoleChanged(string $role, string $action): void
    {
        $this->logAuthActivity(
            description: 'Role changed',
            properties: [
                'role' => $role,
                'action' => $action,
            ],
            userId: $this->id
        );
    }

    protected function logAuthActivity(string $description, ?string $event = null, ?array $properties = [], ?int $userId = null): Activity
    {
        $user = $userId !== null && $userId !== 0 ? static::find($userId) : Auth::user();

        $activity = activity('auth')
            ->causedBy($user)
            ->withProperties(array_merge($properties ?? [], [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ]));

        if ($user) {
            $activity->performedOn($user);
        }

        if ($event !== null && $event !== '' && $event !== '0') {
            $activity->event($event);
        }

        return $activity->log($description);
    }
}
