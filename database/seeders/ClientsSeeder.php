<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientsSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('email', 'admin@invoice.com')->first();
        $adminId = $admin ? $admin->id : 1;

        $clients = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'address' => '123 Main St, New York, NY 10001',
                'company_name' => 'John Doe Enterprises',
                'tax_number' => 'TAX-123456',
                'payment_terms' => 'net_30',
                'currency' => 'USD',
                'notes' => 'Regular client with good payment history',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+1234567891',
                'address' => '456 Oak Ave, Los Angeles, CA 90001',
                'company_name' => 'Smith & Co.',
                'tax_number' => 'TAX-789012',
                'payment_terms' => 'net_15',
                'currency' => 'USD',
                'notes' => 'New client',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Robert Johnson',
                'email' => 'robert@example.com',
                'phone' => '+1234567892',
                'address' => '789 Pine Rd, Chicago, IL 60007',
                'company_name' => 'Johnson Industries',
                'tax_number' => 'TAX-345678',
                'payment_terms' => 'due_on_receipt',
                'currency' => 'EUR',
                'notes' => 'International client',
                'is_active' => true,
                'created_by' => $adminId,
            ],
        ];

        foreach ($clients as $client) {
            Client::updateOrCreate(
                ['email' => $client['email']],
                $client
            );
        }
    }
}
