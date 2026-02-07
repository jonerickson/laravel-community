<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->string('context');
            $table->timestamp('consented_at');
            $table->timestamps();

            $table->index('context');
            $table->unique(['user_id', 'policy_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_consents');
    }
};
