<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('fingerprint_id')->index();
            $table->json('fingerprint_data')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->text('ban_reason')->nullable();
            $table->foreignId('banned_by')->nullable()->constrained('users');
            $table->timestamp('banned_at')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['user_id', 'fingerprint_id']);
            $table->index(['fingerprint_id', 'last_seen_at']);
            $table->index(['fingerprint_id', 'is_banned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_fingerprints');
    }
};
