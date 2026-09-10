<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/2026_08_02_000002_add_is_internal_to_support_ticket_replies_table.php
    public function up(): void
    {
        Schema::table('support_ticket_replies', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('is_admin_reply');
        });
    }
    public function down(): void
    {
        Schema::table('support_ticket_replies', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};
