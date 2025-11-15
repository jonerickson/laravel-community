<?php

declare(strict_types=1);

use App\Http\Controllers\KnowledgeBase\KnowledgeBaseController;
use Illuminate\Support\Facades\Route;

Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
Route::get('/knowledge-base/search', [KnowledgeBaseController::class, 'search'])->name('knowledge-base.search');
Route::get('/knowledge-base/{article:slug}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');
