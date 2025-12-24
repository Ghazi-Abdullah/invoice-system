<?php

namespace App\Constants;

class Constants
{
    // Status Constants
    const ACTIVE = 1;
    const INACTIVE = 0;

    // User Groups
    const SUPER_ADMIN_GROUP_ID = 1;
    const ADMIN_GROUP_ID = 2;
    const CLIENT_GROUP_ID = 5;

    // Permission Titles
    const VIEW_DASHBOARD = 'view_dashboard';
    const VIEW_INVOICES = 'view_invoices';
    const CREATE_INVOICE = 'create_invoice';
    const EDIT_INVOICE = 'edit_invoice';
    const DELETE_INVOICE = 'delete_invoice';
    const SEND_INVOICE = 'send_invoice';
    const DOWNLOAD_INVOICE = 'download_invoice';
    const VIEW_CLIENTS = 'view_clients';
    const CREATE_CLIENT = 'create_client';
    const EDIT_CLIENT = 'edit_client';
    const DELETE_CLIENT = 'delete_client';
    const VIEW_USERS = 'view_users';
    const CREATE_USER = 'create_user';
    const EDIT_USER = 'edit_user';
    const DELETE_USER = 'delete_user';
    const VIEW_ADMIN_GROUPS = 'view_admin_groups';
    const MANAGE_ADMIN_GROUPS = 'manage_admin_groups';
    const VIEW_REPORTS = 'view_reports';
    const EXPORT_REPORTS = 'export_reports';
    const MANAGE_PERMISSIONS = 'manage_permissions';
    const MANAGE_USER_GROUPS = 'manage_user_groups';
    const VIEW_PERMISSIONS = 'view_permissions';

    // Pagination
    const DEFAULT_PER_PAGE = 10;
    const MAX_PER_PAGE = 100;
    const MIN_PER_PAGE = 1;

    // Invoice Status
    const INVOICE_STATUS_DRAFT = 'draft';
    const INVOICE_STATUS_SENT = 'sent';
    const INVOICE_STATUS_PAID = 'paid';
    const INVOICE_STATUS_OVERDUE = 'overdue';
    const INVOICE_STATUS_CANCELLED = 'cancelled';

    // Invoice Payment Terms
    const PAYMENT_TERM_NET_7 = 'net_7';
    const PAYMENT_TERM_NET_15 = 'net_15';
    const PAYMENT_TERM_NET_30 = 'net_30';
    const PAYMENT_TERM_NET_60 = 'net_60';

    // Currency
    const CURRENCY_SAR = 'SAR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';

    // Response Status Codes
    const RESPONSE_SUCCESS = 200;
    const RESPONSE_CREATED = 201;
    const RESPONSE_BAD_REQUEST = 400;
    const RESPONSE_UNAUTHORIZED = 401;
    const RESPONSE_FORBIDDEN = 403;
    const RESPONSE_NOT_FOUND = 404;
    const RESPONSE_VALIDATION_ERROR = 422;
    const RESPONSE_SERVER_ERROR = 500;
}
