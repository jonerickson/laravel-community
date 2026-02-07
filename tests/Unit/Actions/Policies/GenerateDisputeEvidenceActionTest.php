<?php

declare(strict_types=1);

use App\Actions\Policies\GenerateDisputeEvidenceAction;
use App\Enums\HttpMethod;
use App\Enums\HttpStatusCode;
use App\Models\Log;
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

    test('handles order without integrations gracefully', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->integrations)->toBeArray()->toBeEmpty();
    });

    test('includes all integrations when present', function (): void {
        $user = User::factory()->create();
        UserIntegration::factory()->create([
            'user_id' => $user->id,
            'provider' => 'roblox',
            'provider_id' => '12345',
            'provider_name' => 'TestPlayer',
        ]);
        UserIntegration::factory()->create([
            'user_id' => $user->id,
            'provider' => 'discord',
            'provider_id' => '67890',
            'provider_name' => 'TestUser#1234',
        ]);
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->integrations)->toHaveCount(2)
            ->and($data->integrations[0]['provider'])->toBe('roblox')
            ->and($data->integrations[0]['provider_id'])->toBe('12345')
            ->and($data->integrations[0]['provider_name'])->toBe('TestPlayer')
            ->and($data->integrations[1]['provider'])->toBe('discord')
            ->and($data->integrations[1]['provider_id'])->toBe('67890');
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

    test('handles order without access logs gracefully', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->accessLogs)->toBeArray()->toBeEmpty();
    });

    test('includes access logs matching user ID in endpoint', function (): void {
        $user = User::factory()->create();
        Log::create([
            'endpoint' => 'https://api.example.com/users/'.$user->id,
            'method' => HttpMethod::Get,
            'status' => HttpStatusCode::Okay,
        ]);
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->accessLogs)->toHaveCount(1)
            ->and($data->accessLogs[0]['endpoint'])->toContain((string) $user->id);
    });

    test('includes access logs matching integration provider ID in endpoint', function (): void {
        $user = User::factory()->create();
        UserIntegration::factory()->create([
            'user_id' => $user->id,
            'provider' => 'roblox',
            'provider_id' => '98765',
            'provider_name' => 'TestPlayer',
        ]);
        Log::create([
            'endpoint' => 'https://apis.roblox.com/messaging-service/v1/universes/98765',
            'method' => HttpMethod::Post,
            'status' => HttpStatusCode::Okay,
        ]);
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->accessLogs)->toHaveCount(1)
            ->and($data->accessLogs[0]['endpoint'])->toContain('98765');
    });

    test('does not include access logs for unrelated endpoints', function (): void {
        $user = User::factory()->create();
        Log::create([
            'endpoint' => 'https://api.example.com/users/99999',
            'method' => HttpMethod::Get,
            'status' => HttpStatusCode::Okay,
        ]);
        $order = Order::factory()->create(['user_id' => $user->id, 'amount_paid' => 500]);

        $result = GenerateDisputeEvidenceAction::execute($order);

        $data = $result->viewData['data'];
        expect($data->accessLogs)->toBeArray()->toBeEmpty();
    });
});
