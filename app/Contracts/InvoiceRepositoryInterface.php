<?php

namespace App\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InvoiceRepositoryInterface
{
    /**
     * الحصول على فواتير المستخدم مع الفلترة
     */
    public function getUserInvoices(int $userId, array $filters = []): LengthAwarePaginator;

    /**
     * إنشاء فاتورة جديدة
     */
    public function createInvoice(array $data);

    /**
     * تحديث فاتورة موجودة
     */
    public function updateInvoice(array $data, int $id);

    /**
     * تحديث حالة الفاتورة
     */
    public function updateInvoiceStatus(int $id, string $status);

    /**
     * الحصول على فاتورة مع العناصر والعلاقات
     */
    public function getInvoiceWithItems(int $id);

    /**
     * حذف فاتورة
     */
    public function deleteInvoice(int $id): bool;

    /**
     * الحصول على فاتورة للمستخدم المحدد
     */
    public function getUserInvoice(int $userId, int $invoiceId);

    /**
     * الحصول على إحصائيات الفواتير للمستخدم
     */
    public function getUserInvoiceStats(int $userId): array;

    /**
     * الحصول على الفواتير حسب الحالة
     */
    public function getInvoicesByStatus(int $userId, string $status): Collection;

    /**
     * البحث في فواتير المستخدم
     */
    public function searchUserInvoices(int $userId, string $searchTerm): LengthAwarePaginator;
}
