<?php

declare(strict_types=1);

use App\Mail\Policies\PolicyUpdatedMail;
use App\Models\Policy;

test('email has correct subject', function (): void {
    $policy = Policy::factory()->create([
        'title' => 'Privacy Policy',
        'is_active' => true,
    ]);

    $mail = new PolicyUpdatedMail($policy);

    $mail->assertHasSubject('Policy Updated: Privacy Policy');
});

test('email contains policy title', function (): void {
    $policy = Policy::factory()->create([
        'title' => 'Terms of Service',
        'is_active' => true,
    ]);

    $mail = new PolicyUpdatedMail($policy);

    $mail->assertSeeInHtml('Terms of Service');
});

test('email contains version when present', function (): void {
    $policy = Policy::factory()->create([
        'version' => 'v2.0.0',
        'is_active' => true,
    ]);

    $mail = new PolicyUpdatedMail($policy);

    $mail->assertSeeInHtml('v2.0.0');
});

test('email contains link to policy page', function (): void {
    $policy = Policy::factory()->create([
        'is_active' => true,
    ]);

    $mail = new PolicyUpdatedMail($policy);

    $mail->assertSeeInHtml(route('policies.show', [$policy->category->slug, $policy->slug]));
});
