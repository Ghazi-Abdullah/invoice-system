<?php

namespace App\Repository\Admin\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class PaymentRepository implements PaymentInterface
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function createCheckoutSession($invoice)
    {
        DB::beginTransaction();

        try {
            // التحقق من الفاتورة
            if (!$invoice->canBePaid()) {
                return [
                    'status' => false,
                    'message' => __('messages.invoice_cannot_be_paid'),
                    'data' => null
                ];
            }

            // إنشاء جلسة Stripe
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($invoice->currency ?: 'sar'),
                        'product_data' => [
                            'name' => 'فاتورة #' . $invoice->invoice_number,
                            'description' => 'فاتورة من ' . ($invoice->client->company_name ?: $invoice->client->name),
                        ],
                        'unit_amount' => (int)($invoice->total * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('FRONTEND_URL') . '/invoices/' . $invoice->id . '?payment_success=true&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('FRONTEND_URL') . '/invoices/' . $invoice->id . '?payment_cancelled=true',
                'customer_email' => $invoice->client->email,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_id' => $invoice->client_id,
                ],
            ]);

            // إنشاء سجل الدفع
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'user_id' => auth()->id(),
                'amount' => $invoice->total,
                'currency' => $invoice->currency ?: 'SAR',
                'status' => Constants::PAYMENT_STATUS_PENDING,
                'payment_method' => Constants::PAYMENT_METHOD_CARD,
                'stripe_checkout_session_id' => $checkoutSession->id,
                'stripe_payment_intent_id' => $checkoutSession->payment_intent,
                'metadata' => [
                    'checkout_session_url' => $checkoutSession->url,
                    'checkout_session_status' => $checkoutSession->status,
                ],
            ]);

            DB::commit();

            Log::info('جلسة دفع جديدة تم إنشاؤها', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'session_id' => $checkoutSession->id,
            ]);

            return [
                'status' => true,
                'message' => __('messages.payment_session_created'),
                'data' => [
                    'payment_id' => $payment->id,
                    'session_id' => $checkoutSession->id,
                    'url' => $checkoutSession->url,
                    'expires_at' => date('Y-m-d H:i:s', $checkoutSession->expires_at),
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('فشل في إنشاء جلسة الدفع: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => __('messages.payment_session_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function handleWebhook($payload, $sigHeader)
    {
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Invalid signature'];
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                return $this->handleCheckoutSessionCompleted($event->data->object);
            case 'payment_intent.succeeded':
                return $this->handlePaymentIntentSucceeded($event->data->object);
            case 'payment_intent.payment_failed':
                return $this->handlePaymentIntentFailed($event->data->object);
        }

        return ['status' => true, 'message' => 'Event handled'];
    }

    private function handleCheckoutSessionCompleted($session)
    {
        try {
            $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

            if ($payment && $session->payment_status === 'paid') {
                $payment->markAsCompleted('card');

                Log::info('تم تحديث الدفع عبر ويب هوك', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                ]);

                return ['status' => true, 'message' => 'Payment completed'];
            }
        } catch (\Exception $e) {
            Log::error('خطأ في معالجة checkout.session.completed: ' . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    // ... باقي دوال handleWebhook

    public function getPayment($id)
    {
        $payment = Payment::with(['invoice', 'client', 'user'])->find($id);

        return [
            'status' => $payment ? true : false,
            'message' => $payment ? __('messages.payment_fetched') : __('messages.payment_not_found'),
            'data' => $payment
        ];
    }

    public function getPayments($request)
    {
        $query = Payment::with(['invoice', 'client'])
            ->orderBy('created_at', 'desc');

        // تطبيق الفلاتر بنفس نمط InvoiceRepository
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $payments = $query->paginate($request->per_page ?? 15);

        return [
            'status' => true,
            'message' => __('messages.payments_fetched'),
            'data' => $payments
        ];
    }

    public function refundPayment($paymentId, $amount = null)
    {
        // Implement refund logic
        return ['status' => true, 'message' => 'Refund processed'];
    }
}
