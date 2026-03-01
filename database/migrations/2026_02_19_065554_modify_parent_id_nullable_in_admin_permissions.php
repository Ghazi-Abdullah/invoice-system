<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_permissions', function (Blueprint $table) {
            // تغيير عمود parent_id ليكون nullable (بدون قيمة افتراضية)
            $table->unsignedBigInteger('parent_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_permissions', function (Blueprint $table) {
            // إعادة العمود إلى حالته السابقة (default 0 و not nullable)
            // يجب توخي الحذر: هذا قد يفشل إذا كانت هناك قيم null موجودة
            $table->unsignedBigInteger('parent_id')->default(0)->change();
        });
    }
};
