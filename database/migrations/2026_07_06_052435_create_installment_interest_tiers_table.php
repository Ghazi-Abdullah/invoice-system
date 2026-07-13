<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_interest_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number_of_installments')->unique()
                ->comment('عدد الأقساط الذي تنطبق عليه هذه النسبة');
            $table->decimal('interest_rate', 5, 2)
                ->comment('نسبة الفائدة % المقترحة لهذا العدد من الأقساط');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_interest_tiers');
    }
};
