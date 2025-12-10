<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminGroupToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('admin_group_id')->nullable()->constrained('admin_groups');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['admin_group_id']);
            $table->dropColumn(['admin_group_id', 'is_active']);
        });
    }
}
