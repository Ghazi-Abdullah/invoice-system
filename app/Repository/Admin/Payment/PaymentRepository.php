<?php

namespace App\Repository\Admin\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Constants\Constants;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;

class PaymentRepository implements PaymentInterface
{
    public function __construct()
    {
        // ✅ config() بدل env()
        Stripe::setApiKey(Config::get('services.stripe.secret'));
    }

    public function createCheckoutSession($invoice)
    {
        DB::beginTransaction();

        try {
            if (!$invoice->canBePaid()) {
                return [
                    'status'  => false,
                    'message' => __('messages.invoice_cannot_be_paid'),
                    'data'    => null,
                ];
            }

            // ✅ تحقق أن الـ client محمّل (تجنب N+1)
            if (!$invoice->relationLoaded('client')) {
                $invoice->load('client:id,name,email,company_name');
            }

            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => strtolower($invoice->currency ?: 'sar'),
                        'product_data' => [
                            'name'        => 'فاتورة #' . $invoice->invoice_number,
                            'description' => 'فاتورة من ' . ($invoice->client->company_name ?: $invoice->client->name),
                        ],
                        'unit_amount'  => (int) ($invoice->total * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode'           => 'payment',
                'success_url'    => Config::get('app.frontend_url') . '/invoices/' . $invoice->id . '?payment_success=true&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'     => Config::get('app.frontend_url') . '/invoices/' . $invoice->id . '?payment_cancelled=true',
                'customer_email' => $invoice->client->email,
                'metadata'       => [
                    'invoice_id'     => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_id'      => $invoice->client_id,
                ],
            ]);

            $payment = Payment::create([
                'invoice_id'                 => $invoice->id,
                'client_id'                  => $invoice->client_id,
                'user_id'                    => auth()->id(),
                'amount'                     => $invoice->total,
                'currency'                   => $invoice->currency ?: 'SAR',
                'status'                     => Constants::PAYMENT_STATUS_PENDING,
                'payment_method'             => Constants::PAYMENT_METHOD_CARD,
                'payment_gateway'            => 'stripe',
                'stripe_checkout_session_id' => $checkoutSession->id,
                'stripe_payment_intent_id'   => $checkoutSession->payment_intent,
                'metadata'                   => [
                    'checkout_session_url'    => $checkoutSession->url,
                    'checkout_session_status' => $checkoutSession->status,
                ],
            ]);

            DB::commit();

            Log::info('Stripe checkout session created', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'session_id' => $checkoutSession->id,
            ]);

            return [
                'status'  => true,
                'message' => __('messages.payment_session_created'),
                'data'    => [
                    'payment_id' => $payment->id,
                    'session_id' => $checkoutSession->id,
                    'url'        => $checkoutSession->url,
                    'expires_at' => date('Y-m-d H:i:s', $checkoutSession->expires_at),
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PaymentRepository createCheckoutSession error', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => __('messages.payment_session_failed'),
                'data'    => null,
            ];
        }
    }

    public function handleWebhook($payload, $sigHeader)
    {
        $endpointSecret = Config::get('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Invalid signature'];
        }

        return match ($event->type) {
            'checkout.session.completed'    => $this->handleCheckoutSessionCompleted($event->data->object),
            'payment_intent.succeeded'      => $this->handlePaymentIntentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
            default                         => ['status' => true, 'message' => 'Event ignored'],
        };
    }

    public function getPayment($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            return [
                'status'  => false,
                'message' => __('messages.payment_not_found'),
                'data'    => null,
            ];
        }

        // ✅ select فقط ما يحتاجه الـ response
        $payment = Payment::with([
            'invoice:id,invoice_number,total,status,currency',
            'client:id,name,email',
            'user:id,name',
        ])->find((int) $id);

        return [
            'status'  => (bool) $payment,
            'message' => $payment ? __('messages.payment_fetched') : __('messages.payment_not_found'),
            'data'    => $payment,
        ];
    }

    public function getPayments($request)
    {
        try {
            // ✅ select فقط الحقول المطلوبة + with للعلاقات
            $query = Payment::select([
                'id',
                'invoice_id',
                'client_id',
                'user_id',
                'amount',
                'currency',
                'status',
                'payment_method',
                'payment_gateway',
                'paid_at',
                'created_at',
            ])
                ->with([
                    'invoice:id,invoice_number,total,status',
                    'client:id,name,email,company_name',
                ])
                ->orderBy('created_at', 'desc');

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                if (strtotime($request->date_from) && strtotime($request->date_to)) {
                    $query->whereBetween('created_at', [
                        $request->date_from . ' 00:00:00',
                        $request->date_to   . ' 23:59:59',
                    ]);
                }
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = substr(trim($request->search), 0, 100);
                $query->search($search);
            }

            $perPage  = min((int) ($request->per_page ?? 15), 100);
            $payments = $query->paginate($perPage);

            return [
                'status'  => true,
                'message' => __('messages.payments_fetched'),
                'data'    => $payments,
            ];
        } catch (\Exception $e) {
            Log::error('PaymentRepository getPayments error', ['error' => $e->getMessage()]);

            return [
                'status'  => false,
                'message' => __('messages.operation_failed'),
                'data'    => null,
            ];
        }
    }

    public function refundPayment($paymentId, $amount = null)
    {
        return ['status' => true, 'message' => 'Refund processed'];
    }

    // ================================================================
    // Private Helpers
    // ================================================================

    private function handleCheckoutSessionCompleted($session): array
    {
        try {
            $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

            if ($payment && $session->payment_status === 'paid') {
                $payment->markAsCompleted('card');

                Log::info('Payment completed via webhook', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                ]);

                return ['status' => true, 'message' => 'Payment completed'];
            }

            return ['status' => true, 'message' => 'Payment not found or not paid'];
        } catch (\Exception $e) {
            Log::error('handleCheckoutSessionCompleted error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function handlePaymentIntentSucceeded($paymentIntent): array
    {
        Log::info('PaymentIntent succeeded', ['id' => $paymentIntent->id]);
        return ['status' => true, 'message' => 'PaymentIntent handled'];
    }

    private function handlePaymentIntentFailed($paymentIntent): array
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->markAsFailed($paymentIntent->last_payment_error?->message);
        }

        Log::warning('PaymentIntent failed', ['id' => $paymentIntent->id]);
        return ['status' => true, 'message' => 'PaymentIntent failure handled'];
    }
}
