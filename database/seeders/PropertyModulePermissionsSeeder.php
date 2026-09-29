<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyModulePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admin_menus')->insertOrIgnore([
            'id'         => 7,
            'title_en'   => 'Properties',
            'title_ar'   => 'العقارات',
            'link'       => null,
            'icon_class' => 'fa-building',
            'sort_order' => 7,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = [
            ['id' => 27, 'title' => 'view_properties', 'description_en' => 'View Properties', 'description_ar' => 'عرض العقارات'],
            ['id' => 28, 'title' => 'create_property',  'description_en' => 'Create Property',  'description_ar' => 'إنشاء عقار'],
            ['id' => 29, 'title' => 'edit_property',    'description_en' => 'Edit Property',    'description_ar' => 'تعديل عقار'],
            ['id' => 30, 'title' => 'delete_property',  'description_en' => 'Delete Property',  'description_ar' => 'حذف عقار'],

            ['id' => 31, 'title' => 'view_floors',  'description_en' => 'View Floors',  'description_ar' => 'عرض الطوابق'],
            ['id' => 32, 'title' => 'create_floor', 'description_en' => 'Create Floor', 'description_ar' => 'إنشاء طابق'],
            ['id' => 33, 'title' => 'edit_floor',   'description_en' => 'Edit Floor',   'description_ar' => 'تعديل طابق'],
            ['id' => 34, 'title' => 'delete_floor', 'description_en' => 'Delete Floor', 'description_ar' => 'حذف طابق'],

            ['id' => 35, 'title' => 'view_units',  'description_en' => 'View Units',  'description_ar' => 'عرض الوحدات'],
            ['id' => 36, 'title' => 'create_unit', 'description_en' => 'Create Unit', 'description_ar' => 'إنشاء وحدة'],
            ['id' => 37, 'title' => 'edit_unit',   'description_en' => 'Edit Unit',   'description_ar' => 'تعديل وحدة'],
            ['id' => 38, 'title' => 'delete_unit', 'description_en' => 'Delete Unit', 'description_ar' => 'حذف وحدة'],

            ['id' => 39, 'title' => 'view_tenants',  'description_en' => 'View Tenants',  'description_ar' => 'عرض المستأجرين'],
            ['id' => 40, 'title' => 'create_tenant', 'description_en' => 'Create Tenant', 'description_ar' => 'إنشاء مستأجر'],
            ['id' => 41, 'title' => 'edit_tenant',   'description_en' => 'Edit Tenant',   'description_ar' => 'تعديل مستأجر'],
            ['id' => 42, 'title' => 'delete_tenant', 'description_en' => 'Delete Tenant', 'description_ar' => 'حذف مستأجر'],
        ];

        foreach ($permissions as $permission) {
            DB::table('admin_permissions')->insertOrIgnore(array_merge($permission, [
                'admin_menu_id'     => 7,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]));
        }

        foreach ([1, 2] as $groupId) {
            foreach ($permissions as $permission) {
                DB::table('admin_group_permissions')->insertOrIgnore([
                    'admin_group_id'      => $groupId,
                    'admin_permission_id' => $permission['id'],
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        }
    }
}
