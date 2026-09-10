<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/2026_08_02_000001_add_assigned_to_to_support_tickets_table.php
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('last_customer_reply_at')->nullable()->after('status');
            $table->timestamp('last_admin_reply_at')->nullable()->after('last_customer_reply_at');
        });
    }
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['last_customer_reply_at', 'last_admin_reply_at']);
        });
    }
};
