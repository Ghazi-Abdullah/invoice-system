<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\Constants;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // العلاقات بنفس نمط مشروعك
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // البيانات المالية
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('status')->default(Constants::PAYMENT_STATUS_PENDING);
            $table->string('payment_method')->nullable();
            $table->string('payment_gateway')->default('stripe');

            // معرّفات Stripe
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();

            // Metadata بنفس نمط جدول الفواتير
            $table->json('metadata')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // الفهارس بنفس النمط
            $table->index(['invoice_id', 'status']);
            $table->index(['client_id', 'created_at']);
            $table->index('stripe_payment_intent_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
