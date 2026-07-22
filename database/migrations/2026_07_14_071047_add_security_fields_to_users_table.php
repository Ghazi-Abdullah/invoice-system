<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ✅ إضافة الحقول المطلوبة للأمان والتتبع
     */
    public function up(): void
    {
        // ── 1. إضافة last_login_at إلى users ──────────────────────
        if (!Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable()->after('last_login_ip');
            });
        }

        // ── 2. التأكد من وجود جدول password_reset_tokens ───────────
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // ── 3. إضافة index على email في users (للأداء) ───────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasIndex('users', 'users_email_index')) {
                $table->index('email');
            }
        });

        // ── 4. إضافة expires_at إلى otp_logs ─────────────────────
        if (!Schema::hasColumn('otp_logs', 'expires_at')) {
            Schema::table('otp_logs', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('ip_address');
            });
        }

        // ── 5. إضافة index على ip_address في activity_logs ───────
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasIndex('activity_logs', 'activity_logs_ip_index')) {
                $table->index('ip_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });

        Schema::dropIfExists('password_reset_tokens');
    }
};
