<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\User;
use App\Constants\Constants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleInvoicesSeeder extends Seeder
{
    public function run()
    {
        DB::beginTransaction();

        try {
            $admin = User::where('email', 'admin@invoice.com')->first();
            $clients = Client::all();

            if ($clients->isEmpty() || !$admin) {
                $this->command->info('No clients or admin found. Skipping sample invoices.');
                return;
            }

            $services = [
                'Web Development Services',
                'Mobile App Development',
                'UI/UX Design',
                'Digital Marketing',
                'SEO Optimization',
                'Content Writing',
                'Social Media Management',
                'Website Maintenance',
                'Consulting Services',
                'Technical Support'
            ];

            for ($i = 0; $i < 20; $i++) {
                $client = $clients->random();
                $status = ['draft', 'sent', 'paid', 'overdue'][rand(0, 3)];

                $invoice = Invoice::create([
                    'client_id' => $client->id,
                    'invoice_number' => 'INV-' . date('Ym') . '-' . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                    'invoice_date' => now()->subDays(rand(0, 60))->format('Y-m-d'),
                    'due_date' => now()->addDays(rand(15, 60))->format('Y-m-d'),
                    'status' => $status,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total' => 0,
                    'currency' => 'USD',
                    'notes' => rand(0, 1) ? 'Thank you for your business!' : null,
                    'terms' => 'Payment due within 30 days',
                    'created_by' => $admin->id,
                    'is_active' => true,
                    'sent_at' => $status !== 'draft' ? now()->subDays(rand(0, 30)) : null,
                    'paid_at' => $status === 'paid' ? now()->subDays(rand(0, 15)) : null,
                ]);

                // Create invoice items
                $itemCount = rand(1, 5);
                $subtotal = 0;

                for ($j = 0; $j < $itemCount; $j++) {
                    $quantity = rand(1, 10);
                    $unitPrice = rand(50, 1000);
                    $total = $quantity * $unitPrice;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $services[rand(0, count($services) - 1)],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'tax_rate' => 0,
                        'total' => $total,
                        'item_type' => 'service',
                    ]);

                    $subtotal += $total;
                }

                // Calculate totals
                $taxAmount = $subtotal * 0.15; // 15% tax
                $discountAmount = rand(0, 1) ? $subtotal * 0.1 : 0; // 10% discount randomly
                $total = $subtotal + $taxAmount - $discountAmount;

                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total' => $total,
                ]);
            }

            DB::commit();
            $this->command->info('Sample invoices created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Failed to create sample invoices: ' . $e->getMessage());
        }
    }
}
