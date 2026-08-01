<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('SAR');
            $table->enum('status', ['active', 'paid', 'expired', 'cancelled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('sent_via', ['email', 'sms', 'whatsapp', 'manual'])->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['token', 'status']);
            $table->index(['invoice_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
