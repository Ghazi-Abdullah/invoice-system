<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp', 6)->nullable()->after('img');
            $table->string('otp_via', 20)->nullable()->after('otp');
            $table->timestamp('otp_created_at')->nullable()->after('otp_via');
            $table->tinyInteger('otp_attempts')->default(0)->after('otp_created_at');
            $table->timestamp('otp_verified_at')->nullable()->after('otp_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'otp',
                'otp_via',
                'otp_created_at',
                'otp_attempts',
                'otp_verified_at',
            ]);
        });
    }
};
