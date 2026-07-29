<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Database\Seeder;

class SupportTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $ticket1 = SupportTicket::create([
            'user_id' => 1,
            'ticket_number' => 'TKT-DEMO-001',
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'subject' => 'مشكلة في إنشاء فاتورة',
            'message' => 'لا أستطيع إضافة عناصر للفاتورة الجديدة، يظهر خطأ عند الضغط على زر الإضافة.',
            'status' => 'open',
            'priority' => 'high',
        ]);

        SupportTicketReply::create([
            'ticket_id' => $ticket1->id,
            'user_id' => null,
            'message' => 'شكراً لتواصلك. هل يمكنك إرسال لقطة شاشة للخطأ؟',
            'is_admin_reply' => true,
        ]);

        SupportTicketReply::create([
            'ticket_id' => $ticket1->id,
            'user_id' => 1,
            'message' => 'أرفقت لقطة الشاشة في المرفقات.',
            'is_admin_reply' => false,
        ]);

        $ticket2 = SupportTicket::create([
            'user_id' => 1,
            'ticket_number' => 'TKT-DEMO-002',
            'name' => 'سارة علي',
            'email' => 'sara@example.com',
            'subject' => 'استفسار عن خطة الأقساط',
            'message' => 'كيف يمكنني تعديل خطة الأقساط بعد إنشائها؟',
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        SupportTicketReply::create([
            'ticket_id' => $ticket2->id,
            'user_id' => null,
            'message' => 'يمكنك تعديل خطة الأقساط من صفحة تفاصيل الفاتورة.',
            'is_admin_reply' => true,
        ]);

        SupportTicket::create([
            'user_id' => 1,
            'ticket_number' => 'TKT-DEMO-003',
            'name' => 'خالد عبدالله',
            'email' => 'khaled@example.com',
            'subject' => 'خطأ في الدفع عبر Stripe',
            'message' => 'ظهر خطأ أثناء معالجة الدفع عبر Stripe، رمز الخطأ: card_declined.',
            'status' => 'closed',
            'priority' => 'high',
        ]);
    }
}
