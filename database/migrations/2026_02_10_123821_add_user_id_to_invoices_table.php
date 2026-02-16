<?php
// database/migrations/xxxx_add_user_id_to_invoices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // إضافة العمود user_id
            $table->foreignId('user_id')->nullable()->after('client_id')->constrained()->onDelete('cascade');

            // إضافة فهرس
            $table->index('user_id');
        });

        // نسخ القيم من created_by إلى user_id للبيانات الحالية
        DB::statement('UPDATE invoices SET user_id = created_by WHERE user_id IS NULL');

        // لجعل الحقل مطلوباً بعد ملء البيانات (اختياري)
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
