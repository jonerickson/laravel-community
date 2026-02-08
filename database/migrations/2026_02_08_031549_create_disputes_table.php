<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('external_dispute_id')->unique();
            $table->string('external_charge_id');
            $table->string('external_payment_intent_id')->nullable();
            $table->string('status')->index();
            $table->string('reason')->nullable();
            $table->bigInteger('amount');
            $table->string('currency', 10)->default('usd');
            $table->dateTime('evidence_due_by')->nullable();
            $table->boolean('is_charge_refundable')->default(false);
            $table->string('network_reason_code')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
