<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| إضافة حقل payment_date لجدول invoices
|--------------------------------------------------------------------------
| كان markAsPaid() يرسل payment_date لكنه لم يُحفظ لأنه:
| 1. غير موجود في $fillable في Invoice model  ← تم الإصلاح في Invoice.php
| 2. غير موجود في الجدول أصلاً               ← هذه الـ migration تحله
|
| paid_at = توقيت دقيق (datetime) يُحفظ تلقائياً عند الدفع
| payment_date = تاريخ الدفع (date) قابل للتخصيص من المستخدم
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // نضيفه بعد paid_at مباشرة للترتيب المنطقي
            $table->date('payment_date')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_date');
        });
    }
};
