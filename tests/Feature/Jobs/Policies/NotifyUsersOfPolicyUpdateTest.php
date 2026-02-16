<?php

declare(strict_types=1);

use App\Jobs\Policies\NotifyUsersOfPolicyUpdate;
use App\Mail\Policies\PolicyUpdatedMail;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('job queues email to verified users with email', function (): void {
    Mail::fake();

    $policy = Policy::factory()->create(['is_active' => true]);
    $user = User::factory()->create();

    (new NotifyUsersOfPolicyUpdate($policy))->handle();

    Mail::assertQueued(PolicyUpdatedMail::class, function (PolicyUpdatedMail $mail) use ($user): bool {
        return $mail->hasTo($user->email);
    });
});

test('job does not queue email to unverified users', function (): void {
    Mail::fake();

    $policy = Policy::factory()->create(['is_active' => true]);
    User::factory()->unverified()->create();

    (new NotifyUsersOfPolicyUpdate($policy))->handle();

    Mail::assertNothingQueued();
});

test('job does not queue email to users without email', function (): void {
    Mail::fake();

    $policy = Policy::factory()->create(['is_active' => true]);
    User::factory()->create(['email' => null]);

    (new NotifyUsersOfPolicyUpdate($policy))->handle();

    Mail::assertNothingQueued();
});

test('job queues email for each eligible user', function (): void {
    Mail::fake();

    $policy = Policy::factory()->create(['is_active' => true]);
    User::factory()->count(3)->create();
    User::factory()->unverified()->create();
    User::factory()->create(['email' => null]);

    (new NotifyUsersOfPolicyUpdate($policy))->handle();

    Mail::assertQueued(PolicyUpdatedMail::class, 3);
});
