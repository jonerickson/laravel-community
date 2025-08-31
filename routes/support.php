<?php

declare(strict_types=1);

use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/support/tickets/create', [SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support/tickets', [SupportTicketController::class, 'store'])->name('support.store');
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::get('/support/tickets', [SupportTicketController::class, 'index'])->name('support.index');
});
