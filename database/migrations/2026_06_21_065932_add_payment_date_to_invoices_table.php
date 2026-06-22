<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // إضافة العمود فقط إذا لم يكن موجوداً
        if (!Schema::hasColumn('invoices', 'payment_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->date('payment_date')->nullable()->after('paid_at');
            });
        }
    }

    public function down()
    {
        // حذف العمود فقط إذا كان موجوداً
        if (Schema::hasColumn('invoices', 'payment_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('payment_date');
            });
        }
    }
};
