<?php

declare(strict_types=1);

use App\Http\Controllers\SupportTickets\CommentController;
use App\Http\Controllers\SupportTickets\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/support/tickets/create', [SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support/tickets', [SupportTicketController::class, 'store'])->name('support.store');
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::get('/support/tickets', [SupportTicketController::class, 'index'])->name('support.index');

    Route::post('/support/tickets/{ticket}/comments', [CommentController::class, 'store'])->name('support.comments.store');
    Route::put('/support/tickets/{ticket}/comments/{comment}', [CommentController::class, 'update'])->name('support.comments.update');
    Route::delete('/support/tickets/{ticket}/comments/{comment}', [CommentController::class, 'destroy'])->name('support.comments.destroy');
});
