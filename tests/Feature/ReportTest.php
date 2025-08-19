<?php

declare(strict_types=1);

use App\Enums\ReportReason;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->targetUser = User::factory()->create();
    $this->post = Post::factory()->create();
});

test('authenticated user can submit a report', function () {
    $reportData = [
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->targetUser->id,
        'reason' => ReportReason::Spam->value,
        'additional_info' => 'This user is posting spam content.',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/reports', $reportData);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['report_id'],
        ]);

    $this->assertDatabaseHas('reports', [
        'created_by' => $this->user->id,
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->targetUser->id,
        'reason' => ReportReason::Spam->value,
        'additional_info' => 'This user is posting spam content.',
        'status' => 'pending',
    ]);
});

test('user cannot report the same content twice', function () {
    // Create initial report
    Report::factory()->create([
        'created_by' => $this->user->id,
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->targetUser->id,
        'reason' => ReportReason::Spam,
    ]);

    $reportData = [
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->targetUser->id,
        'reason' => ReportReason::Harassment->value,
        'additional_info' => 'Another reason',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/reports', $reportData);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'You have already reported this content.',
        ]);
});

test('unauthenticated user cannot submit a report', function () {
    $reportData = [
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->targetUser->id,
        'reason' => ReportReason::Spam->value,
    ];

    $response = $this->postJson('/api/reports', $reportData);

    $response->assertUnauthorized();
});

test('report requires valid data', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/reports', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reportable_type', 'reportable_id', 'reason']);
});

test('report reason must be valid enum value', function () {
    $reportData = [
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->targetUser->id,
        'reason' => 'invalid_reason',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/reports', $reportData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

test('additional info has character limit', function () {
    $reportData = [
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->targetUser->id,
        'reason' => ReportReason::Other->value,
        'additional_info' => str_repeat('a', 1001), // Exceeds 1000 character limit
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/reports', $reportData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['additional_info']);
});

test('can report different types of content', function () {
    $reportData = [
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->post->id,
        'reason' => ReportReason::InappropriateContent->value,
        'additional_info' => 'This post contains inappropriate content.',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/reports', $reportData);

    $response->assertSuccessful();

    $this->assertDatabaseHas('reports', [
        'created_by' => $this->user->id,
        'reportable_type' => 'App\Models\Post',
        'reportable_id' => $this->post->id,
        'reason' => ReportReason::InappropriateContent->value,
    ]);
});

test('report model relationships work correctly', function () {
    $report = Report::factory()->create([
        'created_by' => $this->user->id,
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $this->targetUser->id,
        'reviewed_by' => $this->user->id,
    ]);

    expect($report->author)->toBeInstanceOf(User::class);
    expect($report->author->id)->toBe($this->user->id);
    expect($report->reportable)->toBeInstanceOf(User::class);
    expect($report->reportable->id)->toBe($this->targetUser->id);
    expect($report->reviewer)->toBeInstanceOf(User::class);
    expect($report->reviewer->id)->toBe($this->user->id);
});

test('report enum provides correct labels and colors', function () {
    expect(ReportReason::Spam->getLabel())->toBe('Spam');
    expect(ReportReason::Harassment->getLabel())->toBe('Harassment');
    expect(ReportReason::Abuse->getColor())->toBe('danger');
    expect(ReportReason::Spam->getColor())->toBe('warning');
});
