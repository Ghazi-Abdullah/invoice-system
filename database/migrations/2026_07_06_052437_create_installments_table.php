<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('installment_number')
                ->comment('ترتيب القسط داخل الخطة: 1, 2, 3...');
            $table->date('due_date');
            $table->decimal('amount', 12, 2)
                ->comment('المبلغ المطلوب لهذا القسط تحديداً');
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete()
                ->comment('سجل الدفعة المرتبط بهذا القسط بعد السداد');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['installment_plan_id', 'installment_number']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
