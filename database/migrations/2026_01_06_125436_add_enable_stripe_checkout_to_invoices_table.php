<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // إضافة حقل enable_stripe_checkout بعد حقل notes
            $table->boolean('enable_stripe_checkout')->default(false)->after('notes');

            // إضافة فهرس للحقل الجديد للاستعلامات السريعة
            $table->index(['enable_stripe_checkout', 'status'], 'invoice_stripe_status_index');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoice_stripe_status_index');
            $table->dropColumn('enable_stripe_checkout');
        });
    }
};
