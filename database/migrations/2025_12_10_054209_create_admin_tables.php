<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // جدول مجموعات المسؤولين
        Schema::create('admin_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_ar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول القوائم الرئيسية
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('link')->nullable();
            $table->string('icon_class')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // جدول القوائم الفرعية
        Schema::create('admin_sub_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_menu_id')->constrained('admin_menus');
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('link')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // جدول الصلاحيات (وليس permissions القديم)
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_menu_id')->constrained('admin_menus');
            $table->foreignId('admin_sub_menu_id')->nullable()->constrained('admin_sub_menus');
            $table->integer('parent_id')->default(0);
            $table->string('title');
            $table->string('description_en');
            $table->string('description_ar');
            $table->boolean('is_parent')->default(false);
            $table->timestamps();
        });

        // جدول صلاحيات المجموعات
        Schema::create('admin_group_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_group_id')->constrained('admin_groups');
            $table->foreignId('admin_permission_id')->constrained('admin_permissions');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_group_permissions');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_sub_menus');
        Schema::dropIfExists('admin_menus');
        Schema::dropIfExists('admin_groups');
    }
};
