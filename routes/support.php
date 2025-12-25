<?php

declare(strict_types=1);

use App\Http\Controllers\SupportTickets\AttachmentController;
use App\Http\Controllers\SupportTickets\CommentController;
use App\Http\Controllers\SupportTickets\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/support/tickets', [SupportTicketController::class, 'index'])->name('support.index');

Route::group(['middleware' => ['auth', 'verified']], function (): void {
    Route::get('/support/tickets/create', [SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support/tickets', [SupportTicketController::class, 'store'])->middleware('throttle:support-ticket')->name('support.store');
    Route::get('/support/tickets/{ticket:reference_id}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::patch('/support/tickets/{ticket:reference_id}', [SupportTicketController::class, 'update'])->name('support.update');

    Route::post('/support/tickets/{ticket:reference_id}/comments', [CommentController::class, 'store'])->name('support.comments.store');
    Route::delete('/support/tickets/{ticket:reference_id}/comments/{comment}', [CommentController::class, 'destroy'])->name('support.comments.destroy');

    Route::post('/support/tickets/{ticket:reference_id}/attachments', [AttachmentController::class, 'store'])->name('support.attachments.store');
    Route::delete('/support/tickets/{ticket:reference_id}/attachments/{file:reference_id}', [AttachmentController::class, 'destroy'])->name('support.attachments.destroy');
});
