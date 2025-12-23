<?php

namespace App\Repository\Admin\Invoice;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceRepository implements InvoiceInterface
{
    public function index($request)
    {
        try {
            $query = Invoice::with(['client', 'createdBy', 'items'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('invoice_date', [
                    $request->date_from,
                    $request->date_to
                ]);
            }

            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Get paginated or all results
            if ($request->has('per_page')) {
                $invoices = $query->paginate(
                    min($request->per_page, Constants::MAX_PER_PAGE)
                );
            } else {
                $invoices = $query->get();
            }

            return [
                'status' => true,
                'message' => 'Invoices retrieved successfully',
                'data' => $invoices
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve invoices: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with(['client', 'createdBy', 'items'])->find($id);

            if (!$invoice) {
                return [
                    'status' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ];
            }

            return [
                'status' => true,
                'message' => 'Invoice retrieved successfully',
                'data' => $invoice
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            // Generate invoice number if not provided
            $invoiceNumber = $request->invoice_number ??
                (new Invoice())->generateInvoiceNumber();

            // Create invoice
            $invoice = Invoice::create([
                'client_id' => $request->client_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'status' => Constants::INVOICE_STATUS_DRAFT,
                'subtotal' => $request->subtotal,
                'tax_amount' => $request->tax_amount ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'total' => $request->total,
                'currency' => $request->currency ?? Constants::CURRENCY_USD,
                'notes' => $request->notes,
                'terms' => $request->terms,
                'footer' => $request->footer,
                'created_by' => auth()->id(),
                'is_active' => true
            ]);

            // Create invoice items
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'total' => $item['quantity'] * $item['unit_price'],
                    'item_type' => $item['item_type'] ?? Constants::ITEM_TYPE_SERVICE,
                    'notes' => $item['notes'] ?? null
                ]);
            }

            // Log activity
            ActivityLog::log(
                'CREATE',
                'Created invoice #' . $invoice->invoice_number,
                $invoice
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice->load('items')
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function update($request, $invoice)
    {
        DB::beginTransaction();

        try {
            $oldValues = $invoice->toArray();

            // Update invoice
            $invoice->update([
                'client_id' => $request->client_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'subtotal' => $request->subtotal,
                'tax_amount' => $request->tax_amount ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'total' => $request->total,
                'currency' => $request->currency ?? $invoice->currency,
                'notes' => $request->notes,
                'terms' => $request->terms,
                'footer' => $request->footer
            ]);

            // Delete existing items
            $invoice->items()->delete();

            // Create new items
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'total' => $item['quantity'] * $item['unit_price'],
                    'item_type' => $item['item_type'] ?? Constants::ITEM_TYPE_SERVICE,
                    'notes' => $item['notes'] ?? null
                ]);
            }

            // Log activity
            ActivityLog::log(
                'UPDATE',
                'Updated invoice #' . $invoice->invoice_number,
                $invoice,
                $oldValues,
                $invoice->fresh()->toArray()
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice->load('items')
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'Failed to update invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function destroy($invoice)
    {
        DB::beginTransaction();

        try {
            $invoiceNumber = $invoice->invoice_number;
            $invoiceId = $invoice->id;

            // Log activity before deletion
            ActivityLog::log(
                'DELETE',
                'Deleted invoice #' . $invoiceNumber,
                $invoice
            );

            // Delete invoice items
            $invoice->items()->delete();

            // Delete invoice
            $invoice->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => 'Invoice deleted successfully',
                'data' => ['id' => $invoiceId]
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'Failed to delete invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function sendInvoice($invoice)
    {
        try {
            if ($invoice->status !== Constants::INVOICE_STATUS_DRAFT) {
                return [
                    'status' => false,
                    'message' => 'Only draft invoices can be sent',
                    'data' => null
                ];
            }

            $invoice->markAsSent();

            // Log activity
            ActivityLog::log(
                'SEND',
                'Sent invoice #' . $invoice->invoice_number . ' to client',
                $invoice
            );

            // TODO: Send email notification to client

            return [
                'status' => true,
                'message' => 'Invoice sent successfully',
                'data' => $invoice
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to send invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function markAsPaid($invoice)
    {
        try {
            $invoice->markAsPaid();

            // Log activity
            ActivityLog::log(
                'PAYMENT',
                'Marked invoice #' . $invoice->invoice_number . ' as paid',
                $invoice
            );

            return [
                'status' => true,
                'message' => 'Invoice marked as paid',
                'data' => $invoice
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to mark invoice as paid: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function duplicate($invoice)
    {
        DB::beginTransaction();

        try {
            // Duplicate invoice
            $newInvoice = $invoice->replicate();
            $newInvoice->invoice_number = (new Invoice())->generateInvoiceNumber();
            $newInvoice->status = Constants::INVOICE_STATUS_DRAFT;
            $newInvoice->sent_at = null;
            $newInvoice->paid_at = null;
            $newInvoice->created_at = now();
            $newInvoice->updated_at = now();
            $newInvoice->save();

            // Duplicate items
            foreach ($invoice->items as $item) {
                $newItem = $item->replicate();
                $newItem->invoice_id = $newInvoice->id;
                $newItem->created_at = now();
                $newItem->updated_at = now();
                $newItem->save();
            }

            // Log activity
            ActivityLog::log(
                'DUPLICATE',
                'Duplicated invoice #' . $invoice->invoice_number . ' to #' . $newInvoice->invoice_number,
                $newInvoice
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Invoice duplicated successfully',
                'data' => $newInvoice->load('items')
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'Failed to duplicate invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function generatePDF($invoice)
    {
        try {
            $data = [
                'invoice' => $invoice->load(['client', 'items']),
                'settings' => [
                    'company_name' => config('app.company_name', 'Your Company'),
                    'company_address' => config('app.company_address', ''),
                    'company_logo' => config('app.company_logo', ''),
                    'company_phone' => config('app.company_phone', ''),
                    'company_email' => config('app.company_email', ''),
                ]
            ];

            $pdf = PDF::loadView('pdf.invoice', $data);

            $fileName = 'invoice-' . $invoice->invoice_number . '.pdf';
            $filePath = 'invoices/' . $fileName;

            // Save to storage
            Storage::disk('public')->put($filePath, $pdf->output());

            return [
                'status' => true,
                'message' => 'PDF generated successfully',
                'data' => [
                    'file_path' => $filePath,
                    'file_url' => Storage::disk('public')->url($filePath),
                    'file_name' => $fileName
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getDashboardStats()
    {
        try {
            $totalInvoices = Invoice::count();
            $totalRevenue = Invoice::sum('total');
            $paidInvoices = Invoice::where('status', Constants::INVOICE_STATUS_PAID)->count();
            $paidRevenue = Invoice::where('status', Constants::INVOICE_STATUS_PAID)->sum('total');
            $overdueInvoices = Invoice::overdue()->count();
            $overdueAmount = Invoice::overdue()->sum('total');

            $monthlyData = Invoice::select(
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('YEAR(invoice_date) as year'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->whereYear('invoice_date', date('Y'))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

            $stats = [
                'total_invoices' => $totalInvoices,
                'total_revenue' => $totalRevenue,
                'paid_invoices' => $paidInvoices,
                'paid_revenue' => $paidRevenue,
                'overdue_invoices' => $overdueInvoices,
                'overdue_amount' => $overdueAmount,
                'monthly_data' => $monthlyData,
                'invoice_status_distribution' => [
                    'draft' => Invoice::draft()->count(),
                    'sent' => Invoice::sent()->count(),
                    'paid' => Invoice::paid()->count(),
                    'overdue' => Invoice::overdue()->count(),
                ]
            ];

            return [
                'status' => true,
                'message' => 'Dashboard stats retrieved successfully',
                'data' => $stats
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve dashboard stats: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getRecentInvoices($limit = 10)
    {
        try {
            $invoices = Invoice::with('client')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return [
                'status' => true,
                'message' => 'Recent invoices retrieved successfully',
                'data' => $invoices
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve recent invoices: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getOverdueInvoices()
    {
        try {
            $invoices = Invoice::with('client')
                ->overdue()
                ->get();

            return [
                'status' => true,
                'message' => 'Overdue invoices retrieved successfully',
                'data' => $invoices
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve overdue invoices: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
     public function export($request)
    {
        try {
            $query = Invoice::with(['client', 'createdBy', 'items'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('invoice_date', [
                    $request->date_from,
                    $request->date_to
                ]);
            }

            if ($request->has('search')) {
                $query->search($request->search);
            }

            $invoices = $query->get();

            // Prepare data for export
            $exportData = [];
            foreach ($invoices as $invoice) {
                $exportData[] = [
                    'Invoice Number' => $invoice->invoice_number,
                    'Client' => $invoice->client->name ?? 'N/A',
                    'Date' => $invoice->invoice_date->format('Y-m-d'),
                    'Due Date' => $invoice->due_date->format('Y-m-d'),
                    'Status' => $invoice->status,
                    'Subtotal' => $invoice->subtotal,
                    'Tax' => $invoice->tax_amount,
                    'Discount' => $invoice->discount_amount,
                    'Total' => $invoice->total,
                    'Currency' => $invoice->currency,
                    'Created By' => $invoice->createdBy->name ?? 'N/A',
                    'Created At' => $invoice->created_at->format('Y-m-d H:i:s'),
                ];
            }

            return [
                'status' => true,
                'message' => 'Data prepared for export',
                'data' => $exportData
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to prepare export: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getStats($request)
    {
        try {
            $query = Invoice::query();

            // Apply date filter if provided
            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('invoice_date', [
                    $request->date_from,
                    $request->date_to
                ]);
            } elseif ($request->has('year')) {
                $query->whereYear('invoice_date', $request->year);
                if ($request->has('month')) {
                    $query->whereMonth('invoice_date', $request->month);
                }
            }

            // Calculate stats
            $totalInvoices = $query->count();
            $totalRevenue = $query->sum('total');
            $totalTax = $query->sum('tax_amount');
            $totalDiscount = $query->sum('discount_amount');

            // Status counts
            $draftCount = $query->clone()->where('status', Constants::INVOICE_STATUS_DRAFT)->count();
            $sentCount = $query->clone()->where('status', Constants::INVOICE_STATUS_SENT)->count();
            $paidCount = $query->clone()->where('status', Constants::INVOICE_STATUS_PAID)->count();
            $overdueCount = $query->clone()->where('status', Constants::INVOICE_STATUS_OVERDUE)->count();
            $cancelledCount = $query->clone()->where('status', Constants::INVOICE_STATUS_CANCELLED)->count();

            // Average invoice value
            $averageValue = $totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0;

            // Monthly data for the current year
            $monthlyData = Invoice::select(
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('YEAR(invoice_date) as year'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total) as total_revenue')
            )
            ->whereYear('invoice_date', date('Y'))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

            // Top clients by revenue
            $topClients = \App\Models\Client::withSum('invoices', 'total')
                ->orderBy('invoices_sum_total', 'desc')
                ->limit(5)
                ->get()
                ->map(function($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'total_revenue' => $client->invoices_sum_total ?? 0,
                        'invoice_count' => $client->invoices()->count()
                    ];
                });

            $stats = [
                'summary' => [
                    'total_invoices' => $totalInvoices,
                    'total_revenue' => $totalRevenue,
                    'total_tax' => $totalTax,
                    'total_discount' => $totalDiscount,
                    'average_invoice_value' => $averageValue,
                ],
                'status_distribution' => [
                    'draft' => $draftCount,
                    'sent' => $sentCount,
                    'paid' => $paidCount,
                    'overdue' => $overdueCount,
                    'cancelled' => $cancelledCount,
                ],
                'monthly_data' => $monthlyData,
                'top_clients' => $topClients,
                'currency_distribution' => [
                    'USD' => $query->clone()->where('currency', 'USD')->sum('total'),
                    'EUR' => $query->clone()->where('currency', 'EUR')->sum('total'),
                    'GBP' => $query->clone()->where('currency', 'GBP')->sum('total'),
                    'SAR' => $query->clone()->where('currency', 'SAR')->sum('total'),
                    'AED' => $query->clone()->where('currency', 'AED')->sum('total'),
                ],
            ];

            return [
                'status' => true,
                'message' => 'Invoice statistics retrieved successfully',
                'data' => $stats
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
