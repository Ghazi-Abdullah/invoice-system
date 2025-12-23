<?php

namespace Database\Seeders;

use App\Models\AdminGroup;
use App\Models\AdminPermission;
use Illuminate\Database\Seeder;

class AssignPermissionsSeeder extends Seeder
{
    public function run()
    {
        $superAdmin = AdminGroup::where('name', 'Super Admin')->first();
        $admin = AdminGroup::where('name', 'Admin')->first();
        $accountant = AdminGroup::where('name', 'Accountant')->first();
        $sales = AdminGroup::where('name', 'Sales')->first();
        $client = AdminGroup::where('name', 'Client')->first();

        $permissions = AdminPermission::all();

        // Super Admin gets all permissions
        if ($superAdmin) {
            $superAdmin->permissions()->sync($permissions->pluck('id'));
        }

        // Admin gets most permissions except some system ones
        if ($admin) {
            $adminPermissions = $permissions->whereNotIn('permission_code', [
                'delete_admin_group', // Can't delete admin groups
            ]);
            $admin->permissions()->sync($adminPermissions->pluck('id'));
        }

        // Accountant permissions
        if ($accountant) {
            $accountantPermissions = $permissions->whereIn('permission_code', [
                'view_dashboard',
                'view_invoices',
                'create_invoice',
                'edit_invoice',
                'send_invoice',
                'download_invoice',
                'view_clients',
                'view_reports',
                'generate_reports',
                'export_reports',
            ]);
            $accountant->permissions()->sync($accountantPermissions->pluck('id'));
        }

        // Sales permissions
        if ($sales) {
            $salesPermissions = $permissions->whereIn('permission_code', [
                'view_dashboard',
                'view_invoices',
                'create_invoice',
                'edit_invoice',
                'send_invoice',
                'download_invoice',
                'view_clients',
                'create_client',
                'edit_client',
            ]);
            $sales->permissions()->sync($salesPermissions->pluck('id'));
        }

        // Client permissions (very limited)
        if ($client) {
            $clientPermissions = $permissions->whereIn('permission_code', [
                'view_dashboard', // Limited dashboard view
            ]);
            $client->permissions()->sync($clientPermissions->pluck('id'));
        }

        $this->command->info('Permissions assigned to admin groups successfully.');
    }
}
