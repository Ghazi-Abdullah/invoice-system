<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Payment\PaymentInterface;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use ResponseTrait;

    public $payment;

    public function __construct(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }

    /**
     * إنشاء جلسة دفع لفاتورة
     */
    public function createCheckoutSession(Request $request, $invoiceId)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_PAYMENT)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $invoice = Invoice::with('client')->find($invoiceId);

        if (!$invoice) {
            return $this->failureResponse(
                __('messages.invoice_not_found'),
                null,
                Constants::RESPONSE_NOT_FOUND
            );
        }

        $data = $this->payment->createCheckoutSession($invoice);

        if ($data['status']) {
            return $this->successResponse(
                $data['message'],
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    /**
     * معالجة Stripe Webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        $data = $this->payment->handleWebhook($payload, $sigHeader);

        if ($data['status']) {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['error' => $data['message']], 400);
    }

    /**
     * الحصول على تفاصيل دفع
     */
    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_PAYMENTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->payment->getPayment($id);

        if ($data['status']) {
            return $this->successResponse(
                $data['message'],
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    /**
     * الحصول على قائمة المدفوعات
     */
    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_PAYMENTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->payment->getPayments($request);

        if ($data['status']) {
            return $this->successResponse(
                $data['message'],
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    /**
     * إجراء استرجاع مبلغ
     */
    public function refund(Request $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::REFUND_PAYMENT)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $amount = $request->get('amount');
        $data = $this->payment->refundPayment($id, $amount);

        if ($data['status']) {
            return $this->successResponse(
                $data['message'],
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }
}
