<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        // إنشاء أو الحصول على المستخدم
        $user = User::firstOrCreate(
            ['email' => 'demo@invoice.com'],
            [
                'name' => 'مستخدم تجريبي',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        // إضافة عملاء تجريبيين
        $clients = [
            [
                'name' => 'شركة التقنية المتطورة',
                'email' => 'tech@company.com',
                'phone' => '0501111111',
                'address' => 'الرياض - حي العليا',
                'company_name' => 'شركة التقنية المتطورة',
                'tax_number' => '310123456700001',
            ],
            [
                'name' => 'مؤسسة البناء الحديث',
                'email' => 'construction@company.com',
                'phone' => '0502222222',
                'address' => 'جدة - حي الصفا',
                'company_name' => 'مؤسسة البناء الحديث',
                'tax_number' => '310123456700002',
            ],
            [
                'name' => 'شركة النقل السريع',
                'email' => 'transport@company.com',
                'phone' => '0503333333',
                'address' => 'الدمام - حي الراكة',
                'company_name' => 'شركة النقل السريع',
                'tax_number' => '310123456700003',
            ],
        ];

        foreach ($clients as $clientData) {
            $client = Client::firstOrCreate(
                ['email' => $clientData['email']],
                array_merge($clientData, ['user_id' => $user->id])
            );

            // إضافة فواتير لكل عميل
            for ($i = 1; $i <= 3; $i++) {
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);

                $invoice = Invoice::firstOrCreate(
                    ['invoice_number' => $invoiceNumber],
                    [
                        'client_id' => $client->id,
                        'user_id' => $user->id,
                        'issue_date' => now()->subDays(rand(1, 30)),
                        'due_date' => now()->addDays(rand(15, 45)),
                        'subtotal' => rand(1000, 10000),
                        'tax_amount' => rand(150, 1500),
                        'total_amount' => rand(1150, 11500),
                        'status' => ['draft', 'sent', 'paid', 'overdue'][rand(0, 3)],
                        'notes' => 'فاتورة تجريبية رقم ' . $i,
                    ]
                );

                // إضافة عناصر للفاتورة
                $itemsCount = rand(1, 5);
                for ($j = 1; $j <= $itemsCount; $j++) {
                    $quantity = rand(1, 10);
                    $unitPrice = rand(100, 1000);

                    InvoiceItem::firstOrCreate(
                        [
                            'invoice_id' => $invoice->id,
                            'description' => 'منتج أو خدمة ' . $j,
                        ],
                        [
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'total' => $quantity * $unitPrice,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ تم إضافة بيانات تجريبية بنجاح!');
        $this->command->info('👤 المستخدم: demo@invoice.com / password123');
        $this->command->info('👥 العملاء: ' . Client::where('user_id', $user->id)->count());
        $this->command->info('🧾 الفواتير: ' . Invoice::where('user_id', $user->id)->count());
    }
}
