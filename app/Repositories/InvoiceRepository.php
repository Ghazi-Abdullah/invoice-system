<?php

namespace App\Repositories;

use App\Contracts\InvoiceRepositoryInterface;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    public function getUserInvoices(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $userId)
            ->with(['client', 'items']);

        // تطبيق الفلاتر
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['client_id']) && $filters['client_id'] !== '') {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $query->where(function ($q) use ($filters) {
                $q->where('invoice_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('client', function ($clientQuery) use ($filters) {
                      $clientQuery->where('name', 'like', '%' . $filters['search'] . '%')
                                 ->orWhere('email', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        // فلترة حسب التاريخ
        if (isset($filters['date'])) {
            $this->applyDateFilter($query, $filters['date']);
        }

        // الترتيب
        $query->orderBy('created_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function createInvoice(array $data)
    {
        return DB::transaction(function () use ($data) {
            // إنشاء رقم فاتورة فريد
            $invoiceNumber = $this->generateInvoiceNumber();

            // حساب المجاميع
            $totals = $this->calculateTotals($data['items']);

            // إنشاء الفاتورة
            $invoice = $this->model->create([
                'invoice_number' => $invoiceNumber,
                'client_id' => $data['client_id'],
                'user_id' => $data['user_id'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            // إنشاء عناصر الفاتورة
            $this->createInvoiceItems($invoice->id, $data['items']);

            return $this->getInvoiceWithItems($invoice->id);
        });
    }

    public function updateInvoice(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $invoice = $this->model->findOrFail($id);

            // حساب المجاميع الجديدة
            $totals = $this->calculateTotals($data['items']);

            // تحديث الفاتورة
            $invoice->update([
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
                'notes' => $data['notes'] ?? null,
            ]);

            // حذف العناصر القديمة وإنشاء الجديدة
            $invoice->items()->delete();
            $this->createInvoiceItems($invoice->id, $data['items']);

            return $this->getInvoiceWithItems($invoice->id);
        });
    }

    public function updateInvoiceStatus(int $id, string $status)
    {
        $invoice = $this->model->findOrFail($id);
        $invoice->update(['status' => $status]);

        return $this->getInvoiceWithItems($id);
    }

    public function getInvoiceWithItems(int $id)
    {
        return $this->model->with(['client', 'items', 'user'])->findOrFail($id);
    }

    public function deleteInvoice(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $invoice = $this->model->findOrFail($id);
            $invoice->items()->delete();
            return $invoice->delete();
        });
    }

    public function getUserInvoice(int $userId, int $invoiceId)
    {
        return $this->model->where('user_id', $userId)
            ->with(['client', 'items', 'user'])
            ->findOrFail($invoiceId);
    }

    public function getUserInvoiceStats(int $userId): array
    {
        $invoices = $this->model->where('user_id', $userId)->get();

        return [
            'total' => $invoices->count(),
            'draft' => $invoices->where('status', 'draft')->count(),
            'sent' => $invoices->where('status', 'sent')->count(),
            'paid' => $invoices->where('status', 'paid')->count(),
            'overdue' => $invoices->where('status', 'overdue')->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'paid_amount' => $invoices->where('status', 'paid')->sum('total_amount'),
        ];
    }

    public function getInvoicesByStatus(int $userId, string $status): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('status', $status)
            ->with(['client', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function searchUserInvoices(int $userId, string $searchTerm): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->where(function ($query) use ($searchTerm) {
                $query->where('invoice_number', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('client', function ($clientQuery) use ($searchTerm) {
                          $clientQuery->where('name', 'like', '%' . $searchTerm . '%')
                                     ->orWhere('email', 'like', '%' . $searchTerm . '%');
                      });
            })
            ->with(['client', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * دوال مساعدة خاصة
     */

    private function generateInvoiceNumber(): string
    {
        $timestamp = now()->format('YmdHis');
        $random = Str::upper(Str::random(6));
        return "INV-{$timestamp}-{$random}";
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = collect($items)->sum(function ($item) {
            return ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
        });

        $taxAmount = $subtotal * 0.15; // 15% ضريبة
        $totalAmount = $subtotal + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    private function createInvoiceItems(int $invoiceId, array $items): void
    {
        foreach ($items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoiceId,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
    }

    private function applyDateFilter($query, string $dateFilter): void
    {
        $now = now();

        switch ($dateFilter) {
            case 'this_month':
                $query->whereYear('issue_date', $now->year)
                      ->whereMonth('issue_date', $now->month);
                break;

            case 'last_month':
                $lastMonth = $now->subMonth();
                $query->whereYear('issue_date', $lastMonth->year)
                      ->whereMonth('issue_date', $lastMonth->month);
                break;

            case 'this_year':
                $query->whereYear('issue_date', $now->year);
                break;

            case 'last_year':
                $query->whereYear('issue_date', $now->subYear()->year);
                break;
        }
    }
}
