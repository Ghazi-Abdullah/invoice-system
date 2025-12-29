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
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // جدول القوائم الرئيسية
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('link')->nullable();
            $table->string('icon_class')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول القوائم الفرعية
        Schema::create('admin_sub_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_menu_id')->constrained()->onDelete('cascade');
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('link')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول الصلاحيات
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_menu_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('admin_sub_menu_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('parent_id')->default(0);
            $table->string('title')->unique();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->boolean('is_parent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول صلاحيات المجموعات
        Schema::create('admin_group_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(
                ['admin_group_id', 'admin_permission_id'],
                'agp_group_permission_unique'
            );
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
