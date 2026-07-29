<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('id');
            $table->unsignedBigInteger('replaced_by')->nullable()->after('version');
            $table->foreign('replaced_by')->references('id')->on('installment_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->dropForeign(['replaced_by']);
            $table->dropColumn(['version', 'replaced_by']);
        });
    }
};
