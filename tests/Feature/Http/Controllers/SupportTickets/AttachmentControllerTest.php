<?php

declare(strict_types=1);

use App\Enums\FileVisibility;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Attachment Store Tests
|--------------------------------------------------------------------------
*/

it('can upload attachment to own ticket', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->actingAs($user)->post(route('support.attachments.store', $ticket->reference_id), [
        'attachment' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');

    Storage::disk('local')->assertExists('support/'.$file->hashName());

    $this->assertDatabaseHas('files', [
        'resource_type' => SupportTicket::class,
        'resource_id' => $ticket->id,
        'filename' => 'document.pdf',
        'visibility' => FileVisibility::Private,
    ]);
});

it('can upload image attachment', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $file = UploadedFile::fake()->image('screenshot.png', 800, 600);

    $response = $this->actingAs($user)->post(route('support.attachments.store', $ticket->reference_id), [
        'attachment' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');

    Storage::disk('local')->assertExists('support/'.$file->hashName());
});

it('redirects guests to login when uploading attachment', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->post(route('support.attachments.store', $ticket->reference_id), [
        'attachment' => $file,
    ]);

    $response->assertRedirect(route('login'));
});

it('returns 403 when uploading attachment to other user ticket', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $otherUser->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->actingAs($user)->post(route('support.attachments.store', $ticket->reference_id), [
        'attachment' => $file,
    ]);

    $response->assertForbidden();
});

it('returns validation error when attachment is missing', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->post(route('support.attachments.store', $ticket->reference_id), []);

    $response->assertSessionHasErrors('attachment');
});

it('returns validation error when attachment is not a file', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->post(route('support.attachments.store', $ticket->reference_id), [
        'attachment' => 'not-a-file',
    ]);

    $response->assertSessionHasErrors('attachment');
});

it('returns validation error when attachment exceeds max size', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    // 11MB file, max is 10MB (10240KB)
    $file = UploadedFile::fake()->create('large-file.pdf', 11265, 'application/pdf');

    $response = $this->actingAs($user)->post(route('support.attachments.store', $ticket->reference_id), [
        'attachment' => $file,
    ]);

    $response->assertSessionHasErrors('attachment');
});

it('returns validation error when attachment has invalid mime type', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $file = UploadedFile::fake()->create('script.php', 100, 'application/x-php');

    $response = $this->actingAs($user)->post(route('support.attachments.store', $ticket->reference_id), [
        'attachment' => $file,
    ]);

    $response->assertSessionHasErrors('attachment');
});

it('returns 404 when uploading attachment to non-existent ticket', function (): void {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->actingAs($user)->post(route('support.attachments.store', 'non-existent-id'), [
        'attachment' => $file,
    ]);

    $response->assertNotFound();
});

it('can upload multiple allowed file types', function (string $filename, string $mime): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $file = UploadedFile::fake()->create($filename, 100, $mime);

    $response = $this->actingAs($user)->post(route('support.attachments.store', $ticket->reference_id), [
        'attachment' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');
})->with([
    'pdf' => ['document.pdf', 'application/pdf'],
    'doc' => ['document.doc', 'application/msword'],
    'docx' => ['document.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'txt' => ['document.txt', 'text/plain'],
    'png' => ['image.png', 'image/png'],
    'jpg' => ['image.jpg', 'image/jpeg'],
    'jpeg' => ['image.jpeg', 'image/jpeg'],
    'gif' => ['image.gif', 'image/gif'],
]);

/*
|--------------------------------------------------------------------------
| Attachment Destroy Tests
|--------------------------------------------------------------------------
*/

it('can delete attachment from own ticket', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    // Create a file in storage and database
    $path = 'support/test-file.pdf';
    Storage::disk('local')->put($path, 'test content');

    $file = $ticket->files()->create([
        'name' => 'test-file.pdf',
        'filename' => 'test-file.pdf',
        'path' => $path,
        'visibility' => FileVisibility::Private,
    ]);

    $response = $this->actingAs($user)->delete(route('support.attachments.destroy', [
        'ticket' => $ticket->reference_id,
        'file' => $file->reference_id,
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('message');

    $this->assertDatabaseMissing('files', ['id' => $file->id]);
    Storage::disk('local')->assertMissing($path);
});

it('redirects guests to login when deleting attachment', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $path = 'support/test-file.pdf';
    Storage::disk('local')->put($path, 'test content');

    $file = $ticket->files()->create([
        'name' => 'test-file.pdf',
        'filename' => 'test-file.pdf',
        'path' => $path,
        'visibility' => FileVisibility::Private,
    ]);

    $response = $this->delete(route('support.attachments.destroy', [
        'ticket' => $ticket->reference_id,
        'file' => $file->reference_id,
    ]));

    $response->assertRedirect(route('login'));
});

it('returns 403 when deleting attachment from other user ticket', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $otherUser->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $path = 'support/test-file.pdf';
    Storage::disk('local')->put($path, 'test content');

    $file = $ticket->files()->create([
        'name' => 'test-file.pdf',
        'filename' => 'test-file.pdf',
        'path' => $path,
        'visibility' => FileVisibility::Private,
    ]);

    $response = $this->actingAs($user)->delete(route('support.attachments.destroy', [
        'ticket' => $ticket->reference_id,
        'file' => $file->reference_id,
    ]));

    $response->assertForbidden();
});

it('returns 404 when deleting attachment from non-existent ticket', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $path = 'support/test-file.pdf';
    Storage::disk('local')->put($path, 'test content');

    $file = $ticket->files()->create([
        'name' => 'test-file.pdf',
        'filename' => 'test-file.pdf',
        'path' => $path,
        'visibility' => FileVisibility::Private,
    ]);

    $response = $this->actingAs($user)->delete(route('support.attachments.destroy', [
        'ticket' => 'non-existent-id',
        'file' => $file->reference_id,
    ]));

    $response->assertNotFound();
});

it('returns 404 when deleting non-existent attachment', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->delete(route('support.attachments.destroy', [
        'ticket' => $ticket->reference_id,
        'file' => 'non-existent-ref',
    ]));

    $response->assertNotFound();
});
