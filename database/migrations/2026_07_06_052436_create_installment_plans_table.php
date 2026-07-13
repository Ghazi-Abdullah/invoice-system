<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number_of_installments');
            $table->decimal('interest_rate', 5, 2)
                ->comment('نسبة الفائدة الفعلية المطبقة على هذه الخطة (قد تختلف عن الجدول الافتراضي لو عُدّلت يدوياً)');
            $table->decimal('original_amount', 12, 2)
                ->comment('مبلغ الفاتورة الأصلي قبل الفائدة');
            $table->decimal('interest_amount', 12, 2)
                ->comment('إجمالي مبلغ الفائدة المضاف');
            $table->decimal('total_amount', 12, 2)
                ->comment('original_amount + interest_amount — الإجمالي المطلوب سداده عبر الأقساط');
            $table->date('start_date')->comment('تاريخ استحقاق أول قسط');
            $table->enum('frequency', ['weekly', 'monthly'])->default('monthly')
                ->comment('الفاصل الزمني بين كل قسط والي يليه');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // فاتورة واحدة ما يصير لها إلا خطة أقساط "فعالة" وحدة بنفس اللحظة
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
