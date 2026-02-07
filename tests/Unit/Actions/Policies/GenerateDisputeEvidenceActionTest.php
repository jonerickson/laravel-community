<?php

declare(strict_types=1);

use App\Actions\Policies\GenerateDisputeEvidenceAction;
use App\Models\Order;
use App\Models\PolicyConsent;
use App\Models\User;
use App\Models\UserIntegration;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

describe('GenerateDisputeEvidenceAction', function (): void {
    beforeEach(function (): void {
        Pdf::fake();
    });

    test('generates a PDF for a valid order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 2500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        expect($result)->toBeInstanceOf(PdfBuilder::class)
            ->and($result->viewName)->toBe('pdf.dispute-evidence')
            ->and($result->downloadName)->toContain($order->reference_id);
    });

    test('PDF contains order reference ID in filename', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 1000]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        expect($result->downloadName)->toBe('dispute-evidence-'.$order->reference_id.'-'.now()->format('Y-m-d').'.pdf');
    });

    test('PDF view data contains user email', function (): void {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->userEmail)->toBe('test@example.com');
    });

    test('handles order without Roblox integration gracefully', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->robloxId)->toBeNull()
            ->and($data->robloxName)->toBeNull();
    });

    test('includes Roblox integration when present', function (): void {
        $user = User::factory()->create();
        UserIntegration::factory()->create([
            'user_id' => $user->id,
            'provider' => 'roblox',
            'provider_id' => '12345',
            'provider_name' => 'TestPlayer',
        ]);
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->robloxId)->toBe('12345')
            ->and($data->robloxName)->toBe('TestPlayer');
    });

    test('handles order without policy consents gracefully', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->consents)->toBeArray()->toBeEmpty();
    });

    test('includes policy consents when present', function (): void {
        $user = User::factory()->create();
        PolicyConsent::factory()->count(2)->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->consents)->toHaveCount(2);
    });

    test('can be executed via static execute method', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        expect($result)->toBeInstanceOf(PdfBuilder::class);
    });
});
