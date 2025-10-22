<?php

declare(strict_types=1);

use App\Http\Controllers\SupportTickets\AttachmentController;
use App\Http\Controllers\SupportTickets\CommentController;
use App\Http\Controllers\SupportTickets\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth', 'verified']], function (): void {
    Route::get('/support/tickets/create', [SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support/tickets', [SupportTicketController::class, 'store'])->middleware('throttle:support-ticket')->name('support.store');
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::patch('/support/tickets/{ticket}', [SupportTicketController::class, 'update'])->name('support.update');
    Route::get('/support/tickets', [SupportTicketController::class, 'index'])->name('support.index');

    Route::post('/support/tickets/{ticket}/comments', [CommentController::class, 'store'])->name('support.comments.store');
    Route::delete('/support/tickets/{ticket}/comments/{comment}', [CommentController::class, 'destroy'])->name('support.comments.destroy');

    Route::post('/support/tickets/{ticket}/attachments', [AttachmentController::class, 'store'])->name('support.attachments.store');
    Route::delete('/support/tickets/{ticket}/attachments/{file}', [AttachmentController::class, 'destroy'])->name('support.attachments.destroy');
});
