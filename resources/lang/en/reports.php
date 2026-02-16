<?php

return [
    // General Headers
    'invoice_number' => 'Invoice Number',
    'client' => 'Client',
    'issue_date' => 'Issue Date',
    'due_date' => 'Due Date',
    'total' => 'Total',
    'paid' => 'Paid',
    'remaining' => 'Remaining',
    'status' => 'Status',

    // Client Report
    'invoices_count' => 'Invoices Count',
    'total_amount' => 'Total Amount',
    'paid_amount' => 'Paid Amount',
    'due_amount' => 'Due Amount',
    'average_invoice' => 'Average Invoice',
    'created_at' => 'Created At',
    'last_invoice' => 'Last Invoice',
    'client_status' => 'Client Status',
    'active' => 'Active',
    'inactive' => 'Inactive',

    // Revenue Report
    'month' => 'Month',
    'total_revenue' => 'Total Revenue',
    'collected_revenue' => 'Collected Revenue',
    'outstanding_revenue' => 'Outstanding Revenue',
    'collection_rate' => 'Collection Rate',
    'average_monthly_revenue' => 'Average Monthly Revenue',

    // Overdue Report
    'overdue_days' => 'Overdue Days',

    // Common words
    'unknown' => 'Unknown',
    'none' => 'None',

    // Invoice status (if not already defined elsewhere)
    'invoices' => [
        'status' => [
            'paid' => 'Paid',
            'unpaid' => 'Unpaid',
            'partially_paid' => 'Partially Paid',
            'sent' => 'Sent',
            'draft' => 'Draft',
            'overdue' => 'Overdue',
        ],
    ],

    // Client fields
    'clients' => [
        'name' => 'Client Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'company' => 'Company Name',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    // Report statistics and info
    'reports' => [
        'statistics' => 'Report Statistics',
        'total_clients' => 'Total Clients',
        'active_clients' => 'Active Clients',
        'total_invoices' => 'Total Invoices',
        'total_revenue' => 'Total Revenue',
        'collection_rate' => 'Collection Rate',
        'report_info' => 'Report Information',
        'generated_at' => 'Generated At',
        'client_report' => 'Client Report',
    ],
];
