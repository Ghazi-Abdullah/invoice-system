<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\Invoice\InvoiceInterface;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InstallmentPlan;

class InvoiceController extends Controller
{
    protected $invoiceRepository;

    public function __construct(InvoiceInterface $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function index(Request $request)
    {
        $result = $this->invoiceRepository->index($request);
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function store(Request $request)
    {
        $result = $this->invoiceRepository->store($request);
        return response()->json($result, $result['status'] ? 201 : 500);
    }

    public function show($id)
    {
        $result = $this->invoiceRepository->show($id);
        return response()->json($result, $result['status'] ? 200 : ($result['message'] === __('messages.not_found') ? 404 : 500));
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.not_found'),
            ], 404);
        }

        $result = $this->invoiceRepository->update($request, $invoice);
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function destroy($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.not_found'),
            ], 404);
        }

        $result = $this->invoiceRepository->destroy($invoice);
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function dashboardStats()
    {
        $result = $this->invoiceRepository->getDashboardStats();
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function overdueInvoices()
    {
        $result = $this->invoiceRepository->getOverdueInvoices();
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function recentInvoices(Request $request)
    {
        $limit = $request->get('limit', 10);
        $result = $this->invoiceRepository->getRecentInvoices($limit);
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function downloadPDF($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.not_found'),
            ], 404);
        }

        $result = $this->invoiceRepository->generatePDF($invoice);
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function duplicate($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.not_found'),
            ], 404);
        }

        $result = $this->invoiceRepository->duplicate($invoice);
        return response()->json($result, $result['status'] ? 201 : 500);
    }

    public function generatePDF($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.not_found'),
            ], 404);
        }

        $result = $this->invoiceRepository->generatePDF($invoice);
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function markAsPaid(Request $request, $id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.not_found'),
            ], 404);
        }

        $result = $this->invoiceRepository->markAsPaid($request, $invoice);
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    public function send($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.not_found'),
            ], 404);
        }

        $result = $this->invoiceRepository->sendInvoice($invoice);
        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * ✅ إضافة: الحصول على خطة الأقساط المرتبطة بالفاتورة
     */
    public function getInstallmentPlan($id)
    {
        try {
            $invoice = Invoice::with(['installmentPlan.installments', 'client'])->find($id);

            if (!$invoice) {
                return response()->json([
                    'status'  => false,
                    'message' => __('messages.not_found'),
                ], 404);
            }

            $plan = $invoice->installmentPlan;

            if (!$plan) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لا توجد خطة أقساط لهذه الفاتورة.',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب خطة الأقساط بنجاح',
                'data'    => $plan,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('InvoiceController getInstallmentPlan error', [
                'invoice_id' => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => __('messages.operation_failed'),
            ], 500);
        }
    }

    /**
     * ✅ إضافة: جلب عدد التنبيهات للجرس
     */
    public function notificationCounts()
    {
        try {
            $counts = \App\Helpers\InvoiceNotificationHelper::getCounts();

            return response()->json([
                'status' => true,
                'data' => [
                    'unpaid'   => $counts['unpaid'],
                    'overdue'  => $counts['overdue'],
                    'due_soon' => $counts['due_soon'],
                    'total'    => $counts['total'],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('InvoiceController notificationCounts error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => __('messages.operation_failed'),
            ], 500);
        }
    }
}
